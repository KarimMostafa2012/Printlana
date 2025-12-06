<?php
/**
 * Plugin Name: Defer third-party textdomain loading (WP 6.7 compatibility)
 * Description: Forces specific plugin textdomains to load on init to avoid WP 6.7 _load_textdomain_just_in_time notices.
 */

add_action('init', function () {
    // Woo Discount Rules
    if (function_exists('load_plugin_textdomain')) {
        load_plugin_textdomain(
            'woo-discount-rules',
            false,
            'woo-discount-rules/languages'
        );
        // Woo Min/Max Quantity Step Control Single
        load_plugin_textdomain(
            'woo-min-max-quantity-step-control-single',
            false,
            'woo-min-max-quantity-step-control-single/languages'
        );
        // All-in-One WP Migration
        load_plugin_textdomain(
            'all-in-one-wp-migration',
            false,
            'all-in-one-wp-migration/languages'
        );
    }
}, 1);
