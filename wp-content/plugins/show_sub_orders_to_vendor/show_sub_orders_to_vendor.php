<?php
/**
 * Plugin Name: Show Only Sub Orders to Vendor (Printlana)
 * Description: Filters Dokan vendor orders to show ONLY sub-orders based on product meta `_assigned_vendor_ids`.
 * Version: 3.0
 * Author: Printlana
 */

if (!defined('ABSPATH')) {
    exit;
}

class Printlana_Show_Suborders_Only
{

    public function __construct()
    {

        // 1) Modify Dokan vendor order query: ONLY sub-orders (no vendor meta constraint)
        add_filter('dokan_get_vendor_orders_args', [$this, 'filter_vendor_orders'], 999, 2);

        // 2) After Dokan gets vendor orders, filter them by custom meta _assigned_vendor_ids
        add_filter('dokan_get_vendor_orders', [$this, 'filter_and_log_vendor_orders'], 10, 2);
    }

    /**
     * Simple debug logger
     */
    private function log($label, $value = null)
    {
        if (is_null($value)) {
            error_log('[ShowSubOrders] ' . $label);
        } else {
            error_log('[ShowSubOrders] ' . $label . ' => ' . print_r($value, true));
        }
    }

    /**
     * Filter Dokan vendor order args:
     * - Keep paging, ordering etc.
     * - Force ONLY sub-orders via parent_exclude => [0]
     * - Remove Dokan's meta_query on _dokan_vendor_id so we can apply our own logic.
     *
     * @param array $args
     * @param mixed $second_param
     * @return array
     */
    public function filter_vendor_orders($args, $second_param)
    {

        $this->log('Query Args BEFORE custom filter', $args);

        // Ensure orders must have a parent (i.e. sub-orders only)
        if (empty($args['parent_exclude']) || !is_array($args['parent_exclude'])) {
            $args['parent_exclude'] = [0];
        } else {
            $args['parent_exclude'][] = 0;
            $args['parent_exclude'] = array_values(array_unique(array_map('intval', $args['parent_exclude'])));
        }

        // Remove Dokan's default meta_query on _dokan_vendor_id
        // if (isset($args['meta_query'])) {
        //     $this->log('Original meta_query (will be unset)', $args['meta_query']);
        //     unset($args['meta_query']);
        // }

        $this->log('Query Args AFTER custom filter (sub-orders only, no vendor meta) => test: disabled', $args);

        return $args;
    }

    /**
     * After Dokan fetches vendor orders, filter them by _assigned_vendor_ids on products.
     *
     * @param array $orders Array of WC_Order objects OR IDs.
     * @param array $args   Query args (contains 'seller_id' = current vendor).
     * @return array Filtered orders.
     */
    public function filter_and_log_vendor_orders($orders, $args)
    {

        $vendor_id = isset($args['seller_id']) ? (int) $args['seller_id'] : get_current_user_id();

        $this->log('Raw orders count before _assigned_vendor_ids filter (vendor ' . $vendor_id . ') => test: disabled ', count($orders));

        $filtered_orders = [];

        // foreach ($orders as $order) {

        //     // Hydrate IDs into WC_Order objects if needed
        //     if (is_numeric($order)) {
        //         $order = wc_get_order($order);
        //     }

        //     if (!$order instanceof WC_Order) {
        //         continue;
        //     }

        //     if ($this->order_matches_assigned_vendor($order, $vendor_id)) {
        //         $filtered_orders[] = $order;
        //     }
        // }

        // // Build a snapshot for debugging
        // $snapshot = [];
        // foreach ($filtered_orders as $order) {

        //     $item_names = [];
        //     foreach ($order->get_items() as $item) {
        //         $item_names[] = $item->get_name();
        //     }

        //     $snapshot[] = [
        //         'id' => $order->get_id(),
        //         'parent_id' => $order->get_parent_id(),
        //         'status' => $order->get_status(),
        //         'total' => $order->get_total(),
        //         'items' => $item_names,
        //     ];
        // }

        // $this->log('Filtered sub-order results based on _assigned_vendor_ids (vendor ' . $vendor_id . ')', $snapshot);

        // return $filtered_orders;
        return $orders;
    }

    /**
     * Check if an order should be visible to a given vendor based on product meta _assigned_vendor_ids.
     *
     * @param WC_Order $order
     * @param int      $vendor_id
     * @return bool
     */
    // private function order_matches_assigned_vendor(WC_Order $order, $vendor_id)
    // {

    //     foreach ($order->get_items('line_item') as $item) {

    //         $product_id = $item->get_product_id();
    //         if (!$product_id) {
    //             continue;
    //         }

    //         $assigned = get_post_meta($product_id, '_assigned_vendor_ids', true);

    //         if (empty($assigned)) {
    //             continue;
    //         }

    //         // Normalize to array of integers (handles array or comma-separated string)
    //         if (is_string($assigned)) {
    //             // e.g. "12,34,56"
    //             $parts = preg_split('/[,\s]+/', $assigned, -1, PREG_SPLIT_NO_EMPTY);
    //             $assigned = array_map('intval', $parts);
    //         } elseif (is_array($assigned)) {
    //             $assigned = array_map('intval', $assigned);
    //         } else {
    //             // Single scalar value
    //             $assigned = [(int) $assigned];
    //         }

    //         if (in_array((int) $vendor_id, $assigned, true)) {
    //             // This order has at least one product assigned to this vendor
    //             return true;
    //         }
    //     }

    //     return false;
    // }

}

new Printlana_Show_Suborders_Only();
