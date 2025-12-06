<?php namespace U2Code\OrderMessenger\Core;

use Exception;
use U2Code\OrderMessenger\Database\Database;
use U2Code\OrderMessenger\Repository\MessageRepository;
use U2Code\OrderMessenger\Settings\Settings;

class ServiceContainer {

	private $services = array();

	private static $instance;

	private function __construct() {
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function add( $name, $instance ) {

		$instance = apply_filters( 'order_messenger/container/service_instance', $instance, $name );

		$this->services[ $name ] = $instance;
	}

	/**
	 * Get service
	 *
	 * @param $name
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get( $name ) {
		if ( ! empty( $this->services[ $name ] ) ) {
			return $this->services[ $name ];
		}

		throw new Exception( 'Undefined service' );
	}

	/**
	 * Get fileManager
	 *
	 * @return FileManager
	 */
	public function getFileManager() {
		try {
			return $this->get( 'fileManager' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get MessageRepository
	 *
	 * @return MessageRepository
	 */
	public function getMessageRepository() {
		try {
			return $this->get( 'messageRepository' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get Settings
	 *
	 * @return Settings
	 */
	public function getSettings() {
		try {
			return $this->get( 'settings' );
		} catch ( Exception $e ) {
			return null;
		}
	}
	/**
	 * Get Database
	 *
	 * @return Database
	 */
	public function getDatabase() {
		try {
			return $this->get( 'database' );
		} catch ( Exception $e ) {
			return null;
		}
	}

}
