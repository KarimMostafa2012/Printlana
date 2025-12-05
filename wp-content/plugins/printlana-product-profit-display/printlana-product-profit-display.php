<?php
/**
 * Plugin Name: Printlana Product Profit Display
 * Description: Display vendor profit from sub-orders in the product listing "Earning" column
 * Version: 1.0
 * Author: Printlana
 */

if (!defined('ABSPATH')) {
    exit;
}

class Printlana_Product_Profit_Display
{
    public function __construct()
    {
        // Override the default products-listing-row template
        add_filter('dokan_get_template_part', array($this, 'override_product_listing_row'), 10, 3);
    }

    /**
     * Override the products-listing-row template
     *
     * @param string $template
     * @param string $slug
     * @param string $name
     * @return string
     */
    public function override_product_listing_row($template, $slug, $name)
    {
        // Only override the products-listing-row template
        if ($slug === 'products/products-listing-row') {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/products-listing-row.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Calculate total profit for a product from vendor's sub-orders
     *
     * @param WC_Product $product
     * @param int $vendor_id
     * @return float
     */
    public static function calculate_product_profit_from_suborders($product, $vendor_id = null)
    {
        if (!$vendor_id) {
            $vendor_id = dokan_get_current_user_id();
        }

        $product_id = $product->get_id();
        $total_profit = 0;

        // Get all sub-orders for this vendor (orders with parent_id != 0)
        $args = array(
            'limit' => -1,
            'type' => 'shop_order',
            'parent_exclude' => array(0), // Only sub-orders
            'meta_query' => array(
                array(
                    'key' => '_dokan_vendor_id',
                    'value' => $vendor_id,
                    'compare' => '='
                )
            )
        );

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            // Only count completed and processing orders
            if (!in_array($order->get_status(), array('completed', 'processing'))) {
                continue;
            }

            // Loop through order items
            foreach ($order->get_items() as $item) {
                $item_product_id = $item->get_product_id();

                // Check if this item is the product we're calculating for
                if ($item_product_id == $product_id) {
                    // Get item subtotal (revenue for this product in this order)
                    $item_subtotal = $item->get_subtotal();

                    // Get vendor earning from order meta
                    $vendor_earning = get_post_meta($order->get_id(), '_dokan_vendor_earning', true);
                    $order_total = $order->get_total();

                    // Calculate profit ratio
                    if ($order_total > 0 && $vendor_earning > 0) {
                        // Calculate this item's share of the vendor earning
                        $profit_ratio = $vendor_earning / $order_total;
                        $item_profit = $item_subtotal * $profit_ratio;
                        $total_profit += $item_profit;
                    } else {
                        // Fallback: use commission calculation
                        if (function_exists('dokan') && dokan()->commission) {
                            $earning = dokan()->commission->get_earning_by_product($product, 'seller');
                            if ($earning) {
                                $item_profit = $earning * $item->get_quantity();
                                $total_profit += $item_profit;
                            }
                        }
                    }
                }
            }
        }

        return apply_filters('printlana_product_profit_from_suborders', $total_profit, $product_id, $vendor_id);
    }
}

new Printlana_Product_Profit_Display();
