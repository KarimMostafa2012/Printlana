<?php
/**
 * Plugin Name: Order Access Edit (Printlana)
 * Description: Extends Dokan order view permission using custom product meta `_assigned_vendor_ids`.
 * Version: 1.0
 * Author: Printlana
 */

if (!defined('ABSPATH')) {
    exit;
}

class Printlana_Order_Access_Edit
{

    public function __construct()
    {
        /**
         * Dokan helper dokan_is_seller_has_order( $seller_id, $order_id )
         * We hook into its filter so we can ADD access when our custom meta matches.
         *
         * Expected filter signature in Dokan:
         *   apply_filters( 'dokan_is_seller_has_order', $has_access, $seller_id, $order_id );
         */
        add_filter('dokan_is_seller_has_order', [$this, 'maybe_allow_assigned_vendor'], 10, 3);
    }

    /**
     * Simple debug logger
     */
    private function log($label, $value = null)
    {
        if (is_null($value)) {
            error_log('[OrderAccessEdit] ' . $label);
        } else {
            error_log('[OrderAccessEdit] ' . $label . ' => ' . print_r($value, true));
        }
    }

    /**
     * Extend Dokan "has order" permission with `_assigned_vendor_ids` meta.
     *
     * @param bool $has_access  Result from Dokan's own logic.
     * @param int  $seller_id   Vendor user ID.
     * @param int  $order_id    Order ID.
     * @return bool
     */
    public function maybe_allow_assigned_vendor($has_access, $seller_id, $order_id)
    {

        $this->log('dokan_is_seller_has_order() called', [
            'has_access' => $has_access,
            'seller_id' => $seller_id,
            'order_id' => $order_id,
        ]);

        // If Dokan already allows it → respect that
        if ($has_access) {
            $this->log('Access already granted by Dokan core, leaving TRUE');
            return true;
        }

        $seller_id = (int) $seller_id;
        $order_id = (int) $order_id;

        if (!$seller_id || !$order_id) {
            $this->log('Missing seller_id or order_id, aborting', compact('seller_id', 'order_id'));
            return $has_access;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            $this->log('Order not found', $order_id);
            return $has_access;
        }

        // Check each product line item for _assigned_vendor_ids including this seller
        foreach ($order->get_items('line_item') as $item_id => $item) {

            $product_id = $item->get_product_id();
            if (!$product_id) {
                continue;
            }

            $assigned = get_post_meta($product_id, '_assigned_vendor_ids', true);

            if (empty($assigned)) {
                continue;
            }

            // Normalize into an array of ints
            if (is_string($assigned)) {
                // e.g. "12, 34,56"
                $parts = preg_split('/[,\s]+/', $assigned, -1, PREG_SPLIT_NO_EMPTY);
                $assigned = array_map('intval', $parts);
            } elseif (is_array($assigned)) {
                $assigned = array_map('intval', $assigned);
            } else {
                // Single scalar
                $assigned = [(int) $assigned];
            }

            if (in_array($seller_id, $assigned, true)) {

                $this->log('Access granted via _assigned_vendor_ids', [
                    'order_id' => $order_id,
                    'seller_id' => $seller_id,
                    'product_id' => $product_id,
                    'assigned' => $assigned,
                ]);

                // ✅ Give permission
                return true;
            }
        }

        // Still false after our custom check
        $this->log('Access still denied after _assigned_vendor_ids check', [
            'order_id' => $order_id,
            'seller_id' => $seller_id,
        ]);

        return $has_access;
    }
}

new Printlana_Order_Access_Edit();
