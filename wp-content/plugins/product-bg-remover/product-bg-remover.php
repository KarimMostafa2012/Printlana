<?php
/**
 * Plugin Name: Client-Side Background Remover
 * Description: Remove backgrounds from images directly in the browser using canvas-based algorithm
 * Version: 2.0.0
 * Author: PrintLana
 */

if (!defined('ABSPATH')) {
    exit;
}

class Client_Side_BG_Remover {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_check_processed_image', array($this, 'check_processed_image'));
        add_action('wp_ajax_mark_image_processed', array($this, 'mark_image_processed'));
        add_action('wp_ajax_upload_bg_removed_image', array($this, 'upload_bg_removed_image'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

        // Fix for upload directory
        add_filter('upload_dir', array($this, 'fix_upload_dir'));
    }

    /**
     * Fix upload directory to use correct date
     */
    public function fix_upload_dir($upload) {
        // Only fix for our plugin's uploads
        if (isset($_POST['action']) && $_POST['action'] === 'upload_bg_removed_image') {
            $current_date = current_time('mysql');
            $time = strtotime($current_date);

            $upload['path'] = str_replace($upload['subdir'], '/' . date('Y/m', $time), $upload['path']);
            $upload['url'] = str_replace($upload['subdir'], '/' . date('Y/m', $time), $upload['url']);
            $upload['subdir'] = '/' . date('Y/m', $time);
        }
        return $upload;
    }

    /**
     * Enqueue scripts for media library
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'upload.php' && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        if (!get_option('pbr_enabled', true)) {
            return;
        }

        wp_enqueue_script(
            'bg-remover-client',
            plugin_dir_url(__FILE__) . 'js/bg-remover.js',
            array('jquery', 'media-editor'),
            '2.0.0',
            true
        );

        wp_localize_script('bg-remover-client', 'bgRemoverSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bg_remover_nonce'),
            'enabled' => true,
            'settings' => array(
                'tolerance' => get_option('pbr_tolerance', 30),
                'smoothing' => get_option('pbr_smoothing', 2),
                'feather' => get_option('pbr_feather', 1),
                'quality' => get_option('pbr_output_quality', 0.8)
            )
        ));

        // Add inline CSS for notifications and UI
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
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease-out;
                max-width: 400px;
            }
            .bg-remover-processing {
                background: #0073aa;
                color: white;
            }
            .bg-remover-notification {
                background: #46b450;
                color: white;
            }
            .bg-remover-error {
                background: #dc3232;
                color: white;
            }
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            /* Settings page styles */
            .pbr-settings-section {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .pbr-settings-section h2 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .pbr-settings-row {
                margin: 15px 0;
                display: flex;
                align-items: center;
            }
            .pbr-settings-row label {
                width: 200px;
                font-weight: 600;
            }
            .pbr-settings-row input[type="range"] {
                width: 300px;
                margin: 0 10px;
            }
            .pbr-settings-row .value-display {
                min-width: 40px;
                font-weight: bold;
                color: #0073aa;
            }
            .pbr-help-text {
                color: #666;
                font-style: italic;
                margin-left: 200px;
                margin-top: 5px;
                font-size: 0.9em;
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
     * Handle upload of background-removed image
     */
    public function upload_bg_removed_image() {
        check_ajax_referer('bg_remover_nonce', 'nonce');

        if (!isset($_FILES['file'])) {
            wp_send_json_error(array('message' => 'No file uploaded'));
        }

        // Require WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Handle the upload
        $file = $_FILES['file'];
        $upload_overrides = array(
            'test_form' => false,
            'test_type' => false,
            'test_size' => false
        );

        // Move uploaded file
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // Get original image ID if provided
            $original_id = isset($_POST['original_id']) ? intval($_POST['original_id']) : 0;

            // Create attachment
            $filename = pathinfo($movefile['file'], PATHINFO_FILENAME);
            if ($original_id) {
                $original_title = get_the_title($original_id);
                $filename = $original_title ? $original_title . '-bg-removed' : $filename;
            }

            $attachment = array(
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_text_field($filename),
                'post_content' => '',
                'post_status' => 'inherit',
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1)
            );

            $attachment_id = wp_insert_attachment($attachment, $movefile['file']);

            if (!is_wp_error($attachment_id)) {
                // Generate metadata
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);

                // Link to original if provided
                if ($original_id) {
                    update_post_meta($attachment_id, '_bg_removed_from', $original_id);
                }

                wp_send_json_success(array(
                    'attachment_id' => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id)
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to create attachment: ' . $attachment_id->get_error_message()));
            }
        } else {
            wp_send_json_error(array('message' => isset($movefile['error']) ? $movefile['error'] : 'Upload failed'));
        }
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_options_page(
            'Background Remover Settings',
            'BG Remover',
            'manage_options',
            'bg-remover-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['pbr_save_settings'])) {
            check_admin_referer('pbr_settings_nonce');

            update_option('pbr_enabled', isset($_POST['pbr_enabled']));
            update_option('pbr_tolerance', intval($_POST['pbr_tolerance']));
            update_option('pbr_smoothing', intval($_POST['pbr_smoothing']));
            update_option('pbr_feather', intval($_POST['pbr_feather']));
            update_option('pbr_output_quality', floatval($_POST['pbr_output_quality']));

            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $enabled = get_option('pbr_enabled', true);
        $tolerance = get_option('pbr_tolerance', 30);
        $smoothing = get_option('pbr_smoothing', 2);
        $feather = get_option('pbr_feather', 1);
        $quality = get_option('pbr_output_quality', 0.8);

        ?>
        <div class="wrap">
            <h1>Background Remover Settings</h1>

            <form method="post" action="">
                <?php wp_nonce_field('pbr_settings_nonce'); ?>

                <div class="pbr-settings-section">
                    <h2>General Settings</h2>

                    <div class="pbr-settings-row">
                        <label>
                            <input type="checkbox" name="pbr_enabled" value="1" <?php checked($enabled); ?>>
                            Enable Background Remover
                        </label>
                    </div>
                </div>

                <div class="pbr-settings-section">
                    <h2>Algorithm Settings</h2>

                    <div class="pbr-settings-row">
                        <label for="pbr_tolerance">Color Tolerance</label>
                        <input type="range" id="pbr_tolerance" name="pbr_tolerance" min="5" max="100" value="<?php echo $tolerance; ?>" oninput="this.nextElementSibling.textContent = this.value">
                        <span class="value-display"><?php echo $tolerance; ?></span>
                    </div>
                    <p class="pbr-help-text">Higher values remove more background but may affect the subject (5-100, default: 30)</p>

                    <div class="pbr-settings-row">
                        <label for="pbr_smoothing">Edge Smoothing</label>
                        <input type="range" id="pbr_smoothing" name="pbr_smoothing" min="0" max="5" value="<?php echo $smoothing; ?>" oninput="this.nextElementSibling.textContent = this.value">
                        <span class="value-display"><?php echo $smoothing; ?></span>
                    </div>
                    <p class="pbr-help-text">Smooths rough edges for cleaner cutouts (0-5, default: 2)</p>

                    <div class="pbr-settings-row">
                        <label for="pbr_feather">Edge Feathering</label>
                        <input type="range" id="pbr_feather" name="pbr_feather" min="0" max="5" value="<?php echo $feather; ?>" oninput="this.nextElementSibling.textContent = this.value">
                        <span class="value-display"><?php echo $feather; ?></span>
                    </div>
                    <p class="pbr-help-text">Softens edges for natural blending (0-5, default: 1)</p>

                    <div class="pbr-settings-row">
                        <label for="pbr_output_quality">Output Quality</label>
                        <input type="range" id="pbr_output_quality" name="pbr_output_quality" min="0.5" max="1" step="0.05" value="<?php echo $quality; ?>" oninput="this.nextElementSibling.textContent = this.value">
                        <span class="value-display"><?php echo $quality; ?></span>
                    </div>
                    <p class="pbr-help-text">PNG compression quality (0.5-1.0, default: 0.8)</p>
                </div>

                <p class="submit">
                    <input type="submit" name="pbr_save_settings" class="button button-primary" value="Save Settings">
                </p>
            </form>

            <div class="pbr-settings-section">
                <h2>How It Works</h2>
                <p><strong>Best for:</strong> Product photos with solid or gradient backgrounds (white, gray, colored backgrounds)</p>
                <p><strong>Algorithm:</strong> Detects background color from image corners and removes similar colors</p>
                <p><strong>Tips:</strong></p>
                <ul>
                    <li>Works best with high contrast between subject and background</li>
                    <li>Increase tolerance for backgrounds with slight variations</li>
                    <li>Use smoothing for cleaner edges on products</li>
                    <li>Add feathering for natural-looking edges</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=bg-remover-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('Client_Side_BG_Remover', 'get_instance'));

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function () {
    // Set default options
    add_option('pbr_enabled', true);
    add_option('pbr_tolerance', 30);
    add_option('pbr_smoothing', 2);
    add_option('pbr_feather', 1);
    add_option('pbr_output_quality', 0.8);
});