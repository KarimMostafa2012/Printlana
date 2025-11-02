<?php
/**
Plugin Name: Product Video Gallery for Woocommerce
Description: Adding Product YouTube Video and Instantly transform the gallery on your WooCommerce Product page into a fully Responsive Stunning Carousel Slider.
Author: NikHiL Gadhiya
Author URI: https://www.technosoftwebs.com
Date: 15/06/2025
Version: 1.5.1.5
Text Domain: product-video-gallery-slider-for-woocommerce
Requires Plugins: woocommerce
WC requires at least: 2.3
WC tested up to: 9.9.3

@package WC_PRODUCT_VIDEO_GALLERY
-------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! defined( 'NICKX_PLUGIN_URL' ) ) {
    define( 'NICKX_PLUGIN_URL', 'https://www.technosoftwebs.com/' );
}
if ( ! defined( 'NICKX_PLUGIN_BASE' ) ) {
    define( 'NICKX_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'NICKX_PLUGIN_VERSION' ) ) {
    define( 'NICKX_PLUGIN_VERSION', '1.5.1.5' );
}
require_once __DIR__ . '/admin/js/nickx_live.php';

/**
	Activation
 */
function nickx_activation_hook_callback() {
	set_transient( 'nickx-plugin_setting_notice', true, 0 );
	if ( empty( get_option( 'nickx_slider_layout' ) ) ) {
		$nickx_set_settings = array( 
			'nickx_slider_layout' => 'horizontal',
			'nickx_slider_responsive' => 'no',
			'nickx_sliderautoplay' => 'no',
			'nickx_sliderfade' => 'no',
			'nickx_arrowinfinite' => 'yes',
			'nickx_arrowdisable' => 'yes',
			'nickx_arrow_thumb' => 'no',
			'nickx_hide_thumbnails' => 'no',
			'nickx_hide_thumbnail' => 'yes',
			'nickx_gallery_action' => 'no',
			'nickx_adaptive_height' => 'yes',
			'nickx_place_of_the_video' => 'no',
			'nickx_videoloop' => 'no',
			'nickx_vid_autoplay' => 'no',
			'nickx_template' => 'no',
			'nickx_controls' => 'yes',
			'nickx_show_lightbox' => 'yes',
			'nickx_show_zoom' => 'yes',
			'nickx_mobile_zoom' => 'no',
			'nickx_zoomlevel' => 1,
			'nickx_show_only_video' => 'no',
			'nickx_thumbnails_to_show' => 4,
			'nickx_arrowcolor' => '#000',
			'nickx_arrowbgcolor' => '',
			'nickx_thumnails_layout' => 'slider',
		);
		foreach ( $nickx_set_settings as $nickx_key => $nickx_set_setting ) {
			update_option( $nickx_key , $nickx_set_setting );
		}
	}
}

register_activation_hook( __FILE__, 'nickx_activation_hook_callback' );
if ( is_admin() ) {
    require_once __DIR__ . '/admin/class-setting.php';
	new WC_PRODUCT_VIDEO_GALLERY_SETTING();
} else {
    require_once __DIR__ . '/public/class-rendering.php';
}
function nickx_error_notice_callback_notice() {
	echo '<div class="error"><p><strong>Product Video Gallery for Woocommerce</strong> requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.</p></div>';
}
add_action( 'plugins_loaded', 'nickx_remove_woo_hooks' );
function nickx_remove_woo_hooks() {
	if ( is_admin() || current_user_can( 'dokan_edit_product' )) {
	    require_once __DIR__ . '/admin/class-video-field.php';
		new WC_PRODUCT_VIDEO_GALLERY_VIDEO_FIELD();
	}
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}
	if ( ( is_multisite() && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) || is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		if( !is_admin() ){
			$nickx_rendering_obj = new WC_PRODUCT_VIDEO_GALLERY_RENDERING();
			if ( get_option( 'nickx_gallery_action' ) != 'yes' ) {
				remove_action( 'woocommerce_before_single_product_summary_product_images', 'woocommerce_show_product_thumbnails', 20 );
				remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
				remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 10 );
				remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
				add_action( 'woocommerce_before_single_product_summary', array( $nickx_rendering_obj, 'nickx_show_product_image' ), 10 );
			}
			add_action( 'wp_head', array( $nickx_rendering_obj, 'nickx_get_nickx_video_schema' ) );
		}
	} else {
		add_action( 'admin_notices', 'nickx_error_notice_callback_notice' );
	}
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );