<?php

namespace WPS\WPS_Bidouille;

class Suggestions {

	use Singleton;

	protected function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_page', array( __CLASS__, 'admin_page' ) );
	}

	/**
	 * Admin menu WPServeur
	 */
	public static function admin_menu() {

		$pf = Helpers::wps_ip_check_return_pf();
		if ( empty( $pf ) ) {
			return false;
		}

		add_submenu_page(
			'wps-bidouille',
			__( 'Our suggestions', 'wps-bidouille' ),
			__( 'Our suggestions', 'wps-bidouille' ),
			'manage_options',
			'wps-bidouille-suggestions',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function admin_page() {
		include( WPS_BIDOUILLE_DIR . '/admin_page/suggestions.php' );
	}
}