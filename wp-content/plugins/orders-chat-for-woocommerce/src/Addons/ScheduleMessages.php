<?php namespace U2Code\OrderMessenger\Addons;

use DateTime;
use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Database\OrderMessagesTable;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageType;

class ScheduleMessages {
	const SCHEDULED_MESSAGE_TYPE = 4;

	const SCHEDULED_ADMIN_MESSAGE_TYPE = 5;

	const DATE_FORMAT = 'Y-m-d\TH:i:s';

	public function __construct() {

		if ( ! self::isEnabled() ) {
			return;
		}

		// Check for scheduled messages
		add_action( 'init', function () {
			$messageRepository = ServiceContainer::getInstance()->getMessageRepository();

			$enabledTypesList = '(' . self::SCHEDULED_MESSAGE_TYPE . ')';

			$tableName         = OrderMessagesTable::getTableName();
			$scheduledMessages = $messageRepository->database->getResults( $messageRepository->database->prepare( "SELECT * FROM {$tableName} WHERE type IN {$enabledTypesList} AND data IS NOT NULL ORDER BY `date_sent` DESC LIMIT %d OFFSET %d",
				999999, 0 ), ARRAY_A );

			if ( $scheduledMessages instanceof \WP_Error ) {
				return;
			}

			$scheduledMessaged = array_map( array( $messageRepository, 'rawMessageToInstance' ), $scheduledMessages );

			/**
			 * Scheduled Messages
			 *
			 * @var $scheduledMessages Message[]
			 */
			if ( ! empty( $scheduledMessaged ) ) {
				foreach ( $scheduledMessaged as $scheduledMessage ) {
					$scheduledDate = ! empty( $scheduledMessage->getData()['scheduled_date'] ) ? $scheduledMessage->getData()['scheduled_date'] : false;
					$scheduledDate = $scheduledDate ? DateTime::createFromFormat( 'Y-m-d H:i:s',
						$scheduledDate ) : false;

					if ( ! $scheduledDate ) {
						$scheduledMessage->delete();
					}
					$now = new DateTime();

					if ( $scheduledDate->getTimestamp() < $now->getTimestamp() ) {

						$message = clone $scheduledMessage;

						$scheduledMessageType = apply_filters( 'order_messenger/addons/scheduled_messages/message_type',
							MessageType::ADMIN );

						$message->setId( null );
						$message->setMessageType( MessageType::fromInt( $scheduledMessageType ) );
						$message->setDateSent( new DateTime() );
						$message->setIsNotified( false );
						$message->setDateRead( null );

						$customData = $scheduledMessage->getData();
						if ( isset( $customData['scheduled_date'] ) ) {
							unset( $customData['scheduled_date'] );
						}
						$message->setData( $customData );

						$scheduledMessage->delete();
						try {
							$message->save();
						} catch ( \Exception $e ) {
							continue;
						}
					}
				}
			}
		} );

		add_action( 'order_messenger/admin/messenger_metabox/additional_options', function () {

			$currentDate = new DateTime();

			$currentDate->setTimezone( wp_timezone() );

			$currentDate->setTime( 12, 0 );

			?>
			<hr>
			<div class="order-messenger-schedule-sending">
				<div>
					<input id="order-messenger-schedule-sending-checkbox" type="checkbox"
						   data-schedule-button-text="
						   <?php 
						   esc_attr_e( 'Schedule sending',
							   'order-messenger-for-woocommerce' ); 
							?>
							   "
						   name="order-messenger-custom-data[schedule_sending]">
					<label for="order-messenger-schedule-sending-checkbox">
					<?php 
					esc_attr_e( 'Schedule sending',
							'order-messenger-for-woocommerce' ); 
					?>
							</label>
				</div>

				<div class="order-messenger-schedule-sending-datepicker-wrapper" style="display: none; margin-top: 5px">
					<input type="datetime-local" name="order-messenger-custom-data[schedule_date]"
						   value="<?php echo esc_attr( $currentDate->format( self::DATE_FORMAT ) ); ?>">
					<br>

				</div>
			</div>

			<script>
				jQuery(document).ready(function ($) {

					let defaultSendText = '';

					jQuery('#order-messenger-schedule-sending-checkbox').on('change', function () {
						let sendButton = $(this).closest('.order-messenger').find('[data-order-message-send]');
						if ($(this).is(':checked')) {

							if (!defaultSendText) {
								defaultSendText = sendButton.val();
							}

							sendButton.val($(this).data('schedule-button-text'));

							$(this).closest('.order-messenger-schedule-sending').find('.order-messenger-schedule-sending-datepicker-wrapper').show();
						} else {

							sendButton.val(defaultSendText);

							$(this).closest('.order-messenger-schedule-sending').find('.order-messenger-schedule-sending-datepicker-wrapper').hide();
						}
					})
				});
			</script>

			<?php
		} );

		add_filter( 'order_messenger/message/allowed_types', function ( $types ) {
			$types[ self::SCHEDULED_MESSAGE_TYPE ]       = 'scheduled_message';
			$types[ self::SCHEDULED_ADMIN_MESSAGE_TYPE ] = 'scheduled_admin_message';

			return $types;
		} );

		add_filter( 'order_messenger/messages/before_saving_admin_message',
			function ( Message $message, \WP_REST_Request $request, $customData ) {
				$scheduledMessageDate = false;

				if ( array_key_exists( 'order-messenger-custom-data[schedule_sending]', $customData ) ) {
					$scheduledMessageDate = ! empty( $customData['order-messenger-custom-data[schedule_date]'] ) ? $customData['order-messenger-custom-data[schedule_date]'] : false;
				}

				if ( ! $scheduledMessageDate ) {
					return $message;
				}
				$date = strtotime( $scheduledMessageDate );

				if ( ! $date ) {
					return $message;
				}

				$scheduledMessageDate = ( new DateTime() )->setTimestamp( $date );

				$message->setMessageType( MessageType::fromInt( self::SCHEDULED_MESSAGE_TYPE ) );

				$message->setData( array_merge( $message->getData(), array(
					'scheduled_date' => $scheduledMessageDate->format( 'Y-m-d H:i:s' ),
				) ) );

				return $message;
			}, 10, 3 );

		add_filter( 'order_messenger/message/message_template_page', function ( $path, $place, Message $message ) {

			if ( $message->getMessageType()->toInt() === self::SCHEDULED_MESSAGE_TYPE ) {

				$fileManager = ServiceContainer::getInstance()->getFileManager();

				return $fileManager->locateTemplate( 'addons/scheduled_messages/' . $place . '/message/message-types/scheduled-message.php' );
			}

			if ( $message->getMessageType()->toInt() === self::SCHEDULED_ADMIN_MESSAGE_TYPE ) {
				$fileManager = ServiceContainer::getInstance()->getFileManager();

				return $fileManager->locateTemplate( $place . '/message/message-types/admin-message.php' );
			}

			return $path;
		}, 10, 3 );

		add_filter( 'order_messenger/config/message_types_admin_should_be_notified', function ( $types ) {
			$types[] = self::SCHEDULED_ADMIN_MESSAGE_TYPE;

			return $types;
		} );
	}

	public static function isEnabled() {
		return ServiceContainer::getInstance()->getSettings()->get( 'scheduled_messages_enabled', 'yes' ) === 'yes';
	}
}
