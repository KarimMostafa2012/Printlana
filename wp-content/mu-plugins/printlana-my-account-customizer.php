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

        // TEMPORARILY DISABLED - The logging created a 42MB log file!
        // add_action('all', [$this, 'log_all_hooks'], 1);
    }

    /**
     * Log every single action/filter that fires
     */
    public function log_all_hooks($hook) {
        // Only log action hooks (not filters)
        if (!doing_action()) {
            return;
        }

        // Log everything
        $this->log('ACTION FIRED: ' . $hook);
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
