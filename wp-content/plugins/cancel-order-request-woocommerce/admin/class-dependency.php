<?php

class pisol_corw_dependency_manager {

    private $dependency_slug = 'add-coupon-by-link-for-woocommerce'; // Slug of the dependency plugin
    private $dependency_file = 'add-coupon-by-link-for-woocommerce/add-coupon-by-link-woocommerce.php'; // Path to the main file of the dependency plugin

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_install_dependency_plugin', [$this, 'handle_install_dependency_plugin']);
    }

    public function enqueue_admin_scripts($hook) {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('pisol-corw-install-dependency-plugin').addEventListener('click', function() {
                    
                    var button = this;
                    button.disabled = true;
                    button.innerText = 'Installing...';

                    jQuery.post(ajaxurl, {
                        action: 'install_dependency_plugin',
                        nonce: '<?php echo wp_create_nonce('install_dependency_nonce'); ?>'
                    }, function(response) {
                        alert(response.data.message);
                        if (response.success) {
                            location.reload();
                        } else {
                            button.disabled = false;
                            button.innerText = 'Install Dependency Plugin';
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function handle_install_dependency_plugin() {
        check_ajax_referer('install_dependency_nonce', 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => 'You do not have permission to install plugins.']);
        }

        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        if (file_exists(WP_PLUGIN_DIR . '/' . $this->dependency_file)) {
            if (!is_plugin_active($this->dependency_file)) {
                activate_plugin($this->dependency_file);
                wp_send_json_success(['message' => 'Dependency plugin activated successfully.']);
            } else {
                wp_send_json_success(['message' => 'Dependency plugin is already active.']);
            }
        }

        $api = plugins_api('plugin_information', ['slug' => $this->dependency_slug]);
        if (is_wp_error($api)) {
            wp_send_json_error(['message' => 'Failed to fetch plugin information.']);
        }

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);

        if (is_wp_error($result) || !$result) {
            wp_send_json_error(['message' => 'Plugin installation failed.']);
        }

        activate_plugin($this->dependency_file);

        wp_send_json_success(['message' => 'Dependency plugin installed and activated successfully.']);
    }
}

new pisol_corw_dependency_manager();
