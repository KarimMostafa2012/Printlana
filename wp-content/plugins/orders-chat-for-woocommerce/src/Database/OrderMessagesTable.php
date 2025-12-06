<?php namespace U2Code\OrderMessenger\Database;

use U2Code\OrderMessenger\Entity\Message;

class OrderMessagesTable {

	const TABLE_NAME = 'wc_order_messages';

	public static function create() {
		global $wpdb;

		$sql = 'CREATE TABLE IF NOT EXISTS ' . self::getTableName() . ' (
			id BIGINT(10) NOT NULL AUTO_INCREMENT,
			order_id BIGINT(10) NOT NULL,
			user_id BIGINT(10) NOT NULL,
			sender_id BIGINT(10) NOT NULL,
			attachment_id BIGINT(10),
			message TEXT(3005),
			is_notified TINYINT(1) NOT NULL DEFAULT 0,
			type TINYINT(1) NOT NULL DEFAULT 0,
			date_sent DATETIME NOT NULL,
			date_read DATETIME,
			data TEXT(' . Message::MAX_LENGTH . '),
			PRIMARY KEY  (`id`)
		) ' . $wpdb->get_charset_collate() . ";\n";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	public static function delete() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_order_messages" ) );
	}

	public static function getTableName() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}
}
