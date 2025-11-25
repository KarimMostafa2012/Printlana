<?php
/**
 * Plugin Name: Order Access Edit (Printlana)
 * Description: Extends Dokan order view permission using custom meta:
 *              - Order meta `_pl_fulfillment_vendor_id`
 *              - Product meta `_assigned_vendor_ids` (fallback).
 * Version: 1.2
 * Author: Printlana
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', function () {

    error_log('[OrderAccessEdit] template_redirect START');

    // ============================================================
    // 1) Only in front-end + logged-in
    // ============================================================
    if (!is_user_logged_in()) {
        error_log('[OrderAccessEdit] EXIT: Not front-end or not logged in => ' . print_r([
            'is_admin' => is_admin(),
            'is_logged_in' => is_user_logged_in(),
        ], true));
        return;
    }

    // ============================================================
    // 2) Only on Dokan dashboard pages
    // ============================================================
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        error_log('[OrderAccessEdit] EXIT: Not dokan seller dashboard => ' . print_r([
            'function_exists' => function_exists('dokan_is_seller_dashboard'),
            'is_dokan_dashboard' => function_exists('dokan_is_seller_dashboard') ? dokan_is_seller_dashboard() : 'N/A',
        ], true));
        return;
    }

    // ============================================================
    // 3) We only care about the single order view ?order_id=xxxx
    // ============================================================
    if (empty($_GET['order_id'])) {
        error_log('[OrderAccessEdit] EXIT: No order_id in GET => ' . print_r($_GET, true));
        return;
    }

    // ============================================================
    // 4) Validate IDs
    // ============================================================
    $order_id = absint($_GET['order_id']);
    $seller_id = get_current_user_id();

    if (!$order_id || !$seller_id) {
        error_log('[OrderAccessEdit] EXIT: Invalid order_id or seller_id => ' . print_r([
            'order_id' => $order_id,
            'seller_id' => $seller_id,
        ], true));
        return;
    }

    // ============================================================
    // 5) Confirm helper exists
    // ============================================================
    if (!function_exists('dokan_is_seller_has_order')) {
        error_log('[OrderAccessEdit] EXIT: dokan_is_seller_has_order does not exist!');
        return;
    }

    // ============================================================
    // 6) Trigger permissions + log result
    // ============================================================
    $has_access = dokan_is_seller_has_order($seller_id, $order_id);
    $order = wc_get_order($order_id);
    $vendor_dokan = $order->get_meta('_dokan_vendor_id', true);
    $vendor_pl = $order->get_meta('_pl_fulfillment_vendor_id', true);

    error_log('[OrderAccessEdit] template_redirect permission result => ' . print_r([
        'seller_id' => $seller_id,
        'order_id' => $order_id,
        'vendor_dokan' => $vendor_dokan,
        'vendor_pl' => $vendor_pl,
        'has_access' => $has_access,
    ], true));

    // ============================================================
    // 7) Deny access if needed
    // ============================================================
    if (!$has_access) {
        error_log('[OrderAccessEdit] DENY: Seller does NOT have access => ' . print_r([
            'seller_id' => $seller_id,
            'order_id' => $order_id,
        ], true));

        wp_die(
            esc_html__('You do not have permission to view this order.', 'printlana'),
            esc_html__('Access denied', 'printlana'),
            ['response' => 403]
        );
    }

    // ============================================================
    // 8) If here, access granted
    // ============================================================
    error_log('[OrderAccessEdit] ALLOW: Seller has access to order => ' . print_r([
        'seller_id' => $seller_id,
        'order_id' => $order_id,
    ], true));

});



class Printlana_Order_Access_Edit
{

    public function __construct()
    {
        /**
         * Dokan helper dokan_is_seller_has_order( $seller_id, $order_id )
         *
         * Expected filter signature in Dokan:
         *   apply_filters( 'dokan_is_seller_has_order', $has_access, $seller_id, $order_id );
         */
        error_log('[OrderAccessEdit] Constructor running, adding filter'); // <--- add this
        add_filter('dokan_is_seller_has_order', function ($has_access, $seller_id, $order_id) {
            error_log('[OrderAccessEdit] TEST filter fired: seller=' . (int) $seller_id . ' order=' . (int) $order_id . ' has_access=' . var_export($has_access, true));
            return $has_access;
        }, 1, 3);


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
     * Extend Dokan "has order" permission with:
     *  1) Order meta `_pl_fulfillment_vendor_id` (primary)
     *  2) Product meta `_assigned_vendor_ids` (fallback)
     *
     * Also logs a detailed "permission snapshot" for debugging.
     *
     * @param bool $has_access  Result from Dokan's own logic.
     * @param int  $seller_id   Vendor user ID.
     * @param int  $order_id    Order ID.
     * @return bool
     */
    public function maybe_allow_assigned_vendor($has_access, $seller_id, $order_id)
    {
        $original_has_access = $has_access;

        $seller_id = (int) $seller_id;
        $order_id = (int) $order_id;

        // Basic snapshot before we do anything
        $this->log('Raw dokan_is_seller_has_order call', [
            'original_has_access' => $original_has_access,
            'seller_id' => $seller_id,
            'order_id' => $order_id,
        ]);

        if (!$seller_id || !$order_id) {
            $this->log('Missing seller_id or order_id, aborting early', compact('seller_id', 'order_id'));
            return $has_access;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            $this->log('Order not found, aborting', $order_id);
            return $has_access;
        }

        // Collect vendor-related indicators on this order
        $parent_id = (int) $order->get_parent_id();
        $post_author = (int) get_post_field('post_author', $order_id);
        $dokan_vendor_meta = (int) $order->get_meta('_dokan_vendor_id');
        $fulfillment_vendor = (int) $order->get_meta('_pl_fulfillment_vendor_id');
        $has_sub_order_flag = (int) $order->get_meta('_has_sub_order');
        $dokan_has_sub_flag = (int) $order->get_meta('_dokan_order_has_sub_order');

        // Log a detailed snapshot of this order and vendor context
        $this->log('Order vendor snapshot', [
            'order_id' => $order_id,
            'seller_id' => $seller_id,
            'parent_id' => $parent_id,
            'original_has_access' => $original_has_access,
            'post_author' => $post_author,
            '_dokan_vendor_id' => $dokan_vendor_meta,
            '_pl_fulfillment_vendor_id' => $fulfillment_vendor,
            '_has_sub_order' => $has_sub_order_flag,
            '_dokan_order_has_sub_order' => $dokan_has_sub_flag,
        ]);

        // If Dokan already allows it → respect that, but log clearly
        if ($original_has_access) {
            $this->log('Final decision: ALLOW (reason = Dokan core already granted access)', [
                'order_id' => $order_id,
                'seller_id' => $seller_id,
            ]);
            return true;
        }

        /**
         * 1) PRIMARY RULE:
         *    If this order is explicitly assigned via _pl_fulfillment_vendor_id, allow it.
         */
        if ($fulfillment_vendor && $fulfillment_vendor === $seller_id) {
            $this->log('Final decision: ALLOW (reason = order-level _pl_fulfillment_vendor_id match)', [
                'order_id' => $order_id,
                'seller_id' => $seller_id,
                'fulfillment_vendor' => $fulfillment_vendor,
            ]);

            return true;
        }

        /**
         * 2) SECONDARY RULE (optional/backwards compatible):
         *    Check each product line item for _assigned_vendor_ids including this seller.
         */
        $matched_products = [];

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
                $matched_products[] = [
                    'product_id' => $product_id,
                    'assigned' => $assigned,
                ];
            }
        }

        if (!empty($matched_products)) {
            $this->log('Final decision: ALLOW (reason = product-level _assigned_vendor_ids match)', [
                'order_id' => $order_id,
                'seller_id' => $seller_id,
                'matched_products' => $matched_products,
            ]);

            return true;
        }

        // 3) If we reach here, nobody granted permission
        $this->log('Final decision: DENY (no core access, no custom meta match)', [
            'order_id' => $order_id,
            'seller_id' => $seller_id,
        ]);

        return $has_access;
    }
}

new Printlana_Order_Access_Edit();
