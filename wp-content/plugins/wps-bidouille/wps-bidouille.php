<?php
/*
Plugin Name: WPS Bidouille
Description: WPS Bidouille fournit des informations sur son site WordPress et contient des outils d'optimisation.
Donate link: https://www.paypal.me/donateWPServeur
Author: WPServeur, NicolasKulka, Benoti, wpformation
Author URI: https://wpserveur.net
Version: 1.33.3
Requires at least: 4.2
Tested up to: 6.8
Domain Path: languages
Text Domain: wps-bidouille
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'WPS_BIDOUILLE_VERSION', '1.33.3' );
define( 'WPS_BIDOUILLE_FOLDER', 'wps-bidouille' );
define( 'WPS_BIDOUILLE_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPS_BIDOUILLE_API_URL', 'https://www.wpserveur.net/wp-json/' );
define( 'WPS_BIDOUILLE_API_CAT_PLUGINS', 102 );
define( 'WPS_BIDOUILLE_API_CAT_THEMES', 101 );

define( 'WPS_BIDOUILLE_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_BIDOUILLE_DIR', plugin_dir_path( __FILE__ ) );

require_once WPS_BIDOUILLE_DIR . 'autoload.php';

register_activation_hook( __FILE__, array( '\WPS\WPS_Bidouille\Plugin', 'install' ) );
register_deactivation_hook( __FILE__, array( '\WPS\WPS_Bidouille\Plugin', 'uninstall' ) );

add_action( 'plugins_loaded', 'plugins_loaded_wps_bidouille_plugin' );
function plugins_loaded_wps_bidouille_plugin() {
	\WPS\WPS_Bidouille\Plugin::get_instance();
	\WPS\WPS_Bidouille\DB_Prefix::get_instance();
	\WPS\WPS_Bidouille\Helpers::get_instance();

	$pf                      = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
	$wps_check_temporary_url = \WPS\WPS_Bidouille\Helpers::wps_check_temporary_url();
	if ( ! empty( $pf ) && empty( $wps_check_temporary_url ) ) {
		\WPS\WPS_Bidouille\RemoveFromCache::get_instance();
	}
	$is_user_white_label = \WPS\WPS_Bidouille\Helpers::is_user_white_label();
	if ( ! $is_user_white_label && ! empty( $pf ) ) {
		\WPS\WPS_Bidouille\WhiteLabel::get_instance();
	}
	//new WPS_BIDOUILLE_SUGGESTIONS;
	\WPS\WPS_Bidouille\Tools::get_instance();

	if ( ! empty( $pf ) && current_user_can( 'manage_options' ) ) {
		\WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_instance();
	}

	load_plugin_textdomain( 'wps-bidouille', false, basename( rtrim( dirname( __FILE__ ), '/' ) ) . '/languages' );
}

if ( function_exists('rocket_after_update_single_options') ) {
	add_action( 'update_option_list_post_without_cache', 'rocket_after_update_single_options', 10, 2 );
}

if ( ! function_exists( 'wpserveur_flush_cache' ) ) {
	function wpserveur_flush_cache( $old_value, $value ) {
		if ( is_plugin_active_for_network( 'wp-rocket/wp-rocket.php' ) || is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
			if ( ! is_array( $value ) ) {
				return false;
			}

			if ( class_exists( 'VarnishPurger' ) ) {
				$varnish = new VarnishPurger;
				$varnish->executePurge();
			}

			foreach ( $value as $p_id ) {
				rocket_clean_post( (int) $p_id );
			}
			set_transient( 'rocket_clear_cache', 'post', HOUR_IN_SECONDS );
		}
	}

	add_action( 'update_option_list_post_without_cache', 'wpserveur_flush_cache', 10, 2 );
}

if ( ! function_exists( 'rocket_exlude_wps_bidouille_page' ) ) {
	/**
	 * Exclude WPS_BIDOUILLE from caching WP Rocket
	 */
	function rocket_exlude_wps_bidouille_page( $urls ) {
		$list_post_without_cache = get_option( 'list_post_without_cache' );
		if ( empty( $list_post_without_cache ) ) {
			return $urls;
		}

		foreach ( $list_post_without_cache as $post_id ) {
			$urls[] = rocket_clean_exclude_file( get_permalink( $post_id ) );
		}

		return $urls;
	}

	add_filter( 'rocket_cache_reject_uri', 'rocket_exlude_wps_bidouille_page' );
}