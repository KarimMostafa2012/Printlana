<?php
/**
 * Plugin Name: Printlana – Dokan Vendor Product Placeholder
 * Description: Ensures a vendor's product list in the Dokan dashboard doesn't break if they have no products by creating a temporary placeholder for them.
 * Author: Printlana
 * Version: 2.0
 */

class Printlana_Vendor_Product_Placeholder {

    /**
     * The meta key used to store the placeholder ID for a vendor.
     * @var string
     */
    const VENDOR_PLACEHOLDER_META_KEY = '_pl_vendor_placeholder_product_id';

    public function boot() {
        // Ensure WC & Dokan are present before wiring hooks
        if ( ! class_exists('WooCommerce') || ! function_exists('dokan') ) {
            return;
        }
    
        // Hook to run when a new vendor is approved/created or profile saved
        add_action('dokan_new_seller_created', [$this, 'check_and_create_placeholder_for_vendor'], 10, 1);
        add_action('dokan_seller_meta_saved', [$this, 'check_and_create_placeholder_for_vendor'], 10, 1);

        // This filter is the main workhorse. It runs before the product list is displayed.
        add_filter('dokan_pre_product_listing_args', [$this, 'ensure_vendor_has_product_in_query'], 999);

        // Hides the placeholder row using CSS
        add_action('wp', [$this, 'hide_placeholder_row']);
        
        // TEMP: Re-check on every request (remove later)
        // add_action('init', [$this, 'refresh_check_every_request']);

    }
    
    
    /**
     * TEMP: On every request, if a logged-in Dokan vendor exists, ensure the placeholder is in place.
     * Remove this method and its `add_action('init', ...)` hook once verified.
     */
    // public function refresh_check_every_request() {
    //     // Skip cron and ajax to avoid unnecessary work
    //     if ( defined('DOING_CRON') && DOING_CRON ) return;
    //     if ( defined('DOING_AJAX') && DOING_AJAX ) return;
    
    //     // Must be logged in
    //     if ( ! is_user_logged_in() ) return;
    
    //     $vendor_id = get_current_user_id();
    
    //     // Make sure this user is a Dokan seller (enabled)
    //     // dokan_is_user_seller($user_id) returns true for sellers (even if pending in some setups).
    //     // If you want *enabled only*, use dokan()->vendor->get($vendor_id)->is_enabled()
    //     if ( function_exists('dokan_is_user_seller') && dokan_is_user_seller( $vendor_id ) ) {
    //         // Optional: ensure vendor is enabled; uncomment if you need stricter check
    //         // $vendor = function_exists('dokan') ? dokan()->vendor->get($vendor_id) : null;
    //         // if ( $vendor && method_exists($vendor, 'is_enabled') && ! $vendor->is_enabled() ) return;
    
    //         // Ensure placeholder exists (or gets cleaned up if products now exist)
    //         $this->check_and_create_placeholder_for_vendor( $vendor_id );
    //     }
    // }


    /**
     * Checks if a vendor has any products, and if not, creates a placeholder for them.
     *
     * @param int $vendor_id The ID of the vendor to check.
     * @return int|null The ID of the placeholder product if created, null otherwise.
     */
    public function check_and_create_placeholder_for_vendor( $vendor_id ) {
        // First, check if this vendor already has a placeholder assigned.
        $existing_placeholder_id = get_user_meta($vendor_id, self::VENDOR_PLACEHOLDER_META_KEY, true);
        if ($existing_placeholder_id && get_post_status($existing_placeholder_id) === 'draft') {
            return $existing_placeholder_id;
        }

        // Next, check if the vendor has any real products.
        $product_query = new WP_Query([
            'post_type'      => 'product',
            'author'         => $vendor_id,
            'post_status'    => ['publish', 'pending', 'draft', 'private'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);

        // If they have products, we don't need to do anything.
        // if ( $product_query->have_posts() ) {
        //     return null;
        // }

        // If we reach here, the vendor has NO products. Let's create one for them.
        $placeholder_id = wp_insert_post([
            'post_type'   => 'product',
            'post_status' => 'draft',
            'post_title'  => 'Vendor Placeholder (Do Not Delete)',
            'post_author' => $vendor_id,
        ]);

        if ( $placeholder_id && ! is_wp_error($placeholder_id) ) {
            update_user_meta($vendor_id, self::VENDOR_PLACEHOLDER_META_KEY, $placeholder_id);
            return $placeholder_id;
        }
        
        return null;
    }
    
    /**
     * Modifies the Dokan product query to include the placeholder if needed.
     *
     * @param array $args The original WP_Query arguments.
     * @return array The modified arguments.
     */
    public function ensure_vendor_has_product_in_query( $args ) {
        if ( ! dokan_is_seller_dashboard() ) {
            return $args;
        }

        $vendor_id = dokan_get_current_user_id();
        $placeholder_id = $this->check_and_create_placeholder_for_vendor($vendor_id);

        if ( ! $placeholder_id ) {
            return $args;
        }

        if ( ! empty($args['post__in']) && is_array($args['post__in']) ) {
            $args['post__in'][] = $placeholder_id;
            $args['post__in'] = array_unique(array_map('intval', $args['post__in']));
        } else {
            $args['post__in'] = [$placeholder_id];
        }
        
        $args['post_status'] = ['publish','pending','draft','private'];

        return $args;
    }

    /**
     * Injects CSS into the page head to hide the placeholder product row from the vendor.
     */
    public function hide_placeholder_row() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        add_action('wp_head', function () {
            $vendor_id = dokan_get_current_user_id();
            $placeholder_id = (int) get_user_meta($vendor_id, self::VENDOR_PLACEHOLDER_META_KEY, true);
            
            if ( $placeholder_id ) {
                echo '<style>.dokan-dashboard .dokan-dashboard-content tr#post-' . (int)$placeholder_id . '{display:none !important;}</style>';
            }
        });
    }
}

// Instantiate the class to run the plugin
add_action('plugins_loaded', function() {
    // Create the object first
    $printlana_placeholder_plugin = new Printlana_Vendor_Product_Placeholder();
    // Then call the method on the object
    $printlana_placeholder_plugin->boot();
});