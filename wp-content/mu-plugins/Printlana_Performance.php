<?php
/**
 * Plugin Name: Printlana Performance
 * Description: Kills expensive AJAX (cart fragments, heartbeat), trims assets, and fixes noisy notices.
 * Author: Printlana
 */

/* ---------------------------
   1) CART FRAGMENTS (guests)
-----------------------------*/

// Disable Woo cart fragments for guests
add_filter('woocommerce_cart_fragments_enabled', function ($enabled) {
    return is_user_logged_in(); // only for logged-in users
}, 10);

// Block Elementor’s menu-cart fragments for guests (even if registered elsewhere)
add_action('init', function () {
    if ( is_user_logged_in() ) return;

    // Unhook if present
    remove_action('wp_ajax_nopriv_elementor_menu_cart_fragments', 'elementor_menu_cart_fragments');
    remove_action('wp_ajax_elementor_menu_cart_fragments',      'elementor_menu_cart_fragments');
}, 100);

// Hard block the request early (cover unknown callbacks)
add_action('init', function () {
    if ( defined('DOING_AJAX') && DOING_AJAX && ( $_REQUEST['action'] ?? '' ) === 'elementor_menu_cart_fragments' && ! is_user_logged_in() ) {
        wp_send_json_success(['fragments' => []]); // minimal response, no DB work
    }
}, 0);


/* ---------------------------
   2) HEARTBEAT TUNING
-----------------------------*/

// Frontend: remove Heartbeat entirely
add_action('init', function () {
    if ( ! is_admin() ) {
        wp_deregister_script('heartbeat');
    }
}, 1);

// Admin: throttle heavily except on edit screens
add_filter('heartbeat_settings', function ($settings) {
    if ( ! is_admin() ) return $settings;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_edit_screen = $screen && in_array($screen->id, ['post','page','product','elementor_library'], true);

    // 60s on editors, 600s elsewhere in wp-admin
    $settings['interval'] = $is_edit_screen ? 60 : 600;
    return $settings;
}, 10, 1);

// If Heartbeat hits without auth (your logs showed role=guest), bail instantly
add_action('wp_ajax_nopriv_heartbeat', function () {
    wp_send_json_success([]); // zero work for anonymous heartbeat
}, 0);


/* --------------------------------------------
   3) TRANSLATION “called too early” notices
----------------------------------------------*/
// If a plugin exposes its textdomain loader, re-hook it to 'init'
add_action('plugins_loaded', function () {
    if ( function_exists('woo_discount_rules_load_textdomain') ) {
        remove_action('plugins_loaded', 'woo_discount_rules_load_textdomain');
        add_action('init', 'woo_discount_rules_load_textdomain');
    }
    // Add similar blocks for other offenders if they expose functions
}, 20);


/* ---------------------------------
   4) TRIM UNNEEDED FRONT-END ASSETS
-----------------------------------*/
add_action('wp_enqueue_scripts', function () {

    // Elementor animations CSS (if you don't use entrance animations)
    wp_dequeue_style('e-animations');

    // Swiper: keep only where you actually use sliders (adjust allowlist if needed)
    if ( ! is_front_page() && ! is_page(array('home','landing')) ) {
        wp_dequeue_style('swiper');
        wp_dequeue_script('swiper');
        wp_dequeue_style('e-swiper');
    }

    // Font Awesome: remove if Elementor "Inline Font Icons" is enabled
    wp_dequeue_style('font-awesome');
    wp_dequeue_style('fontawesome');
    wp_dequeue_style('elementor-icons-fa-solid');
    wp_dequeue_style('elementor-icons-fa-regular');
    wp_dequeue_style('elementor-icons-fa-brands');
    wp_dequeue_style('elementor-icons-shared-0');
    wp_deregister_style('font-awesome');
    wp_deregister_style('fontawesome');

    // Moment.js: keep only on pages that truly need it
    if ( ! ( is_cart() || is_checkout() || is_account_page() ) ) {
        wp_dequeue_script('moment');
        wp_deregister_script('moment');
        wp_dequeue_script('moment-timezone');
        wp_deregister_script('moment-timezone');
    }

    // jQuery Migrate: drop on frontend unless you see console errors
    // if ( ! is_admin() ) {
    //     wp_deregister_script('jquery-migrate');
    // }

}, 99);
