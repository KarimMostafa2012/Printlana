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

/**
 * Override the default Dokan filter to show only sub-orders to customers
 * This removes the Dokan filter that shows only parent orders (post_parent = 0)
 * and replaces it with our filter that shows only sub-orders (post_parent != 0)
 */
function printlana_show_only_sub_orders_to_customers() {
    // Remove Dokan's default filter that shows only parent orders
    remove_filter( 'woocommerce_my_account_my_orders_query', 'dokan_get_customer_main_order' );

    // Add our custom filter to show only sub-orders
    add_filter( 'woocommerce_my_account_my_orders_query', 'printlana_get_customer_sub_orders', 10 );
}
add_action( 'init', 'printlana_show_only_sub_orders_to_customers', 20 );

/**
 * Filter customer orders to show only sub-orders (exclude parent orders)
 *
 * @param array $customer_orders WooCommerce customer orders query arguments
 * @return array Modified query arguments
 */
function printlana_get_customer_sub_orders( $customer_orders ) {
    // Only show orders that have a parent (sub-orders)
    // Exclude orders where post_parent = 0 (parent orders)
    $customer_orders['post_parent__not_in'] = array( 0 );

    return $customer_orders;
}
