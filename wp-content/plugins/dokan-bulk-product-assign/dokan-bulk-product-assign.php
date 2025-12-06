<?php
/**
 * Plugin Name: Dokan Bulk Product Assign to Vendor
 * Description: Allows admins to bulk assign existing products to vendors by duplicating them using Dokan SPMV functionality.
 * Version: 7.2.0
 * Author: Andrii
 * License: GPL-2.0+
 * Text Domain: dokan-bulk-assign
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dokan_Bulk_Product_Assign
 */
class Dokan_Bulk_Product_Assign {

    /**
     * Constructor
     */
    public function __construct() {
        // Check if Dokan and SPMV are active
        add_action('admin_init', array($this, 'check_dependencies'));

        // Load text domain
        add_action('init', array($this, 'load_textdomain'));

        // Add bulk action to products list
        add_filter('bulk_actions-edit-product', array($this, 'add_bulk_action'));

        // Handle bulk action
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_action'), 10, 3);

        // Admin notice after bulk action
        add_action('admin_notices', array($this, 'admin_notices'));

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // AJAX handler for vendor search
        add_action('wp_ajax_dokan_bulk_assign_search_vendors', array($this, 'search_vendors'));
    }

    /**
     * Check if required plugins are active
     */
    public function check_dependencies() {
        if (!class_exists('WeDevs_Dokan') || !dokan_pro()->module->is_active('spmv')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . __('Dokan Bulk Product Assign requires Dokan Pro and the Single Product Multi Vendor (SPMV) module to be active.', 'dokan-bulk-assign') . '</p></div>';
            });
            return;
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('dokan-bulk-assign', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Add bulk action to products list
     *
     * @param array $actions
     * @return array
     */
    public function add_bulk_action($actions) {
        $actions['assign_to_vendor'] = __('Assign to Vendor', 'dokan-bulk-assign');
        return $actions;
    }

    /**
     * Handle bulk action
     *
     * @param string $redirect_url
     * @param string $action
     * @param array $post_ids
     * @return string
     */
    public function handle_bulk_action($redirect_url, $action, $post_ids) {
        if ($action !== 'assign_to_vendor') {
            return $redirect_url;
        }

        // Get vendor ID from request
        $vendor_id = isset($_REQUEST['vendor_id']) ? intval($_REQUEST['vendor_id']) : 0;

        if (!$vendor_id) {
            $redirect_url = add_query_arg('bulk_assign_error', 'no_vendor', $redirect_url);
            return $redirect_url;
        }

        // Verify vendor is valid
        $vendor = get_user_by('id', $vendor_id);
        if (!$vendor || !(in_array('seller', $vendor->roles) || in_array('vendor', $vendor->roles) || user_can($vendor->ID, 'dokandar') || get_user_meta($vendor->ID, 'dokan_enable_selling', true) === 'yes')) {
            $redirect_url = add_query_arg('bulk_assign_error', 'invalid_vendor', $redirect_url);
            return $redirect_url;
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($post_ids as $product_id) {
            if ($this->assign_product_to_vendor($product_id, $vendor_id)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        $redirect_url = add_query_arg(array(
            'bulk_assign_success' => $success_count,
            'bulk_assign_error' => $error_count,
        ), $redirect_url);

        return $redirect_url;
    }

    /**
     * Assign product to vendor by duplicating
     *
     * @param int $product_id
     * @param int $vendor_id
     * @return bool
     */
    private function assign_product_to_vendor($product_id, $vendor_id) {
        // Check if already assigned
        if ($this->is_product_assigned_to_vendor($product_id, $vendor_id)) {
            return false; // Already assigned
        }

        // Use Dokan SPMV Product Duplicator
        if (!class_exists('Dokan_SPMV_Product_Duplicator')) {
            return false;
        }

        $duplicator = Dokan_SPMV_Product_Duplicator::instance();
        $duplicated_id = $duplicator->clone_product($product_id, $vendor_id);

        if (is_wp_error($duplicated_id)) {
            return false;
        }

        // Save brands
        $this->save_product_brands_after_clone($duplicated_id, $product_id);

        return true;
    }

    /**
     * Check if product is already assigned to vendor
     *
     * @param int $product_id
     * @param int $vendor_id
     * @return bool
     */
    private function is_product_assigned_to_vendor($product_id, $vendor_id) {
        global $wpdb;

        $map_id = get_post_meta($product_id, '_has_multi_vendor', true);

        if (empty($map_id)) {
            return false;
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}dokan_product_map` WHERE `map_id` = %d AND `seller_id` = %d AND `is_trash` IN (0,2,3)",
            $map_id,
            $vendor_id
        );

        $result = $wpdb->get_row($sql);

        return !empty($result);
    }

    /**
     * Save product brands after clone
     *
     * @param int $cloned_product_id
     * @param int $product_id
     */
    private function save_product_brands_after_clone($cloned_product_id, $product_id) {
        if (function_exists('dokan')) {
            $brands_ids = dokan()->product->get_brand_ids($product_id);
            dokan()->product->save_brands($cloned_product_id, $brands_ids);
        }
    }

    /**
     * Display admin notices after bulk action
     */
    public function admin_notices() {
        if (isset($_GET['bulk_assign_success'])) {
            $success = intval($_GET['bulk_assign_success']);
            $error = isset($_GET['bulk_assign_error']) ? intval($_GET['bulk_assign_error']) : 0;

            printf(
                '<div class="notice notice-success"><p>' . __('Bulk assign complete: %d products assigned successfully, %d failed.', 'dokan-bulk-assign') . '</p></div>',
                $success,
                $error
            );
        }

        if (isset($_GET['bulk_assign_error']) && $_GET['bulk_assign_error'] === 'no_vendor') {
            echo '<div class="notice notice-error"><p>' . __('No vendor selected for assignment.', 'dokan-bulk-assign') . '</p></div>';
        }

        if (isset($_GET['bulk_assign_error']) && $_GET['bulk_assign_error'] === 'invalid_vendor') {
            echo '<div class="notice notice-error"><p>' . __('Invalid vendor selected.', 'dokan-bulk-assign') . '</p></div>';
        }
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'edit.php' || get_current_screen()->post_type !== 'product') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13');

        wp_enqueue_script(
            'dokan-bulk-assign',
            plugins_url('assets/js/dokan-bulk-assign.js', __FILE__),
            array('jquery', 'select2'),
            '1.0.2',
            true
        );

        wp_localize_script(
            'dokan-bulk-assign',
            'dokanBulkAssign',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dokan-bulk-assign-nonce'),
                'i18n' => array(
                    'searching' => __('Searching vendors', 'dokan-bulk-assign'),
                    'no_vendors' => __('No vendors found', 'dokan-bulk-assign'),
                    'input_too_short' => __('Search vendors', 'dokan-bulk-assign'),
                    'select_vendor' => __('Please select a vendor before assigning products.', 'dokan-bulk-assign'),
                )
            )
        );

        wp_add_inline_style('dokan-bulk-assign', '
            .bulk-assign-vendor { padding: 10px; background: #f9f9f9; border: 1px solid #ddd; margin-top: 10px; }
            .bulk-assign-vendor label { margin-right: 10px; }
            .select2-container { width: 300px !important; }
        ');
    }

    /**
     * AJAX handler for vendor search
     */
    public function search_vendors() {
        check_ajax_referer('dokan-bulk-assign-nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized operation', 'dokan-bulk-assign')), 403);
        }

        $vendors = array();
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        if (!empty($search)) {
            $results = dokan()->vendor->all(array(
                'search' => '*' . $search . '*',
                'number' => -1,
            ));

            if (!count($results)) {
                $results = dokan()->vendor->get_vendors(array(
                    'number' => -1,
                    'status' => array('all'),
                    'role__in' => array('seller', 'vendor'),
                    'meta_query' => array(
                        array(
                            'key' => 'dokan_enable_selling',
                            'value' => 'yes',
                            'compare' => '='
                        ),
                        array(
                            'key' => 'dokan_store_name',
                            'value' => $search,
                            'compare' => 'LIKE'
                        )
                    )
                ));
            }

            foreach ($results as $vendor) {
                $vendors[] = array(
                    'id' => $vendor->get_id(),
                    'text' => !empty($vendor->get_shop_name()) ? $vendor->get_shop_name() . ' (ID: ' . $vendor->get_id() . ')' : $vendor->get_name() . ' (ID: ' . $vendor->get_id() . ')'
                );
            }
        }

        wp_send_json_success(array('results' => $vendors));
    }
}

// Initialize the plugin
new Dokan_Bulk_Product_Assign();