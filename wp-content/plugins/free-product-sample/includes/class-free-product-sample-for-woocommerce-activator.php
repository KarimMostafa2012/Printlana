<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    DSFPS_Free_Product_Sample_Pro
 * @subpackage DSFPS_Free_Product_Sample_Pro/includes
 * @author     Multidots <inquiry@multidots.in>
 */
if ( !class_exists( 'DSFPS_Free_Product_Sample_Pro_Activator' ) ) {
	class DSFPS_Free_Product_Sample_Pro_Activator {

		/**
		 * Short Description. (use period)
		 *
		 * Long Description.
		 *
		 * @since    1.0.0
		 */
		public static function activate() {
			global $jal_db_version;
			$jal_db_version = '1.0.0';
			if (  in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ||  is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
				set_transient( '_welcome_screen_activation_redirect_ds_product_sample', true, 30 );
			} else {
				wp_die( "<strong>Advanced Product Sample for WooCommerce</strong> plugin requires <strong>WooCommerce</strong>. Return to <a href='" . esc_url( get_admin_url( null, 'plugins.php' ) ) . "'>Plugins page</a>." );
			}
			add_option( 'jal_db_version', $jal_db_version );
		}
	}
}