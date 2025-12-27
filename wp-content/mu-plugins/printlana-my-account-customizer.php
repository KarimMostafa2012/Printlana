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
     * Add CSS for orders page
     */
    public function orders_custom_css() {
        // Run on all My Account pages
        if (!is_account_page()) {
            return;
        }
        ?>
        <style>
            /* Center align order status */
            .order-status {
                text-align: center;
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

        // Fix SAR currency position by swapping order in DOM
        $content = preg_replace_callback(
            '/<bdi>([\d,\.]+)<span class="woocommerce-Price-currencySymbol">(.*?)<\/span><\/bdi>/s',
            function($matches) {
                // Swap: put currency symbol before the amount
                return '<bdi>' . $matches[2] . $matches[1] . '</bdi>';
            },
            $content
        );

        // Add pagination between Previous/Next buttons
        $content = $this->add_pagination_numbers($content);

        echo $content;
    }

    /**
     * Add pagination numbers between Previous and Next buttons
     */
    private function add_pagination_numbers($content) {
        // Get current page number - WooCommerce uses endpoint-based pagination
        global $wp;
        $current_page = isset($wp->query_vars['orders']) && absint($wp->query_vars['orders']) > 1
            ? absint($wp->query_vars['orders'])
            : 1;

        // Check if pagination exists in content
        if (strpos($content, 'woocommerce-pagination') !== false) {
            // Get ONLY current user's orders for accurate count
            $customer_id = get_current_user_id();

            // Get current user's orders with proper filtering
            $customer_orders = wc_get_orders([
                'customer_id' => $customer_id,  // Use customer_id for filtering
                'limit' => -1,  // Get all orders
                'return' => 'ids',  // Only return IDs for performance
            ]);

            $total_orders = count($customer_orders);
            $per_page = apply_filters('woocommerce_my_account_my_orders_per_page', 10);
            $total_pages = max(1, ceil($total_orders / $per_page));

            // Debug logging
            $this->log('PAGINATION DEBUG', [
                'customer_id' => $customer_id,
                'total_orders' => $total_orders,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
                'current_page' => $current_page,
            ]);

            if ($total_pages > 1) {
                // Build pagination HTML with product card button styles
                $pagination_html = '<div class="woocommerce-pagination woocommerce-Pagination" style="text-align: center; margin: 20px 0;">';

                // Previous button - using product card button style
                if ($current_page > 1) {
                    $prev_url = wc_get_endpoint_url('orders', $current_page - 1);
                    $prev_text = $this->is_arabic() ? 'السابق' : 'Previous';
                    $pagination_html .= '<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="' . esc_url($prev_url) . '" style="border: 1px solid var(--e-global-color-ee4e79d); background-color: white; padding: 12px 47px; border-radius: 100px; display: inline-block; margin: 0 5px; text-decoration: none;">' . $prev_text . '</a>';
                }

                // Page numbers - show max 5 pages for better UX
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                // Adjust range if we're at the beginning or end
                if ($current_page <= 3) {
                    $end_page = min(5, $total_pages);
                } elseif ($current_page >= $total_pages - 2) {
                    $start_page = max(1, $total_pages - 4);
                }

                // First page if not in range
                if ($start_page > 1) {
                    $page_url = wc_get_endpoint_url('orders', 1);
                    $pagination_html .= '<a href="' . esc_url($page_url) . '" style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border-radius: 3px;">1</a>';
                    if ($start_page > 2) {
                        $pagination_html .= '<span style="display: inline-block; padding: 8px 12px;">...</span>';
                    }
                }

                // Page numbers
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $current_page) {
                        $pagination_html .= '<span style="display: inline-block; padding: 8px 12px; margin: 0 2px; font-weight: bold; color: #0044F1; background: #f0f0f0; border-radius: 3px;">' . $i . '</span>';
                    } else {
                        $page_url = wc_get_endpoint_url('orders', $i);
                        $pagination_html .= '<a href="' . esc_url($page_url) . '" style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border-radius: 3px;">' . $i . '</a>';
                    }
                }

                // Last page if not in range
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        $pagination_html .= '<span style="display: inline-block; padding: 8px 12px;">...</span>';
                    }
                    $page_url = wc_get_endpoint_url('orders', $total_pages);
                    $pagination_html .= '<a href="' . esc_url($page_url) . '" style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border-radius: 3px;">' . $total_pages . '</a>';
                }

                // Next button - using product card button style
                if ($current_page < $total_pages) {
                    $next_url = wc_get_endpoint_url('orders', $current_page + 1);
                    $next_text = $this->is_arabic() ? 'التالي' : 'Next';
                    $pagination_html .= '<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="' . esc_url($next_url) . '" style="border: 1px solid var(--e-global-color-ee4e79d); background-color: white; padding: 12px 47px; border-radius: 100px; display: inline-block; margin: 0 5px; text-decoration: none;">' . $next_text . '</a>';
                }

                $pagination_html .= '</div>';

                // Replace existing pagination - match the entire div
                $content = preg_replace(
                    '/<div class="woocommerce-pagination[^"]*"[^>]*>.*?<\/div>/s',
                    $pagination_html,
                    $content
                );
            }
        }

        return $content;
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
