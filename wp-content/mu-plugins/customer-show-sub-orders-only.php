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
    // Exclude orders where parent = 0 (parent orders)
    // WooCommerce uses 'parent_exclude' parameter for this
    $customer_orders['parent_exclude'] = array( 0 );

    printlana_sub_orders_log( 'Filter applied - modified query args', $customer_orders );

    return $customer_orders;
}

/**
 * Log the actual orders being displayed after WooCommerce query
 */
function printlana_log_wc_orders_result( $orders, $query_vars ) {
    // Only log if we have the customer parameter (indicates My Account orders)
    if ( ! isset( $query_vars['customer'] ) ) {
        return $orders;
    }

    printlana_sub_orders_log( 'WooCommerce orders query executed', array(
        'customer' => $query_vars['customer'],
        'parent_exclude' => isset( $query_vars['parent_exclude'] ) ? $query_vars['parent_exclude'] : 'Not set',
        'total_orders' => is_object( $orders ) && isset( $orders->total ) ? $orders->total : count( $orders ),
    ) );

    // Log individual orders
    $orders_list = is_object( $orders ) && isset( $orders->orders ) ? $orders->orders : $orders;

    if ( ! empty( $orders_list ) && is_array( $orders_list ) ) {
        $orders_info = array();
        foreach ( $orders_list as $order ) {
            if ( is_a( $order, 'WC_Order' ) ) {
                $orders_info[] = array(
                    'order_id' => $order->get_id(),
                    'parent_id' => $order->get_parent_id(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'is_sub_order' => $order->get_parent_id() > 0 ? 'YES' : 'NO (PARENT ORDER)',
                );
            }
        }
        printlana_sub_orders_log( 'Orders returned (' . count( $orders_info ) . ' total)', $orders_info );
    } else {
        printlana_sub_orders_log( 'No orders found - customer may have no sub-orders' );
    }

    return $orders;
}
add_filter( 'woocommerce_orders_get_orders_query', 'printlana_log_wc_orders_result', 999, 2 );

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
