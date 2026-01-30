<?php

namespace WPML\TM\ATE\Download\OrphanPostCleaner;

class ProcessCounter {

	const OPTION_NAME = 'wpml_ate_download_process_counter';
	const EXPIRATION_SECONDS = 20;

	public function increment() {
		$data = $this->getData();

		if ( $this->isExpired( $data ) ) {
			$data = [ 'counter' => 0, 'timestamp' => time() ];
		}

		$data['counter']++;
		$data['timestamp'] = time();

		update_option( self::OPTION_NAME, $data, false );
	}

	public function decrement() {
		$data = $this->getData();

		if ( $this->isExpired( $data ) ) {
			delete_option( self::OPTION_NAME );
			return;
		}

		$data['counter'] = max( 0, $data['counter'] - 1 );

		if ( $data['counter'] > 0 ) {
			$data['timestamp'] = time();
			update_option( self::OPTION_NAME, $data, false );
		} else {
			delete_option( self::OPTION_NAME );
		}
	}

	/**
	 * @return int
	 */
	public function get() {
		$data = $this->getData();
		return $this->isExpired( $data ) ? 0 : $data['counter'];
	}

	/**
	 * @return array{counter: int, timestamp: int}
	 */
	private function getData() {
		$data = get_option( self::OPTION_NAME, null );
		if ( ! is_array( $data ) || ! isset( $data['counter'], $data['timestamp'] ) ) {
			return [ 'counter' => 0, 'timestamp' => 0 ];
		}
		return $data;
	}

	private function isExpired( array $data ) {
		return ( time() - $data['timestamp'] ) > self::EXPIRATION_SECONDS;
	}
}
