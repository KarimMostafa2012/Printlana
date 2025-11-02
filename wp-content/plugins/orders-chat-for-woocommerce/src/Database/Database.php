<?php namespace U2Code\OrderMessenger\Database;

class Database {

	/**
	 * Instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * WPDB
	 *
	 * @var mixed
	 */
	private $wpdatabase;

	private function __construct() {
		$this->wpdatabase = $GLOBALS['wpdb'];

		global $wpdb;
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function query() {
		return $this->wpdatabase->query( ...func_get_args() );
	}

	public function getResults() {
		return $this->wpdatabase->get_results( ...func_get_args() );
	}

	public function getColumn() {
		return $this->wpdatabase->get_col( ...func_get_args() );
	}

	public function prepare() {
		return $this->wpdatabase->prepare( ...func_get_args() );
	}

	public function update() {
		return $this->wpdatabase->update( ...func_get_args() );
	}

	public function delete() {
		return $this->wpdatabase->delete( ...func_get_args() );
	}

	public function getRow() {
		return $this->wpdatabase->get_row( ...func_get_args() );
	}
}
