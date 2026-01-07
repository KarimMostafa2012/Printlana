<?php
/**
 * Plugin Name: Product Background Remover
 * Plugin URI: https://printlana.com
 * Description: Automatically removes backgrounds from product images using client-side processing
 * Version: 1.0.0
 * Author: Printlana
 * Author URI: https://printlana.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: product-bg-remover
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PBR_VERSION', '1.0.0');
define('PBR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PBR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Client-Side Background Removal with Smart Duplicate Detection
 */
class Client_Side_BG_Remover {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Enqueue scripts only on product edit pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX endpoint to check if image already processed
        add_action('wp_ajax_check_processed_image', array($this, 'check_processed_image'));
        
        // AJAX endpoint to mark image as processed
        add_action('wp_ajax_mark_image_processed', array($this, 'mark_image_processed'));
        
        // Add settings link
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }
    
    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        global $post_type;
        if ($post_type !== 'product') {
            return;
        }
        
        // Check if feature is enabled
        if (!get_option('pbr_enabled', true)) {
            return;
        }
        
        // Enqueue the background removal library
        wp_enqueue_script(
            'imgly-bg-removal',
            'https://unpkg.com/@imgly/background-removal@1.4.5/dist/index.umd.js',
            array(),
            '1.4.5',
            true
        );
        
        wp_enqueue_script(
            'product-bg-remover',
            PBR_PLUGIN_URL . 'js/product-bg-remover.js',
            array('jquery', 'imgly-bg-removal'),
            PBR_VERSION,
            true
        );
        
        wp_localize_script('product-bg-remover', 'bgRemoverData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bg_remover_nonce'),
            'enabled' => true
        ));
        
        // Add inline CSS for notifications
        wp_add_inline_style('wp-admin', '
            .bg-remover-processing,
            .bg-remover-notification,
            .bg-remover-error {
                position: fixed;
                top: 50px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                z-index: 99999;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                animation: slideIn 0.3s ease-out;
            }
            .bg-remover-processing { background: #0073aa; color: white; }
            .bg-remover-notification { background: #46b450; color: white; }
            .bg-remover-error { background: #dc3232; color: white; }
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        ');
    }
    
    /**
     * Check if image hash already processed
     */
    public function check_processed_image() {
        check_ajax_referer('bg_remover_nonce', 'nonce');
        
        $file_hash = sanitize_text_field($_POST['file_hash']);
        
        // Query for attachment with this hash
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => array(
                array(
                    'key' => '_original_file_hash',
                    'value' => $file_hash,
                    'compare' => '='
                ),
                array(
                    'key' => '_background_removed',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        );
        
        $existing = get_posts($args);
        
        if (!empty($existing)) {
            $attachment_id = $existing[0];
            wp_send_json_success(array(
                'exists' => true,
                'attachment_id' => $attachment_id,
                'url' => wp_get_attachment_url($attachment_id)
            ));
        }
        
        wp_send_json_success(array('exists' => false));
    }
    
    /**
     * Save processed image and mark with hash
     */
    public function mark_image_processed() {
        check_ajax_referer('bg_remover_nonce', 'nonce');
        
        $attachment_id = intval($_POST['attachment_id']);
        $file_hash = sanitize_text_field($_POST['file_hash']);
        
        // Store hash and processed flag
        update_post_meta($attachment_id, '_original_file_hash', $file_hash);
        update_post_meta($attachment_id, '_background_removed', '1');
        update_post_meta($attachment_id, '_bg_removed_date', current_time('mysql'));
        
        wp_send_json_success();
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=pbr-settings') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('Client_Side_BG_Remover', 'get_instance'));

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    // Set default options
    add_option('pbr_enabled', true);
    add_option('pbr_output_quality', 0.8);
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
});