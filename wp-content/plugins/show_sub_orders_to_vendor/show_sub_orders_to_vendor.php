<?php
/**
 * Plugin Name: Show Only Sub Orders to Vendor (Printlana)
 * Description: Filters Dokan vendor orders to show ONLY sub-orders. Logs output order results (clean arrays).
 * Version: 2.0
 * Author: Printlana
 */

if (!defined('ABSPATH'))
    exit;

class Printlana_Show_Suborders_Only
{

    public function __construct()
    {
        add_filter('dokan_get_vendor_orders_args', [$this, 'filter_vendor_orders'], 999, 2);

        /**
         * AFTER Dokan fetches orders, log them (clean formatted arrays)
         * Hook: dokan_after_get_vendor_orders
         */
        add_action('dokan_after_get_vendor_orders', [$this, 'log_vendor_orders'], 10, 2);
    }

    /** Simple debug logger */
    private function log($label, $value = null)
    {
        $prefix = '[ShowSubOrders] ' . $label;
        if ($value === null) {
            error_log($prefix);
        } else {
            error_log($prefix . ' => ' . print_r($value, true));
        }
    }

    /**
     * Modify the query: show ONLY sub-orders
     */
    public function filter_vendor_orders($args, $second_param)
    {
        // add parent_exclude => [0] to exclude parent orders
        if (empty($args['parent_exclude']) || !is_array($args['parent_exclude'])) {
            $args['parent_exclude'] = [0];
        } else {
            $args['parent_exclude'][] = 0;
            $args['parent_exclude'] = array_values(array_unique(array_map('intval', $args['parent_exclude'])));
        }

        return $args;
    }

    /**
     * Log sub-order results (NOT the query) as clean readable arrays
     *
     * @param array  $orders      Array of WC_Order objects
     * @param string $context     Dokan context information (unused)
     */
    public function log_vendor_orders($orders, $context)
    {
        $formatted = [];

        foreach ($orders as $order) {
            $formatted[] = [
                'id' => $order->get_id(),
                'parent_id' => $order->get_parent_id(),
                'vendor_id' => (int) $order->get_meta('_dokan_vendor_id'),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'items' => wp_list_pluck($order->get_items(), 'name'), // only product names
            ];
        }

        $this->log('Sub-order results (Clean Array)', $formatted);
    }
}

new Printlana_Show_Suborders_Only();
