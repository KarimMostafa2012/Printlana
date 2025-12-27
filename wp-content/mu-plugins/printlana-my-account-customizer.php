<?php
/**
 * Plugin Name: Printlana My Account Customizer
 * Description: Customizes the behavior of the My Account page and its Dokan-managed tabs
 * Version: 1.0.0
 * Author: Qersh Yahya
 */

// TEST: This will run on EVERY page load to verify the file is being loaded
add_action('wp_footer', function() {
    echo '<!-- PRINTLANA PLUGIN LOADED! -->';
}, 999);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enable debugging mode - set to false to disable logs
define('PRINTLANA_MY_ACCOUNT_DEBUG', true);

class Printlana_My_Account_Customizer {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Only run on frontend
        if (is_admin()) {
            return;
        }

        // Hook debugger - logs ALL actions that fire on My Account pages
        add_action('template_redirect', [$this, 'start_hook_logging']);
    }

    /**
     * Start logging all WordPress actions on My Account pages
     */
    public function start_hook_logging() {
        // Only log on My Account pages
        if (!is_account_page()) {
            return;
        }

        global $wp;
        $current_endpoint = '';

        // Try to detect which endpoint we're on
        if (isset($wp->query_vars) && !empty($wp->query_vars)) {
            $endpoints = array_keys($wp->query_vars);
            $current_endpoint = !empty($endpoints) ? $endpoints[0] : 'dashboard';
        }

        // VISIBLE TEST - Add HTML comment to page to verify plugin is loading
        add_action('wp_head', function() use ($current_endpoint) {
            echo "\n<!-- PRINTLANA MY ACCOUNT PLUGIN IS ACTIVE - Endpoint: " . esc_html($current_endpoint) . " -->\n";
        });

        $this->log('=== MY ACCOUNT PAGE LOADED ===', [
            'endpoint' => $current_endpoint,
            'full_url' => home_url($wp->request),
        ]);

        // Enable filtered logging - only log payment/account related hooks
        add_action('all', [$this, 'log_filtered_hooks'], 1);

        // Add test elements to payment methods page
        add_action('woocommerce_before_account_payment_methods', [$this, 'add_test_before_payment_methods']);
        add_action('woocommerce_after_account_payment_methods', [$this, 'add_test_after_payment_methods']);

        // Add test elements to orders page (for discovery)
        add_action('woocommerce_before_account_orders', [$this, 'add_test_before_orders']);
        add_action('woocommerce_after_account_orders', [$this, 'add_test_after_orders']);
    }

    /**
     * Log only payment/account related hooks
     */
    public function log_filtered_hooks($hook) {
        // Only log action hooks (not filters)
        if (!doing_action()) {
            return;
        }

        // Keywords to filter for
        $keywords = [
            'payment',
            'woocommerce_account',
            'woocommerce_before_account',
            'woocommerce_after_account',
            'my_account',
            'orders',
        ];

        // Check if hook name contains any of our keywords
        foreach ($keywords as $keyword) {
            if (stripos($hook, $keyword) !== false) {
                $this->log('RELEVANT ACTION: ' . $hook);
                break;
            }
        }
    }

    /**
     * Add security notice BEFORE payment methods content
     */
    public function add_test_before_payment_methods() {
        echo '<div style="background: #e3f2fd; padding: 20px; border-right: 4px solid #2196f3; margin-bottom: 20px; border-radius: 4px;">';
        echo '<p style="margin: 0; color: #1976d2; font-size: 15px; line-height: 1.6;">لأسباب أمنية، لا نقوم حالياً بحفظ بيانات طرق الدفع. هذه الميزة ستكون متاحة قريباً.</p>';
        echo '</div>';
    }

    /**
     * Add security notice AFTER payment methods content (placeholder for future use)
     */
    public function add_test_after_payment_methods() {
        // Currently not displaying anything after payment methods
        // This can be used for additional information in the future
    }

    /**
     * Test: Add yellow box BEFORE orders content
     */
    public function add_test_before_orders() {
        echo '<p style="background: #ffeb3b; padding: 15px; border: 2px solid #f57c00; font-weight: bold;">TEST: This appears BEFORE orders</p>';
    }

    /**
     * Test: Add green box AFTER orders content
     */
    public function add_test_after_orders() {
        echo '<p style="background: #4caf50; padding: 15px; border: 2px solid #2e7d32; color: white; font-weight: bold;">TEST: This appears AFTER orders</p>';
    }

    /**
     * Debug logger
     */
    private function log($message, $data = null) {
        if (!defined('PRINTLANA_MY_ACCOUNT_DEBUG') || !PRINTLANA_MY_ACCOUNT_DEBUG) {
            return;
        }

        $log_message = '[PRINTLANA MY ACCOUNT] ' . $message;

        if ($data !== null) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }

        error_log($log_message);
    }
}

// Initialize the plugin
new Printlana_My_Account_Customizer();
