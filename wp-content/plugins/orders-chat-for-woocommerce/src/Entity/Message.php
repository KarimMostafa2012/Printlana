<?php namespace U2Code\OrderMessenger\Entity;

use DateTime;
use Exception;
use U2Code\OrderMessenger\Config\Config;
use WP_User;
use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Database\OrderMessagesTable;

class Message {

	const MAX_LENGTH = 3000;

	private $message;
	private $orderId;

	private $dateSent;
	private $dateRead;

	private $isNotified;
	private $messageType;
	private $userId;
	private $senderId;
	private $attachment;
	private $data;
	private $id;

	/**
	 * Message constructor.
	 *
	 * @param string $message
	 * @param int $orderId
	 * @param int $userId
	 * @param int $senderId
	 * @param MessageType $messageType
	 * @param MessageAttachment $attachment
	 * @param bool $isNotified
	 * @param DateTime $dateSent
	 * @param DateTime $dateRead
	 * @param array $data
	 * @param null|int $id
	 */
	public function __construct( $message, $orderId, $userId, $senderId, MessageType $messageType, MessageAttachment $attachment = null, DateTime $dateSent = null, $isNotified = false, DateTime $dateRead = null, array $data = array(), $id = null ) {
		$this->message     = $message;
		$this->orderId     = $orderId;
		$this->userId      = $userId;
		$this->senderId    = $senderId;
		$this->messageType = $messageType;
		$this->isNotified  = $isNotified;
		$this->dateSent    = $dateSent ? $dateSent : new DateTime( 'now' );
		$this->dateRead    = $dateRead;
		$this->attachment  = $attachment;
		$this->data        = $data;
		$this->id          = $id;
	}

	/**
	 * Get message
	 *
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Set message
	 *
	 * @param string $message
	 */
	public function setMessage( $message ) {
		$this->message = $message;
	}

	/**
	 * Get order id
	 *
	 * @return int
	 */
	public function getOrderId() {
		return $this->orderId;
	}

	/**
	 * Set order id
	 *
	 * @param int $orderId
	 */
	public function setOrderId( $orderId ) {
		$this->orderId = $orderId;
	}

	/**
	 * Get Date sent
	 *
	 * @param bool $localTimeZone
	 *
	 * @return DateTime
	 */
	public function getDateSent( $localTimeZone = true ) {

		if ( $localTimeZone ) {
			$localDateSend = clone $this->dateSent;
			$localDateSend->setTimezone( wp_timezone() );

			return $localDateSend;
		}

		return $this->dateSent;
	}

	/**
	 * Set date sent
	 *
	 * @param DateTime $dateSent
	 */
	public function setDateSent( $dateSent ) {
		$this->dateSent = $dateSent;
	}

	/**
	 * Get date read
	 *
	 * @param bool $localTimeZone
	 *
	 * @return DateTime
	 */
	public function getDateRead( $localTimeZone = true ) {

		if ( $localTimeZone ) {
			$localDateRead = $this->dateRead ? clone $this->dateRead : null;

			return $localDateRead ? $localDateRead->setTimezone( wp_timezone() ) : null;
		}

		return $this->dateRead;
	}

	/**
	 * Set date read
	 *
	 * @param DateTime $dateRead
	 */
	public function setDateRead( $dateRead ) {
		$this->dateRead = $dateRead;
	}

	/**
	 * Is notified
	 *
	 * @return bool
	 */
	public function isNotified() {
		return $this->isNotified;
	}

	/**
	 * Set is notified
	 *
	 * @param bool $isNotified
	 */
	public function setIsNotified( $isNotified ) {
		$this->isNotified = $isNotified;
	}

	/**
	 * Get message type
	 *
	 * @return MessageType
	 */
	public function getMessageType() {
		return $this->messageType;
	}

	/**
	 * Set message type
	 *
	 * @param MessageType $messageType
	 */
	public function setMessageType( MessageType $messageType ) {
		$this->messageType = $messageType;
	}

	/**
	 * Get user id
	 *
	 * @return int
	 */
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * Set user id
	 *
	 * @param int $userId
	 */
	public function setUserId( $userId ) {
		$this->userId = $userId;
	}

	/**
	 * Get attachment
	 *
	 * @return null|MessageAttachment
	 */
	public function getAttachment() {
		return $this->attachment;
	}

	/**
	 * Set attachment
	 *
	 * @param MessageAttachment $attachment
	 */
	public function setAttachment( MessageAttachment $attachment ) {
		$this->attachment = $attachment;
	}

	/**
	 * Get message data
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Set message data
	 *
	 * @param array $data
	 */
	public function setData( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get id
	 *
	 * @return null|int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set id
	 *
	 * @param null|int $id
	 */
	public function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * Get sender id
	 *
	 * @return int
	 */
	public function getSenderId() {
		return $this->senderId;
	}

	/**
	 * Get sender
	 *
	 * @return WP_User
	 */
	public function getSender() {
		return new WP_User( $this->getSenderId() );
	}

	/**
	 * Set sender id
	 *
	 * @param int $senderId
	 */
	public function setSenderId( $senderId ) {
		$this->senderId = $senderId;
	}

	public function getSenderName() {
		$storeSignature = Config::getStoreSignature();

		if ( $storeSignature && ! $this->getMessageType()->isCustomer() ) {
			$name = $storeSignature;
		} else {
			$sender = $this->getSender();

			if ( $sender ) {
				$name = $this->getSender()->display_name;
			} else {
				if ( $this->getMessageType()->isCustomer() ) {
					$name = __( 'User', 'order-messenger-for-woocommerce' );
				} else {
					$name = __( 'Admin', 'order-messenger-for-woocommerce' );
				}
			}
		}

		return apply_filters( 'order_messenger/message/sender_name', $name, $this );
	}

	public function getViewPath( $place = 'frontend' ) {
		$fileManager = ServiceContainer::getInstance()->getFileManager();

		$path = $fileManager->locateTemplate( $place . '/message/message-types/' . $this->getMessageType()->getName() . '-message.php' );

		return apply_filters( 'order_messenger/message/message_template_page', $path, $place, $this );
	}

	/**
	 * Save entity
	 *
	 * @throws Exception
	 */
	public function save() {
		if ( ! empty( $this->getId() ) ) {
			$this->update();
			do_action( 'order_messenger/messages/message_updated', $this );
		} else {
			$this->create();
			do_action( 'order_messenger/messages/message_created', $this );
		}
	}

	public function delete() {
		global $wpdb;

		$wpdb->delete( OrderMessagesTable::getTableName(), array( 'id' => $this->getId() ) );
	}

	public function unread() {
		global $wpdb;

		$wpdb->update( OrderMessagesTable::getTableName(), array( 'date_read' => null ), array( 'id' => $this->getId() ) );
	}

	/**
	 * Get message as an array
	 *
	 * @param false $includeID
	 * @param bool $localTimeZone
	 *
	 * @return array
	 */
	public function getAsArray( $includeID = false, $localTimeZone = true ) {
		$data = array(
			'order_id'      => $this->getOrderId(),
			'user_id'       => $this->getUserId(),
			'sender_id'     => $this->getSenderId(),
			'attachment_id' => $this->getAttachment() ? $this->getAttachment()->getId() : 0,
			'message'       => $this->getMessage(),
			'is_notified'   => $this->isNotified(),
			'type'          => $this->getMessageType()->toInt(),
			'date_sent'     => $this->getDateSent( $localTimeZone )->format( 'Y-m-d H:i:s' ),
			'date_read'     => $this->getDateRead() ? $this->getDateRead( $localTimeZone )->format( 'Y-m-d H:i:s' ) : null,
			'data'          => json_encode( $this->getData() ),
		);

		if ( $includeID ) {
			$data['id'] = $this->getId();
		}

		return $data;
	}

	/**
	 * Update message
	 */
	protected function update() {
		global $wpdb;

		$wpdb->update( OrderMessagesTable::getTableName(), $this->getAsArray( false, false ), array( 'id' => $this->getId() ) );
	}

	/**
	 * Create message
	 *
	 * @throws Exception
	 */
	protected function create() {
		global $wpdb;

		$result = $wpdb->insert( OrderMessagesTable::getTableName(), $this->getAsArray( false, false ) );

		if ( ! $result ) {
			throw new Exception( esc_html($wpdb->last_error) );
		}

		$this->setId( $wpdb->insert_id );
	}

	public static function createOrderStatusMessage( $orderId, $customerId, $from, $to ) {

		$message = new Message( null, $orderId, $customerId, 0, MessageType::orderStatus() );

		$message->setData( array(
			'from' => $from,
			'to'   => $to,
		) );

		return $message;
	}
}
