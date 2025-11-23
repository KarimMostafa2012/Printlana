<?php
/**
 * Plugin Name: Show Only Sub Orders to Vendor (Printlana)
 * Description: Filters Dokan vendor orders to show ONLY sub-orders. Adds debug logging.
 * Version: 1.0
 * Author: Printlana
 */

if (!defined('ABSPATH'))
    exit; // Prevent direct access

class Printlana_Show_Suborders_Only
{

    public function __construct()
    {
        // Filter the vendor order query arguments
        add_filter('dokan_get_vendor_orders_args', [$this, 'filter_vendor_orders'], 999, 2);
    }

    /**
     * Debug logger helper
     */
    private function log($msg)
    {
        if (is_array($msg) || is_object($msg)) {
            error_log('[ShowSubOrders] ' . print_r($msg, true));
        } else {
            error_log('[ShowSubOrders] ' . $msg);
        }
    }

    /**
     * Filter orders shown to vendor -> Only sub-orders (orders that have a parent)
     */
    public function filter_vendor_orders($args, $vendor_id)
    {

        // Log original args
        $this->log('Original Query Args:');
        $this->log($args);
        $this->log('Vendor ID: ' . $vendor_id);

        // Remove any default Dokan filters that would show parent orders
        unset($args['seller_id']);
        unset($args['author']);

        // Force ONLY orders that are children
        $args['post_parent__not_in'] = [0];  // Exclude parent orders
        $args['post_parent__not_in'][] = 0;    // Double protect
        $args['post_parent__not_in'] = array_unique($args['post_parent__not_in']);

        // The magic line: ONLY return orders where `_dokan_vendor_id` matches this vendor
        $args['meta_query'][] = [
            'key' => '_dokan_vendor_id',
            'value' => (int) $vendor_id,
            'compare' => '=',
            'type' => 'NUMERIC'
        ];

        // Log modified args
        $this->log('Modified Query Args (sub-orders only):');
        $this->log($args);

        return $args;
    }

}

new Printlana_Show_Suborders_Only();
