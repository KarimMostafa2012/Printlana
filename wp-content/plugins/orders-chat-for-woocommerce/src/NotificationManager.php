<?php namespace U2Code\OrderMessenger;

use DateTime;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\Database\OrderMessagesTable;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageType;
use U2Code\OrderMessenger\Settings\EmailNotificationsOption;
use WC_Email;

/**
 * Class NotificationManager
 *
 * @package U2Code\OrderMessenger
 */
class NotificationManager {

	use ServiceContainerTrait;

	public function __construct() {

		if ( EmailNotificationsOption::isNotificationsEnabled() ) {

			if ( EmailNotificationsOption::getNotificationsType() === 'group' ) {
				add_action( 'shutdown', array( $this, 'sendNotifications' ) );
			} else {
				add_action( 'order_messenger/messages/message_created', function ( Message $message ) {

					if ( $message->isNotified() ) {
						return;
					}

					$emails = wc()->mailer()->get_emails();

					$adminShouldBeNotified    = Config::getMessageTypesAdminShouldBeNotified();
					$customerShouldBeNotified = Config::getMessageTypesCustomerShouldBeNotified();
					$email                    = false;

					if ( in_array( $message->getMessageType()->toInt(), $adminShouldBeNotified ) ) {

						$email = isset( $emails['OM_Admin_Notification_Email'] ) ? $emails['OM_Admin_Notification_Email'] : false;

					} else if ( in_array( $message->getMessageType()->toInt(), $customerShouldBeNotified ) ) {

						$email = isset( $emails['OM_Customer_Notification_Email'] ) ? $emails['OM_Customer_Notification_Email'] : false;
					}

					if ( ! $email || ! $email->is_enabled() ) {
						return;
					}

					/**
					 * Email
					 *
					 * @var $email WC_Email
					 */
					$email->trigger( $message->getOrderId(), 1 );

					$message->setIsNotified( true );

					try {
						$message->save();
					} catch ( \Exception $e ) {
						return;
					}
				} );

			}
		}

	}

	protected function getNotifications( $type ) {

		$now = new DateTime();

		$notificationDelay = apply_filters( 'order_messenger/notification/email_delay', 5 );

		return ServiceContainer::getInstance()->getDatabase()->getRow(
			ServiceContainer::getInstance()->getDatabase()->prepare(
				'SELECT order_id, COUNT(*) as total_messages, GROUP_CONCAT(DISTINCT id SEPARATOR \',\' ) as messages_ids FROM ' . Database\OrderMessagesTable::getTableName() . ' WHERE type = %d AND is_notified != 1 AND date_read IS NULL AND DATE_ADD(date_sent, INTERVAL %d MINUTE) <= %s GROUP BY order_id LIMIT 1',
				$type,
				$notificationDelay,
				$now->format( 'Y-m-d H:i:s' )
			), ARRAY_A );
	}

	public function needToSendNotifications() {

		if ( isset( $_GET['om_trigger_sending'] ) ) {
			return true;
		}

		// Initial
		if ( ! get_option( 'order_messenger_notifications_last_sending', false ) ) {
			update_option( 'order_messenger_notifications_last_sending', time() );

			return false;
		}

		$lastNotificationSending = (int) get_option( 'order_messenger_notifications_last_sending', time() );

		if ( ( time() - $lastNotificationSending ) < MINUTE_IN_SECONDS ) {
			return false;
		}

		update_option( 'order_messenger_notifications_last_sending', time() );

		return true;
	}

	public function sendAdminNotifications() {
		$emails = wc()->mailer()->get_emails();

		if ( isset( $emails['OM_Admin_Notification_Email'] ) ) {

			$notifications = $this->getNotifications( MessageType::CUSTOMER );

			if ( empty( $adminNotification ) ) {
				return;
			}

			// Email is not enabled
			if ( ! $emails['OM_Admin_Notification_Email']->is_enabled() ) {
				// Mark as send without sending
				ServiceContainer::getInstance()->getDatabase()->query( 'UPDATE ' . OrderMessagesTable::getTableName() . ' SET is_notified = 1 WHERE id IN (' . $notifications['messages_ids'] . ')' );

				return;
			}

			$orderId             = isset( $notifications['order_id'] ) ? intval( $notifications['order_id'] ) : false;
			$totalUnreadMessages = isset( $notifications['total_messages'] ) ? intval( $notifications['total_messages'] ) : false;

			if ( $orderId && $totalUnreadMessages && $emails['OM_Admin_Notification_Email']->trigger( $orderId, $totalUnreadMessages ) ) {
				ServiceContainer::getInstance()->getDatabase()->query( 'UPDATE ' . OrderMessagesTable::getTableName() . ' SET is_notified = 1 WHERE id IN (' . $notifications['messages_ids'] . ')' );
			}
		}
	}

	public function sendCustomerNotifications() {
		$emails = wc()->mailer()->get_emails();

		if ( isset( $emails['OM_Customer_Notification_Email'] ) ) {

			$notifications = $this->getNotifications( MessageType::ADMIN );

			if ( empty( $notifications ) ) {
				return;
			}

			// Email is not enabled
			if ( ! $emails['OM_Customer_Notification_Email']->is_enabled() ) {
				ServiceContainer::getInstance()->getDatabase()->query( 'UPDATE ' . OrderMessagesTable::getTableName() . ' SET is_notified = 1 WHERE id IN (' . $notifications['messages_ids'] . ')' );

				return;
			}

			$orderId             = isset( $notifications['order_id'] ) ? intval( $notifications['order_id'] ) : false;
			$totalUnreadMessages = isset( $notifications['total_messages'] ) ? intval( $notifications['total_messages'] ) : false;

			if ( $orderId && $totalUnreadMessages && $emails['OM_Customer_Notification_Email']->trigger( $orderId, $totalUnreadMessages ) ) {
				ServiceContainer::getInstance()->getDatabase()->query( 'UPDATE ' . OrderMessagesTable::getTableName() . ' SET is_notified = 1 WHERE id IN (' . $notifications['messages_ids'] . ')' );
			}
		}
	}

	public function sendNotifications() {

		if ( ! $this->needToSendNotifications() ) {
			return;
		}

		$this->sendAdminNotifications();
		$this->sendCustomerNotifications();
	}
}
