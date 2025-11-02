<?php namespace U2Code\OrderMessenger\Repository;

use DateTime;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use WP_Error;
use Exception;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageType;
use U2Code\OrderMessenger\Entity\MessageAttachment;
use U2Code\OrderMessenger\Database\OrderMessagesTable;

class MessageRepository {

	/**
	 * WPDB
	 */
	public $database;

	use ServiceContainerTrait;

	public function __construct() {
		$this->database = $this->getContainer()->getDatabase();
	}

	/**
	 * Get messages for order
	 *
	 * @param  int  $type
	 * @param  int  $offset
	 * @param  null  $limit
	 * @param  null|string  $context
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function get( $types = array(), $offset = 0, $limit = null, $context = null ) {
		$limit = $limit ? $limit : Config::getPreloadMessagesCount();

		if ( empty( $types ) ) {
			$types = Config::getEnabledMessageTypes( $context );
		}

		$enabledTypesList = '(' . implode( ',', $types ) . ')';

		$tableName = OrderMessagesTable::getTableName();

		$messages = $this->database->getResults( $this->database->prepare( "SELECT * FROM {$tableName} WHERE type IN {$enabledTypesList} ORDER BY `date_sent` DESC LIMIT %d OFFSET %d",
			$limit, $offset ), ARRAY_A );

		if ( $messages instanceof WP_Error ) {
			throw new Exception( 'Messages error' );
		}

		$messages = array_map( array( $this, 'rawMessageToInstance' ), $messages );

		return apply_filters( 'order_messenger/message_repository/get_messages', $messages, $types, $offset, $limit );
	}

	/**
	 * Get messages for order
	 *
	 * @param $orderId
	 * @param  int  $offset
	 * @param  null  $limit
	 * @param  string  $context
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function getForOrder( $orderId, $offset = 0, $limit = null, $context = null ) {
		$limit = $limit ? $limit : Config::getPreloadMessagesCount();

		$enabledTypes = Config::getEnabledMessageTypes( $context );

		$enabledTypesList = '(' . implode( ',', $enabledTypes ) . ')';

		$tableName = OrderMessagesTable::getTableName();

		$messages = $this->database->getResults( $this->database->prepare( "SELECT * FROM {$tableName} WHERE order_id = %d AND type IN {$enabledTypesList} ORDER BY `date_sent` DESC LIMIT %d OFFSET %d",
			$orderId, $limit, $offset ), ARRAY_A );

		if ( $messages instanceof WP_Error ) {
			throw new Exception( 'Messages error' );
		}

		$messages = array_map( array( $this, 'rawMessageToInstance' ), $messages );

		return apply_filters( 'order_messenger/message_repository/order_messages', $messages, $orderId, $offset,
			$limit );
	}

	public function deleteMessagesForOrder( $orderId ) {

		do_action( 'order_messenger/message_repository/before_delete_messages_for_order', $orderId );

		$this->database->delete( OrderMessagesTable::getTableName(), array(
			'order_id' => $orderId,
		) );
	}

	/**
	 * Get unread messages for order
	 *
	 * @param  int  $orderId
	 * @param  int  $type
	 * @param  int  $offset
	 * @param  int  $limit
	 *
	 * @return array
	 */
	public function getUnreadForOrder( $orderId, $type = MessageType::CUSTOMER, $offset = 0, $limit = null ) {

		$limit = $limit ? $limit : Config::getPreloadMessagesCount();

		$tableName = OrderMessagesTable::getTableName();

		$messages = $this->database->getResults( $this->database->prepare( "SELECT * FROM {$tableName} WHERE order_id = %d AND type = %d AND date_read IS NULL ORDER BY `date_sent` DESC LIMIT %d OFFSET %d",
			$orderId, $type, $limit, $offset ), ARRAY_A );

		$messages = array_map( array( $this, 'rawMessageToInstance' ), $messages );

		return apply_filters( 'order_messenger/message_repository/unread_order_messages', $messages, $type, $offset,
			$limit );
	}

	/**
	 * Make messages as read for order
	 *
	 * @param $orderId
	 * @param  int  $type
	 * @param  DateTime  $date
	 */
	public function makeMessagesAsReadForOrder( $orderId, $context = 'admin', DateTime $date = null ) {
		if ( ! $date ) {
			$date = new DateTime();
		}
		$types = array();

		if ( 'admin' === $context ) {
			$types = Config::getMessageTypesAdminShouldBeNotified();
		} elseif ( 'customer' === $context ) {
			$types = Config::getMessageTypesCustomerShouldBeNotified();
		}

		if ( ! empty( $types ) ) {
			$types     = '(' . implode( ',', $types ) . ')';
			$tableName = OrderMessagesTable::getTableName();
			$this->database->query( $this->database->prepare( "UPDATE {$tableName} SET date_read = %s WHERE order_id = %d AND type IN {$types}",
				$date->format( 'Y-m-d H:i:s' ), $orderId ) );
		}
	}

	public function getUnreadCountForOrder( $orderId, $context = 'admin' ) {

		$types = array();

		if ( 'admin' === $context ) {
			$types = Config::getMessageTypesAdminShouldBeNotified();
		} elseif ( 'customer' === $context ) {
			$types = Config::getMessageTypesCustomerShouldBeNotified();
		}

		if ( ! empty( $types ) ) {
			$tableName = OrderMessagesTable::getTableName();
			$types     = '(' . implode( ',', $types ) . ')';

			$totalQuery = $this->database->getResults( $this->database->prepare( "SELECT COUNT(*) as total FROM {$tableName} WHERE order_id = %d AND date_read IS NULL AND type IN {$types}",
				$orderId ), ARRAY_A );

			if ( $totalQuery instanceof WP_Error ) {
				throw new Exception( 'Database error' );
			}

			$total = ! empty( $totalQuery[0]['total'] ) ? (int) $totalQuery[0]['total'] : 0;

			return apply_filters( 'order_messenger/message_repository/unread_order_messages_count', $total, $context,
				$orderId );
		}

		return 0;
	}

	public function getUnreadMessagesCount( $types = array() ) {

		$types = ! empty( $types ) ? $types : Config::getMessageTypesAdminShouldBeNotified();

		$totalQuery = $this->database->getRow( 'SELECT COUNT(*) as total FROM ' . OrderMessagesTable::getTableName() . ' WHERE  date_read IS NULL AND type IN (' . implode( ',',
				$types ) . ')', ARRAY_A );

		if ( $totalQuery instanceof WP_Error ) {
			throw new Exception( 'Database error' );
		}

		$total = ! empty( $totalQuery['total'] ) ? (int) $totalQuery['total'] : 0;

		return apply_filters( 'order_messenger/message_repository/unread_messages_count', $total, $types );
	}

	public function getUnreadOrdersIds( $type = MessageType::CUSTOMER ) {

		$ordersIds = $this->database->getColumn( 'SELECT order_id FROM ' . OrderMessagesTable::getTableName() . ' WHERE date_read IS NULL AND type IN (' . implode( ',',
				Config::getMessageTypesAdminShouldBeNotified() ) . ')' );

		if ( $ordersIds instanceof WP_Error ) {
			throw new Exception( 'Database error' );
		}

		$ordersIds = array_values( (array) $ordersIds );

		return apply_filters( 'order_messenger/message_repository/unread_orders_ids', $ordersIds, $type );
	}

	public function getUnreadMessagesCountForUser( $userId ) {

		$dontNotifyTypes = array( MessageType::ORDER_STATUS );

		$enabledTypes = array_diff( Config::getEnabledMessageTypes(), $dontNotifyTypes );

		$type = MessageType::ADMIN;

		$totalQuery = $this->database->getResults( $this->database->prepare( 'SELECT COUNT(*) as total FROM ' . OrderMessagesTable::getTableName() . ' WHERE type = %d AND date_read IS NULL AND type IN (' . implode( ',',
				$enabledTypes ) . ') AND user_id = %d', $type, $userId ), ARRAY_A );

		if ( $totalQuery instanceof WP_Error ) {
			throw new Exception( 'Database error' );
		}

		$total = ! empty( $totalQuery[0]['total'] ) ? (int) $totalQuery[0]['total'] : 0;

		return apply_filters( 'order_messenger/message_repository/total_unread_user_messages', $total, $userId );
	}

	/**
	 * Get total messages count for order
	 *
	 * @param $orderId
	 * @param  null  $context
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getTotalForOrder( $orderId, $context = null ) {

		$enabledTypes = Config::getEnabledMessageTypes( $context );

		$totalQuery = $this->database->getResults( $this->database->prepare( 'SELECT COUNT(*) as total FROM ' . OrderMessagesTable::getTableName() . ' WHERE order_id = %d AND type IN (' . implode( ',',
				$enabledTypes ) . ')', $orderId ), ARRAY_A );

		if ( $totalQuery instanceof WP_Error ) {
			throw new Exception( 'Database error' );
		}

		$total = ! empty( $totalQuery[0]['total'] ) ? (int) $totalQuery[0]['total'] : 0;

		return apply_filters( 'order_messenger/message_repository/order_total_messages', $total, $orderId );
	}

	/**
	 * Get user order messages
	 *
	 * @param  int  $userId
	 * @param  int  $offset
	 * @param  int  $limit
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getUserOrderMessages( $userId, $offset = 0, $limit = null ) {

		$limit = $limit ? $limit : 10;

		$enabledTypes = Config::getEnabledMessageTypes();

		$messages = $this->database->getResults( $this->database->prepare( 'SELECT * FROM ' . OrderMessagesTable::getTableName() . ' WHERE id IN ( SELECT MAX(id) FROM  ' . OrderMessagesTable::getTableName() . ' WHERE user_id = %d AND type IN (' . implode( ',',
				$enabledTypes ) . ') group by order_id ) ORDER BY `date_sent` DESC LIMIT %d OFFSET %d ', $userId,
			$limit, $offset ), ARRAY_A );

		if ( $messages instanceof WP_Error ) {
			throw new Exception( 'Messages error' );
		}

		$messages = array_map( array( $this, 'rawMessageToInstance' ), $messages );

		return $messages;
	}

	public function getUserOrderDialogsCount( $userId ) {

		$enabledTypes = Config::getEnabledMessageTypes();

		$messages = $this->database->getRow( $this->database->prepare( 'SELECT COUNT(*) as total FROM ' . OrderMessagesTable::getTableName() . ' WHERE id IN ( SELECT MAX(id) FROM  ' . OrderMessagesTable::getTableName() . ' WHERE user_id = %d AND type IN (' . implode( ',',
				$enabledTypes ) . ') group by order_id )', $userId ), ARRAY_A );

		if ( $messages instanceof WP_Error ) {
			throw new Exception( 'Messages error' );
		}

		return isset( $messages['total'] ) ? intval( $messages['total'] ) : 0;
	}

	public function getById( $messageId ) {

		$message = $this->database->getRow( $this->database->prepare( 'SELECT * FROM ' . OrderMessagesTable::getTableName() . ' WHERE id = %d',
			$messageId ), ARRAY_A );

		if ( $message ) {
			return $this->rawMessageToInstance( $message );
		}

		return null;
	}

	/**
	 * Convert raw message to instance
	 *
	 * @param  array  $rawMessage
	 *
	 * @return Message
	 */
	public function rawMessageToInstance( array $rawMessage ) {
		$message    = (string) $rawMessage['message'];
		$id         = (int) $rawMessage['id'];
		$orderId    = (int) $rawMessage['order_id'];
		$userId     = (int) $rawMessage['user_id'];
		$senderId   = (int) $rawMessage['sender_id'];
		$type       = (int) $rawMessage['type'];
		$isNotified = (bool) $rawMessage['is_notified'];

		try {
			$dateSent = $rawMessage['date_sent'] ? new DateTime( $rawMessage['date_sent'] ) : new DateTime();
		} catch ( Exception $exception ) {
			$dateSent = new DateTime();
		}

		try {
			$dateRead = $rawMessage['date_read'] ? new DateTime( $rawMessage['date_read'] ) : null;
		} catch ( Exception $exception ) {
			$dateRead = new DateTime();
		}

		$type = MessageType::fromInt( $type );

		$attachment = $rawMessage['attachment_id'] ? new MessageAttachment( $rawMessage['attachment_id'] ) : null;
		$data       = json_decode( $rawMessage['data'] );
		$data       = $data ? (array) $data : array();

		return new Message( $message, $orderId, $userId, $senderId, $type, $attachment, $dateSent, $isNotified,
			$dateRead, $data, $id );
	}
}
