<?php
/**
 * Plugin Name: Customer Show Sub Orders Only
 * Description: Filters customer "My Account" orders page to show only sub-orders and hide parent orders
 * Version: 1.0
 * Author: Printlana
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enable debugging mode - set to false to disable logs
define( 'PRINTLANA_SUB_ORDERS_DEBUG', true );

/**
 * Helper function to log debug messages
 */
function printlana_sub_orders_log( $message, $data = null ) {
    if ( ! defined( 'PRINTLANA_SUB_ORDERS_DEBUG' ) || ! PRINTLANA_SUB_ORDERS_DEBUG ) {
        return;
    }

    $log_message = '[PRINTLANA SUB ORDERS] ' . $message;

    if ( $data !== null ) {
        $log_message .= ' | Data: ' . print_r( $data, true );
    }

    error_log( $log_message );
}

/**
 * Override the default Dokan filter to show only sub-orders to customers
 * This removes the Dokan filter that shows only parent orders (post_parent = 0)
 * and replaces it with our filter that shows only sub-orders (post_parent != 0)
 */
function printlana_show_only_sub_orders_to_customers() {
    printlana_sub_orders_log( 'Plugin initializing - setting up filters' );

    // Check if Dokan filter exists before removing
    $has_dokan_filter = has_filter( 'woocommerce_my_account_my_orders_query', 'dokan_get_customer_main_order' );
    printlana_sub_orders_log( 'Dokan filter exists?', $has_dokan_filter ? 'Yes' : 'No' );

    // Remove Dokan's default filter that shows only parent orders
    $removed = remove_filter( 'woocommerce_my_account_my_orders_query', 'dokan_get_customer_main_order' );
    printlana_sub_orders_log( 'Dokan filter removed?', $removed ? 'Success' : 'Failed or did not exist' );

    // Add our custom filter to show only sub-orders
    add_filter( 'woocommerce_my_account_my_orders_query', 'printlana_get_customer_sub_orders', 10 );
    printlana_sub_orders_log( 'Custom filter added successfully' );
}
add_action( 'init', 'printlana_show_only_sub_orders_to_customers', 20 );

/**
 * Filter customer orders to show only sub-orders (exclude parent orders)
 *
 * @param array $customer_orders WooCommerce customer orders query arguments
 * @return array Modified query arguments
 */
function printlana_get_customer_sub_orders( $customer_orders ) {
    printlana_sub_orders_log( 'Filter triggered - original query args', $customer_orders );

    // Only show orders that have a parent (sub-orders)
    // Exclude orders where post_parent = 0 (parent orders)
    $customer_orders['post_parent__not_in'] = array( 0 );

    printlana_sub_orders_log( 'Filter applied - modified query args', $customer_orders );

    return $customer_orders;
}

/**
 * Log the actual orders being displayed (for debugging)
 */
function printlana_log_customer_orders( $query ) {
    // Only log on My Account orders page
    if ( ! is_account_page() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->get( 'post_type' ) !== 'shop_order' ) {
        return;
    }

    printlana_sub_orders_log( 'Orders query executed', array(
        'post_type' => $query->get( 'post_type' ),
        'post_parent' => $query->get( 'post_parent' ),
        'post_parent__not_in' => $query->get( 'post_parent__not_in' ),
        'customer_id' => $query->get( 'customer_id' ),
        'found_posts' => $query->found_posts,
    ) );

    // Log individual orders returned
    if ( ! empty( $query->posts ) ) {
        $orders_info = array();
        foreach ( $query->posts as $post ) {
            $order = wc_get_order( $post->ID );
            if ( $order ) {
                $orders_info[] = array(
                    'order_id' => $order->get_id(),
                    'parent_id' => $order->get_parent_id(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'is_sub_order' => $order->get_parent_id() > 0 ? 'Yes' : 'No',
                );
            }
        }
        printlana_sub_orders_log( 'Orders returned (' . count( $orders_info ) . ' total)', $orders_info );
    } else {
        printlana_sub_orders_log( 'No orders found in query results' );
    }
}
add_action( 'pre_get_posts', 'printlana_log_customer_orders', 999 );

/**
 * Add admin notice to show debugging is active
 */
function printlana_debug_admin_notice() {
    if ( ! defined( 'PRINTLANA_SUB_ORDERS_DEBUG' ) || ! PRINTLANA_SUB_ORDERS_DEBUG ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>Printlana Sub Orders Debug Mode Active:</strong> Check your debug.log file for detailed logging. Logs are prefixed with [PRINTLANA SUB ORDERS]</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'printlana_debug_admin_notice' );
