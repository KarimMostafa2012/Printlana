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

        // Customize orders page
        add_action('wp_head', [$this, 'orders_custom_css']);
        add_filter('gettext', [$this, 'translate_order_text'], 20, 3);
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'add_order_actions'], 10, 2);

        // Use output buffering to modify order date display
        add_action('woocommerce_account_orders_endpoint', [$this, 'start_order_output_buffer'], 1);
        add_action('woocommerce_account_orders_endpoint', [$this, 'end_order_output_buffer'], 999);
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
     * Add message BEFORE orders content if no orders exist
     */
    public function add_test_before_orders() {
        // Get current user
        $customer_id = get_current_user_id();

        // Get order count for this customer
        $customer_orders = wc_get_orders([
            'customer_id' => $customer_id,
            'limit' => 1, // We only need to know if at least one exists
            'return' => 'ids',
        ]);

        // Only show message if user has NO orders
        if (empty($customer_orders)) {
            $shop_url = get_permalink(wc_get_page_id('shop'));
            echo '<div style="background: #e3f2fd; padding: 20px; border-right: 4px solid #2196f3; margin-bottom: 20px; border-radius: 4px;">';
            echo '<p style="margin: 0; color: #1976d2; font-size: 15px; line-height: 1.6;">لم تقم بإنشاء أي طلب بعد. <a href="' . esc_url($shop_url) . '" style="color: #1976d2; font-weight: bold; text-decoration: underline;">ابدأ التسوق الآن</a> لتقديم طلبك الأول!</p>';
            echo '</div>';
        }
    }

    /**
     * Placeholder for content AFTER orders
     */
    public function add_test_after_orders() {
        // Currently not displaying anything after orders
        // This can be used for additional information in the future
    }

    /**
     * Add CSS for orders page customization
     */
    public function orders_custom_css() {
        // Run on all My Account pages to ensure CSS loads
        if (!is_account_page()) {
            return;
        }
        ?>
        <style>
            /* Fix SAR currency symbol position - move to left side */
            .order-total .sar-currency-svg {
                margin-right: 0 !important;
                margin-left: 0.2em !important;
            }

            /* Reorder button styling */
            .order-reorder-btn,
            .woocommerce-button.button.order-again {
                background: #2196f3 !important;
                color: white !important;
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                display: inline-block;
                margin-top: 8px;
                transition: background 0.2s;
            }
            .order-reorder-btn:hover,
            .woocommerce-button.button.order-again:hover {
                background: #1976d2 !important;
                color: white !important;
            }
        </style>
        <?php
    }

    /**
     * Check if current language is Arabic
     */
    private function is_arabic() {
        // Check if WPML is active and current language is Arabic
        if (defined('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE === 'ar';
        }

        // Fallback: check URL for /ar/ or ?lang=ar
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($current_url, '/ar/') !== false || strpos($current_url, '?lang=ar') !== false || strpos($current_url, '&lang=ar') !== false);
    }

    /**
     * Translate "Order" text based on language
     */
    public function translate_order_text($translated, $text, $domain) {
        // Only translate for WooCommerce domain
        if ($domain !== 'woocommerce') {
            return $translated;
        }

        // Don't translate if not Arabic
        if (!$this->is_arabic()) {
            return $translated;
        }

        // Translate "Order" to Arabic
        if ($text === 'Order') {
            return 'رقم الطلب';
        }

        return $translated;
    }

    /**
     * Add reorder button to order actions
     */
    public function add_order_actions($actions, $order) {
        // Determine button text based on language
        $button_text = $this->is_arabic() ? 'إعادة الطلب' : 'Reorder';

        // Add reorder button
        $actions['order-again'] = [
            'url' => wp_nonce_url(add_query_arg('order_again', $order->get_id(), wc_get_cart_url()), 'woocommerce-order_again'),
            'name' => $button_text,
        ];

        return $actions;
    }

    /**
     * Start output buffering for orders content
     */
    public function start_order_output_buffer() {
        ob_start();
    }

    /**
     * End output buffering and modify orders content
     */
    public function end_order_output_buffer() {
        $content = ob_get_clean();

        if ($this->is_arabic()) {
            // Arabic version: Replace "Order No." with Arabic
            $content = preg_replace('/Order No\./i', 'رقم الطلب', $content);

            // Arabic version: Add date label before time elements
            $content = preg_replace(
                '/<div class="order-date">\s*<time/',
                '<div class="order-date"><span class="date-label" style="margin-left: 0.3em;">التاريخ: </span><time',
                $content
            );
        } else {
            // English version: Keep "Order No." but add date label
            $content = preg_replace(
                '/<div class="order-date">\s*<time/',
                '<div class="order-date"><span class="date-label" style="margin-right: 0.3em;">Date: </span><time',
                $content
            );
        }

        echo $content;
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
