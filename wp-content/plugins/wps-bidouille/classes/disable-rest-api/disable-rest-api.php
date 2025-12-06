<?php
/**
 * Disable_REST_API class
 *
 * Most of the work is done in here
 */
class WPS_Disable_REST_API {

	/**
	 * Disable_REST_API constructor.
	 */
	public function __construct() {

		// This actually does everything in this plugin
		add_filter( 'rest_authentication_errors', array( &$this, 'you_shall_not_pass' ), 20 );

	}


	/**
	 * Checks for a current route being requested, and processes the allowlist
	 *
	 * @param $access
	 *
	 * @return WP_Error|null|boolean
	 */
	public function you_shall_not_pass( $access ) {
		$error_message = esc_html__( 'WPS Bidouille: Restrict access the REST API.' );

		if ( is_wp_error( $access ) ) {
			$access->add( 'rest_cannot_access', $error_message, array( 'status' => rest_authorization_required_code() ) );

			return $access;
		}

		return new WP_Error( 'rest_cannot_access', $error_message, array( 'status' => rest_authorization_required_code() ) );
	}
}
