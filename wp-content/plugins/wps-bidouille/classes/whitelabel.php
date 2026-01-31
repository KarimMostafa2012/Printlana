<?php

namespace WPS\WPS_Bidouille;

class WhiteLabel {

	use Singleton;

	protected function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'wp_ajax_wps_get_users', array( __CLASS__, 'wps_get_users' ) );

		add_action( 'admin_init', array( __CLASS__, 'register_wps_tools_settings' ) );

		add_filter( 'pre_update_option', array(
			__CLASS__,
			'update_option_select2_wps_users'
		), 99, 3 );
	}

	/**
	 * Register a custom menu page.
	 */
	public static function admin_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		add_submenu_page(
			'wps-bidouille',
			__( 'White Label', 'wps-bidouille' ),
			__( 'White Label', 'wps-bidouille' ),
			'manage_options',
			'wps-bidouille-white-label',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function register_wps_tools_settings() {
		register_setting( 'wps-bidouille-settings', 'wps_white_list' );
		register_setting( 'wps-bidouille-settings', 'select2_wps_users' );
	}

	public static function admin_page() {
		include( WPS_BIDOUILLE_DIR . '/admin_page/white_label.php' );
	}

	public static function wps_get_users() {
		$results    = array();
		$user_query = new \WP_User_Query(
			array(
				'search'         => '*' . sanitize_text_field( $_GET['q'] ) . '*',
				'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'ID' ),
				'number'         => 10,
				'exclude'        => array( get_current_user_id() )
			)
		);

		$users = $user_query->get_results();
		foreach ( $users as $user ) :
			$results[] = array(
				'id'   => $user->ID,
				'text' => $user->display_name . ' (' . $user->user_email . ')'
			);
		endforeach;

		echo json_encode( $results );
		die;
	}

	/**
	 * @param $value
	 * @param $option
	 * @param $old_value
	 *
	 * @return array
	 */
	public static function update_option_select2_wps_users( $value, $option, $old_value ) {
		if ( 'select2_wps_users' !== $option ) {
			return $value;
		}

		if ( true == get_option( 'wps_white_list' ) ) {
			if ( is_array( $value ) ) {
				$value = array_unique( array_merge( $value, array( get_current_user_id() ) ), SORT_REGULAR );
			} else {
				$value = array( get_current_user_id() );
			}
		}

		return $value;
	}
}
