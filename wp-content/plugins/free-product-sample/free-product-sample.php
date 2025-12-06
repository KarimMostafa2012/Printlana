<?php

/**
 * Free Product Samples for WooCommerce – Try Before You Buy, Request Samples by Mail
 *
 * @link              https://www.thedotstore.com/
 * @since             1.0.0
 * @package           DSFPS_Free_Product_Sample_Pro
 *
 * @wordpress-plugin
 * Plugin Name: Advanced Product Sample for WooCommerce
 * Plugin URI: https://www.thedotstore.com/free-product-samples-for-woocommerce/
 * Description: Allows your customers to order and try out samples before buying the product.
 * Version: 1.4.2
 * Author: theDotstore
 * Author URI: https://www.thedotstore.com/
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: free-product-sample
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 *
 * WC requires at least: 4.5
 * WP tested up to: 6.8.3
 * WC tested up to: 10.2.2
 * Requires PHP: 5.6
 * Requires at least: 5.0
 */
// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die;
}
if ( function_exists( 'fps_fs' ) ) {
    fps_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'fps_fs' ) ) {
        // Create a helper function for easy SDK access.
        function fps_fs() {
            global $fps_fs;
            if ( !isset( $fps_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $fps_fs = fs_dynamic_init( array(
                    'id'             => '9758',
                    'slug'           => 'free-product-sample',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_2df3c5de98b32dd1c2dca3d8405ca',
                    'is_premium'     => false,
                    'premium_suffix' => 'Premium',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'slug'       => 'fps-sample-settings',
                        'first-path' => 'admin.php?page=fps-sample-settings',
                        'contact'    => false,
                        'support'    => false,
                        'network'    => true,
                    ),
                    'is_live'        => true,
                ) );
            }
            return $fps_fs;
        }

        // Init Freemius.
        fps_fs();
        // Signal that SDK was initiated.
        do_action( 'fps_fs_loaded' );
        fps_fs()->get_upgrade_url();
        function fps_fs_settings_url() {
            return admin_url( 'admin.php?page=fps-sample-settings' );
        }

        fps_fs()->add_filter( 'connect_url', 'fps_fs_settings_url' );
        fps_fs()->add_filter( 'after_skip_url', 'fps_fs_settings_url' );
        fps_fs()->add_filter( 'after_connect_url', 'fps_fs_settings_url' );
        fps_fs()->add_filter( 'after_pending_connect_url', 'fps_fs_settings_url' );
    }
}
if ( !defined( 'DSFPS_PLUGIN_VERSION' ) ) {
    define( 'DSFPS_PLUGIN_VERSION', '1.4.2' );
}
if ( !defined( 'DSFPS_PLUGIN_URL' ) ) {
    define( 'DSFPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'DSFPS_PLUGIN_DIR_PATH' ) ) {
    define( 'DSFPS_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'DSFPS_PLUGIN_NAME' ) ) {
    define( 'DSFPS_PLUGIN_NAME', 'Advanced Product Sample for WooCommerce' );
}
if ( !defined( 'DSFPS_PLUGIN_BASENAME' ) ) {
    define( 'DSFPS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( !defined( 'DSFPS_STORE_URL' ) ) {
    define( 'DSFPS_STORE_URL', 'https://www.thedotstore.com/' );
}
/**
 * Hide freemius account tab
 *
 * @since    1.2.0
 */
if ( !function_exists( 'dsfps_hide_account_tab' ) ) {
    function dsfps_hide_account_tab() {
        return true;
    }

    fps_fs()->add_filter( 'hide_account_tabs', 'dsfps_hide_account_tab' );
}
/**
 * Include plugin header on freemius account page
 *
 * @since    1.2.0
 */
if ( !function_exists( 'dsfps_load_plugin_header_after_account' ) ) {
    function dsfps_load_plugin_header_after_account() {
        require_once plugin_dir_path( __FILE__ ) . 'admin/partials/header/plugin-header.php';
    }

    fps_fs()->add_action( 'after_account_details', 'dsfps_load_plugin_header_after_account' );
}
/**
 * Hide powerd by popup from freemius account page
 *
 * @since    1.2.0
 */
if ( !function_exists( 'dsfps_hide_freemius_powered_by' ) ) {
    function dsfps_hide_freemius_powered_by() {
        return true;
    }

    fps_fs()->add_action( 'hide_freemius_powered_by', 'dsfps_hide_freemius_powered_by' );
}
/**
 * Start plugin setup wizard before license activation screen
 *
 * @since    1.2.0
 */
if ( !function_exists( 'dsfps_load_plugin_setup_wizard_connect_before' ) ) {
    function dsfps_load_plugin_setup_wizard_connect_before() {
        require_once plugin_dir_path( __FILE__ ) . 'admin/partials/dots-plugin-setup-wizard.php';
        ?>
        <div class="tab-panel" id="step5">
            <div class="ds-wizard-wrap">
                <div class="ds-wizard-content">
                    <h2 class="cta-title"><?php 
        echo esc_html__( 'Activate Plugin', 'free-product-sample' );
        ?></h2>
                </div>
        <?php 
    }

    fps_fs()->add_action( 'connect/before', 'dsfps_load_plugin_setup_wizard_connect_before' );
}
/**
 * End plugin setup wizard after license activation screen
 *
 * @since    1.2.0
 */
if ( !function_exists( 'dsfps_load_plugin_setup_wizard_connect_after' ) ) {
    function dsfps_load_plugin_setup_wizard_connect_after() {
        ?>
        </div>
        </div>
        </div>
        </div>
        <?php 
    }

    fps_fs()->add_action( 'connect/after', 'dsfps_load_plugin_setup_wizard_connect_after' );
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-category-banner-management-activator.php
 */
if ( !function_exists( 'activate_ds_woo_free_product_sample' ) ) {
    function activate_ds_woo_free_product_sample() {
        require plugin_dir_path( __FILE__ ) . 'includes/class-free-product-sample-for-woocommerce-activator.php';
        DSFPS_Free_Product_Sample_Pro_Activator::activate();
    }

}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-category-banner-management-deactivator.php
 */
if ( !function_exists( 'deactivate_ds_woo_free_product_sample' ) ) {
    function deactivate_ds_woo_free_product_sample() {
        require plugin_dir_path( __FILE__ ) . 'includes/class-free-product-sample-for-woocommerce-deactivator.php';
        DSFPS_Free_Product_Sample_Pro_Deactivator::deactivate();
        // remove sample page on plugin deactivation
        $page_id = get_option( 'dsfps_sample_page' );
        wp_delete_post( $page_id );
    }

}
register_activation_hook( __FILE__, 'activate_ds_woo_free_product_sample' );
register_deactivation_hook( __FILE__, 'deactivate_ds_woo_free_product_sample' );
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) || function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks.
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-free-product-sample-for-woocommerce.php';
    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    if ( !function_exists( 'run_ds_free_prod_sample_for_woo' ) ) {
        function run_ds_free_prod_sample_for_woo() {
            $plugin = new DSFPS_Free_Product_Sample_Pro();
            $plugin->run();
        }

    }
}
/**
 * Check Initialize plugin in case of WooCommerce plugin is missing.
 *
 * @since    1.0.0
 */
if ( !function_exists( 'dsfps_initialize_plugin' ) ) {
    function dsfps_initialize_plugin() {
        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) && (!function_exists( 'is_plugin_active_for_network' ) || !is_plugin_active_for_network( 'woocommerce/woocommerce.php' )) ) {
            add_action( 'admin_notices', 'dsfps_plugin_admin_notice' );
        } else {
            run_ds_free_prod_sample_for_woo();
        }
        // Load the plugin text domain for translation.
        load_plugin_textdomain( 'free-product-sample', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

}
add_action( 'plugins_loaded', 'dsfps_initialize_plugin' );
if ( !function_exists( 'dsfps_plugin_custom_icon_for_freemius' ) ) {
    function dsfps_plugin_custom_icon_for_freemius() {
        return dirname( __FILE__ ) . '/images/icon-woocommerce-free-product-samples.png';
    }

}
fps_fs()->add_filter( 'plugin_icon', 'dsfps_plugin_custom_icon_for_freemius' );
/**
 * Show admin notice in case of WooCommerce plugin is missing.
 *
 * @since    1.0.0
 */
if ( !function_exists( 'dsfps_plugin_admin_notice' ) ) {
    function dsfps_plugin_admin_notice() {
        $dsfps_plugin_name = esc_html__( DSFPS_PLUGIN_NAME, 'free-product-sample' );
        $wc_plugin = esc_html__( 'WooCommerce', 'free-product-sample' );
        ?>
        <div class="error">
            <p>
                <?php 
        echo sprintf( esc_html__( '%1$s requires %2$s to be installed & activated!', 'free-product-sample' ), '<strong>' . esc_html__( $dsfps_plugin_name, 'free-product-sample' ) . '</strong>', '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '" target="_blank"><strong>' . esc_html__( $wc_plugin, 'free-product-sample' ) . '</strong></a>' );
        ?>
            </p>
        </div>
        <?php 
    }

}
/**
 * Filter to make sample plugin compatible with Yith Catalog Mode plugin
 *
 * @since    1.0.0
 */
if ( in_array( 'yith-woocommerce-catalog-mode/init.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) || function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'yith-woocommerce-catalog-mode/init.php' ) || (in_array( 'yith-woocommerce-catalog-mode-premium/init.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) || function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'yith-woocommerce-catalog-mode-premium/init.php' )) ) {
    // Allow products to add in the cart
    if ( !function_exists( 'dsfps_allow_sample_product_add_to_cart' ) ) {
        function dsfps_allow_sample_product_add_to_cart() {
            return true;
        }

        add_filter( 'woocommerce_add_to_cart_validation', 'dsfps_allow_sample_product_add_to_cart', 11 );
    }
    // Make products as purchasable
    add_filter( 'woocommerce_is_purchasable', '__return_true' );
}
/**
 * Plugin compability with WooCommerce HPOS
 *
 * @since    1.2.0
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );