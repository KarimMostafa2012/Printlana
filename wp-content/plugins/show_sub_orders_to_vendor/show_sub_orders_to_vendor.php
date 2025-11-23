<?php
/**
 * Plugin Name: Show Only Sub Orders to Vendor (Printlana)
 * Description: Filters Dokan vendor orders to show ONLY sub-orders. Logs output order results (clean arrays).
 * Version: 2.1
 * Author: Printlana
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Printlana_Show_Suborders_Only {

    public function __construct() {

        // 1) Modify Dokan vendor order query: ONLY sub-orders
        add_filter( 'dokan_get_vendor_orders_args', [ $this, 'filter_vendor_orders' ], 999, 2 );

        // 2) AFTER Dokan has fetched orders, log the actual results (array of orders or IDs)
        // This filter exists in Dokan and is used when building vendor order list.
        add_filter( 'dokan_get_seller_orders', [ $this, 'log_vendor_orders' ], 10, 2 );
    }

    /**
     * Simple debug logger
     */
    private function log( $label, $value = null ) {
        if ( is_null( $value ) ) {
            error_log( '[ShowSubOrders] ' . $label );
        } else {
            error_log( '[ShowSubOrders] ' . $label . ' => ' . print_r( $value, true ) );
        }
    }

    /**
     * Filter Dokan vendor order args to show ONLY sub-orders
     *
     * @param array $args
     * @param mixed $second_param
     * @return array
     */
    public function filter_vendor_orders( $args, $second_param ) {

        // OPTIONAL: uncomment if you want to see the query once
        $this->log( 'Query Args BEFORE sub-order filter', $args );

        // Use WC_Order_Query param "parent_exclude" => [0] to exclude parent orders
        if ( empty( $args['parent_exclude'] ) || ! is_array( $args['parent_exclude'] ) ) {
            $args['parent_exclude'] = [ 0 ];
        } else {
            $args['parent_exclude'][] = 0;
            $args['parent_exclude']    = array_values( array_unique( array_map( 'intval', $args['parent_exclude'] ) ) );
        }

        // OPTIONAL: uncomment to see the modified query
        // $this->log( 'Query Args AFTER sub-order filter', $args );

        return $args;
    }

    /**
     * Log sub-order results (NOT the query) as clean readable arrays.
     *
     * @param array $orders    Array of WC_Order objects OR order IDs
     * @param int   $seller_id Vendor ID
     * @return array
     */
    public function log_vendor_orders( $orders, $seller_id ) {

        $formatted = [];

        foreach ( $orders as $order ) {

            // Dokan sometimes returns IDs instead of objects, hydrate if needed
            if ( is_numeric( $order ) ) {
                $order = wc_get_order( $order );
            }

            if ( ! $order instanceof WC_Order ) {
                continue;
            }

            $formatted[] = [
                'id'        => $order->get_id(),
                'parent_id' => $order->get_parent_id(),
                'vendor_id' => (int) $order->get_meta( '_dokan_vendor_id' ),
                'status'    => $order->get_status(),
                'total'     => $order->get_total(),
                'items'     => wp_list_pluck( $order->get_items(), 'name' ),
            ];
        }

        $this->log( 'Sub-order results (vendor ' . $seller_id . ')', $formatted );

        // IMPORTANT: return orders so Dokan can continue using them
        return $orders;
    }
}

new Printlana_Show_Suborders_Only();
