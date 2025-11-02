<?php
/**
 * v1.0.0
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class Pi_Corw_Analytics{

    private $plugin_slug; 
    private $plugin_path;
    private $version;
    private $url;
    private $plugin_name;
    private $enable_tracking;
    private $enable_tracking_action;
    public function __construct($plugin_name, $plugin_path, $version) {
        $this->plugin_name = $plugin_name;
        $this->plugin_path = $plugin_path;

        $parts = explode('/', $this->plugin_path);

        $this->plugin_slug = $parts[0];

        $this->enable_tracking = 'pisol_'.$this->plugin_slug;
        $this->enable_tracking_action = 'pisol_'.$this->plugin_slug.'_action';
        

        $this->version = $version;

        $this->url = 'https://www.piwebsolution.com/plugin-tracker/'; 

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action( 'admin_footer-plugins.php', [ $this, 'print_deactivation_modal' ] );
        add_action('admin_post_pi_handle_deactivation_' . $this->plugin_slug, array($this, 'handle_deactivation_form'));

        add_action('admin_notices', array($this, 'show_tracker_notice'));

        add_action('admin_post_' . $this->enable_tracking_action, array($this, 'handle_tracker_action'));

    }

    public function show_tracker_notice() {
        //delete_option($this->enable_tracking);
        if (!empty(get_option($this->enable_tracking, ''))) {
            return; 
        }

        $notice = '<div class="notice notice-error is-dismissible">';
        $notice .= '<h4>Help to Improve ' . esc_html($this->plugin_name) . ' plugin</h4>';
        $notice .= '<p>'.__("Hi, your support can make a big difference!", 'conditional-discount-rule-woocommerce').'</p>';
        $notice .= '<p>'.__("We collect only technical data — including the plugin version, WordPress version, WooCommerce version, and site url — solely to improve compatibility and enhance plugin features.", 'conditional-discount-rule-woocommerce').'</p>';

        $notice .= '<p style="display: flex; justify-content: space-between; margin-top: 10px;">';

        $notice .= sprintf(
            '<a href="%s" class="button">%s</a>',
            esc_url(admin_url('admin-post.php?enable=0&action=' . $this->enable_tracking_action)),
            __('I Don\'t Help', 'conditional-discount-rule-woocommerce')
        );
        $notice .= sprintf(
            '<a href="%s" class="button button-primary" style="margin-right:20px; padding-left:30px; padding-right:30px;">%s</a>',
            esc_url(admin_url('admin-post.php?enable=1&action=' . $this->enable_tracking_action)),
            __('I Will Help', 'conditional-discount-rule-woocommerce')
        );
        
        $notice .= '</p>';
        $notice .= '</div>';
        echo $notice;
        
    }

    public function handle_tracker_action() {
        if (isset($_GET['enable']) && in_array($_GET['enable'], array('1', '0'))) {
            $enable = $_GET['enable'] === '1' ? 'enable' : 'disable';
            update_option($this->enable_tracking, $enable);
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : admin_url();

            if ($enable === 'enable') {
                $this->send_activation_data('enable');
            } 

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public function send_activation_data($action, $message = '') {
       
        $data = array(
            'plugin_slug' => $this->plugin_slug,
            'version' => $this->version,
            'site_url' => get_site_url(),
            'wp_version' => get_bloginfo('version'),
            'wc_version' => function_exists('WC') ? WC()->version : '',
            'action' => $action,
            'message' => $message,
        );

        // Make the request non-blocking by setting 'blocking' => false
        wp_remote_post($this->url, array(
            'body' => wp_json_encode($data),
            'headers' => array('Content-Type' => 'application/json'),
            'blocking' => false,
        ));
    }

    function enqueue_scripts($hook) {
        if ($hook !== 'plugins.php') return;

        $js = '
        jQuery(document).ready(function($) {
            var id = "deactivate-'.$this->plugin_slug.'";
            
            jQuery(document).on("click", "#"+id, function(e) {
                
                var href = $(this).attr("href");
                var popup = "#pi-deactivation-reason-'.$this->plugin_slug.'";
                if ($(popup).length) {
                    e.preventDefault();
                    $(popup).show();
                }
            });

            jQuery(document).on("click", "#pi-deactivator-close-'.$this->plugin_slug.'", function(e) {

                var popup = "#pi-deactivation-reason-'.$this->plugin_slug.'";
                if ($(popup).length) {
                    e.preventDefault();
                    $(popup).hide();
                }
            });

            jQuery(document).on("change", "#pi-deact-form-'.esc_attr($this->plugin_slug).' input[name=\'reason_radio\']", function () {
                if (jQuery(this).val() === "Other") {
                    jQuery("#pi-deact-form-'.esc_attr($this->plugin_slug).' textarea").show();
                } else {
                    jQuery("#pi-deact-form-'.esc_attr($this->plugin_slug).' textarea").hide();
                }
            });
        });
        ';

        wp_add_inline_script('jquery', $js);

        // Add the deactivation modal CSS as inline style
        $css = '.pi-deactivation-overlay-' . esc_attr($this->plugin_slug) . ' {
          display: none;
          position: fixed;
          top: 0; left: 0; right: 0; bottom: 0;
          width: 100%;
          height: 100%;
          background: rgba(0,0,0,0.5);
          z-index: 9999;
        }

        .pi-deact-close-' . esc_attr($this->plugin_slug) . ' {
          color: #000;
          text-decoration: none;
          font-size: 20px;
          font-weight: bold;
        }

        .pi-deactivation-modal-' . esc_attr($this->plugin_slug) . ' {
          position: fixed;
          top: 50%; 
          left: 50%;
          transform: translate(-50%, -50%);
          background: #fff;
          padding: 20px;
          width: 400px;
          max-width: 90%;
          max-height: 90%;
          overflow: auto;
          box-shadow: 0 2px 10px rgba(0,0,0,0.3);
          z-index: 10000;
        }';
        wp_add_inline_style('wp-admin', $css);
    }

    public function print_deactivation_modal() {
    $nonce = wp_create_nonce('pi_deactivate_nonce_' . $this->plugin_slug);
    ?>
        <div id="pi-deactivation-reason-<?php echo esc_attr($this->plugin_slug); ?>" style="display:none;" class="pi-deactivation-overlay-<?php echo esc_attr($this->plugin_slug); ?>">
            <div class="pi-deactivation-modal-<?php echo esc_attr($this->plugin_slug); ?>">
                <a href="javascript:void(0);" id="pi-deactivator-close-<?php echo esc_attr($this->plugin_slug); ?>" class="pi-deact-close-<?php echo esc_attr($this->plugin_slug); ?>" style="position: absolute; top: 10px; right: 10px; z-index: 10001;">&times;</a>
                <form id="pi-deact-form-<?php echo esc_attr($this->plugin_slug); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <h3>Help us to improve by giving reason for deactivation:</h3>
                    <div style="margin-bottom:10px;">
                        <label style="margin-bottom:10px; display:block;"><input type="radio" name="reason_radio" value="Was looking for something else"> Was looking for something else</label>
                        <label style="margin-bottom:10px; display:block;"><input type="radio" name="reason_radio" value="I found a better plugin"> I found a better plugin</label>
                        <label style="margin-bottom:10px; display:block;"><input type="radio" name="reason_radio" value="The plugin broke my site"> The plugin broke my site</label>
                        <label style="margin-bottom:10px; display:block;"><input type="radio" name="reason_radio" value="The plugin is not working as expected"> The plugin is not working as expected</label>
                        <label style="margin-bottom:10px; display:block;"><input type="radio" name="reason_radio" value="Other"> Other (please specify below)</label>
                    </div>
                    <textarea id="pi-deact-reason-<?php echo esc_attr($this->plugin_slug); ?>" name="message" style="width:100%; display:none;" rows="4" placeholder="Let us know the reason for deactivation"></textarea>
                    <?php
                        // Hidden fields
                        echo '<input type="hidden" name="action" value="pi_handle_deactivation_' . esc_attr($this->plugin_slug) . '">';
                        echo '<input type="hidden" name="plugin_slug" value="' . esc_attr($this->plugin_slug) . '">';
                        echo '<input type="hidden" name="nonce" value="' . esc_attr($nonce) . '">';
                    ?>
                    <p style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <button type="submit" name="action_type" class="pi-deact-skip button" value="skip">Skip</button>
                        <button type="submit" class="pi-deact-submit button button-primary" name="action_type" value="submit">Deactivate & Submit</button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_deactivation_form() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pi_deactivate_nonce_' . $this->plugin_slug)) {
            wp_die(__('Security check failed', 'conditional-discount-rule-woocommerce'));
        }

        $plugin_slug = sanitize_text_field($_POST['plugin_slug'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $reason = sanitize_text_field($_POST['reason_radio'] ?? '');
        // Combine reason and message, prioritizing reason if message is empty
        if (!empty($reason) && !empty($message)) {
            $message = $reason . ': ' . $message;
        } elseif (!empty($reason)) {
            $message = $reason;
        }

        $action_type = sanitize_text_field($_POST['action_type'] ?? '');

        if (is_multisite() && is_plugin_active_for_network($this->plugin_path)) {
            if (is_super_admin()) {
                deactivate_plugins($this->plugin_path, false, true); // network-wide
            } else {
                wp_die(__('You do not have permission to deactivate a network plugin.', 'conditional-discount-rule-woocommerce'));
            }
        } else {
            deactivate_plugins($this->plugin_path); // normal
        }

        if($action_type === 'submit'){
            $this->send_activation_data('disable', wp_strip_all_tags($message));
        }
        

        // Redirect back to plugins page
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }


}

new Pi_Corw_Analytics(
    'WooCommerce Cancel order request',
    'cancel-order-request-woocommerce/cancel-order-request-woocommerce.php',
    CANCEL_ORDER_REQUEST_WOOCOMMERCE_VERSION
);