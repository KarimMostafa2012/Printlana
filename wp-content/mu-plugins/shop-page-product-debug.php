<?php
/**
 * Plugin Name: Shop Page Product Debug
 * Description: Debug product queries on shop pages to see what's being filtered
 * Version: 1.0
 * Author: Printlana
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enable debugging mode - set to false to disable logs
define( 'PRINTLANA_SHOP_DEBUG', true );

class Printlana_Shop_Page_Debug {

    public function __construct() {
        // Hook into WooCommerce product query
        add_action( 'woocommerce_product_query', array( $this, 'log_product_query' ), 999 );

        // Hook into the main query
        add_action( 'pre_get_posts', array( $this, 'log_pre_get_posts' ), 999 );

        // Log SQL queries for products
        add_filter( 'posts_request', array( $this, 'log_sql_query' ), 999, 2 );

        // Log final products found
        add_action( 'woocommerce_after_shop_loop', array( $this, 'log_products_found' ), 1 );
    }

    /**
     * Log when WooCommerce product query hook fires
     */
    public function log_product_query( $query ) {
        if ( ! is_admin() && ( is_shop() || is_product_category() || is_product_tag() || is_search() ) ) {
            $this->log( 'woocommerce_product_query fired', array(
                'is_shop' => is_shop(),
                'is_product_category' => is_product_category(),
                'is_product_tag' => is_product_tag(),
                'is_search' => is_search(),
                'current_url' => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : 'N/A',
                'query_vars' => $query->query_vars,
            ) );
        }
    }

    /**
     * Log pre_get_posts for product queries
     */
    public function log_pre_get_posts( $query ) {
        if ( ! is_admin() && $query->is_main_query() && ( is_shop() || is_product_category() || is_product_tag() ) ) {
            $this->log( 'pre_get_posts for products', array(
                'post_type' => $query->get( 'post_type' ),
                'posts_per_page' => $query->get( 'posts_per_page' ),
                'paged' => $query->get( 'paged' ),
                'tax_query' => $query->get( 'tax_query' ),
                'meta_query' => $query->get( 'meta_query' ),
            ) );
        }
    }

    /**
     * Log the actual SQL query being executed
     */
    public function log_sql_query( $request, $query ) {
        if ( ! is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'product' ) {
            $this->log( 'SQL Query for products', array(
                'sql' => $request,
                'found_posts' => $query->found_posts,
            ) );
        }
        return $request;
    }

    /**
     * Log products that were actually found and displayed
     */
    public function log_products_found() {
        global $wp_query;

        $this->log( 'Products displayed on shop page', array(
            'found_posts' => $wp_query->found_posts,
            'post_count' => $wp_query->post_count,
            'max_num_pages' => $wp_query->max_num_pages,
            'current_page' => max( 1, get_query_var( 'paged' ) ),
        ) );

        // Log individual product IDs and titles
        if ( $wp_query->have_posts() ) {
            $products_info = array();
            foreach ( $wp_query->posts as $post ) {
                $products_info[] = array(
                    'ID' => $post->ID,
                    'title' => $post->post_title,
                    'status' => $post->post_status,
                );
            }
            $this->log( 'Individual products (' . count( $products_info ) . ' total)', $products_info );
        } else {
            $this->log( 'NO PRODUCTS FOUND!' );
        }
    }

    /**
     * Check Dokan SPMV settings
     */
    public function get_dokan_spmv_settings() {
        if ( function_exists( 'dokan_get_option' ) ) {
            $show_order = dokan_get_option( 'show_order', 'dokan_spmv', 'show_all' );
            $this->log( 'Dokan SPMV Settings', array(
                'show_order' => $show_order,
            ) );
        }
    }

    /**
     * Debug logger
     */
    private function log( $message, $data = null ) {
        if ( ! defined( 'PRINTLANA_SHOP_DEBUG' ) || ! PRINTLANA_SHOP_DEBUG ) {
            return;
        }

        $log_message = '[PRINTLANA SHOP DEBUG] ' . $message;

        if ( $data !== null ) {
            $log_message .= ' | Data: ' . print_r( $data, true );
        }

        error_log( $log_message );
    }
}

// Initialize the plugin
new Printlana_Shop_Page_Debug();

/**
 * Add admin notice to show debugging is active
 */
function printlana_shop_debug_notice() {
    if ( ! defined( 'PRINTLANA_SHOP_DEBUG' ) || ! PRINTLANA_SHOP_DEBUG ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>Printlana Shop Debug Mode Active:</strong> Check your debug.log file for detailed logging. Logs are prefixed with [PRINTLANA SHOP DEBUG]</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'printlana_shop_debug_notice' );
