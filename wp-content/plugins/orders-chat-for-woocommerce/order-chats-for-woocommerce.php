<?php
	/**
	 *
	 * Plugin Name: Orders Chat for WooCommerce Premium
	 * Description:       Simple and robust method to be in touch with your customers.
	 * Requires Plugins:  woocommerce
	 * Version:           1.2.0
	 * Author:            u2Code
	 * Author URI:        https://u2code.com
	 * License:           GPL-2.0+
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 * Text Domain:       order-messenger-for-woocommerce
	 * Domain Path:       /languages
	 */
	
	use U2Code\OrderMessenger\OrderMessengerPlugin;

// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}
	
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	
	if ( function_exists( 'omfw_fs' ) ) {
		omfw_fs()->set_basename( false, __FILE__ );
	} else {
		
		if ( ! function_exists( 'omfw_fs' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'license.php';
		}
		
		call_user_func( function () {
			
			$main = new OrderMessengerPlugin( __FILE__ );
			
			register_activation_hook( __FILE__, array( $main, 'activate' ) );
			
			register_deactivation_hook( __FILE__, array( $main, 'deactivate' ) );
			
			register_uninstall_hook( __FILE__, array( OrderMessengerPlugin::class, 'uninstall' ) );
			
			$main->run();
		} );
	}
