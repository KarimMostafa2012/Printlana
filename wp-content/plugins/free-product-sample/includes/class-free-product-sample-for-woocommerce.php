<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    DSFPS_Free_Product_Sample_Pro
 * @subpackage DSFPS_Free_Product_Sample_Pro/includes
 * @author     Multidots <inquiry@multidots.in>
 */
if ( !class_exists( 'DSFPS_Free_Product_Sample_Pro' ) ) {
    class DSFPS_Free_Product_Sample_Pro {
        /**
         * The loader that's responsible for maintaining and registering all hooks that power
         * the plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      DSFPS_Free_Product_Sample_Pro_Loader $loader Maintains and registers all hooks for the plugin.
         */
        protected $loader;

        /**
         * The unique identifier of this plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      string $plugin_name The string used to uniquely identify this plugin.
         */
        protected $plugin_name;

        /**
         * The current version of the plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      string $version The current version of the plugin.
         */
        protected $version;

        /**
         * Define the core functionality of the plugin.
         *
         * Set the plugin name and the plugin version that can be used throughout the plugin.
         * Load the dependencies, define the locale, and set the hooks for the admin area and
         * the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function __construct() {
            $this->plugin_name = 'free-product-sample';
            $this->version = DSFPS_PLUGIN_VERSION;
            $this->load_dependencies();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            $prefix = ( is_network_admin() ? 'network_admin_' : '' );
            add_filter( "{$prefix}plugin_action_links_" . DSFPS_PLUGIN_BASENAME, array($this, 'dsfps_plugin_action_links'), 10 );
            add_filter(
                'plugin_row_meta',
                array($this, 'dsfps_plugin_row_meta_action_links'),
                20,
                3
            );
            // Enable the ajax for the plugin if the YITH WooCommerce Added to Cart Popup Premium plugin is active.
            $fps_ajax_enable_disable = get_option( 'fps_ajax_enable_disable' );
            if ( in_array( 'yith-woocommerce-added-to-cart-popup-premium/init.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) && 'off' === $fps_ajax_enable_disable ) {
                update_option( 'fps_ajax_enable_disable', 'on' );
            }
        }

        /**
         * Load the required dependencies for this plugin.
         *
         * Include the following files that make up the plugin:
         *
         * - DSFPS_Free_Product_Sample_Pro_Loader. Orchestrates the hooks of the plugin.
         * - Advanced_Flat_Rate_Shipping_For_WooCommerce_Pro_i18n. Defines internationalization functionality.
         * - DSFPS_Free_Product_Sample_Pro_Admin. Defines all hooks for the admin area.
         * - Advanced_Flat_Rate_Shipping_For_WooCommerce_Pro_Public. Defines all hooks for the public side of the site.
         *
         * Create an instance of the loader which will be used to register the hooks
         * with WordPress.
         *
         * @since    1.0.0
         * @access   private
         */
        private function load_dependencies() {
            /**
             * The class responsible for orchestrating the actions and filters of the
             * core plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-free-product-sample-for-woocommerce-loader.php';
            /**
             * The class responsible for defining all actions that occur in the admin area.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-free-product-sample-for-woocommer-admin.php';
            /**
             * The class responsible for defining all actions that occur in the public-facing
             * side of the site.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-free-product-sample-for-woocommer-public.php';
            $this->loader = new DSFPS_Free_Product_Sample_Pro_Loader();
        }

        /**
         * Register all of the hooks related to the admin area functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_admin_hooks() {
            $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $plugin_admin = new DSFPS_Free_Product_Sample_Pro_Admin($this->get_plugin_name(), $this->get_version());
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'dsfps_pro_enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'dsfps_pro_enqueue_scripts' );
            $this->loader->add_action( 'admin_menu', $plugin_admin, 'dsfps_admin_menu_intigration' );
            $this->loader->add_action( 'admin_head', $plugin_admin, 'dsfps_dot_store_icon_css' );
            $this->loader->add_action( 'admin_head', $plugin_admin, 'dsfps_remove_admin_submenus' );
            $this->loader->add_action( 'wp_ajax_dsfps_save_sample_prod_settings', $plugin_admin, 'dsfps_save_sample_prod_settings' );
            $this->loader->add_action( 'wp_ajax_nopriv_dsfps_save_sample_prod_settings', $plugin_admin, 'dsfps_save_sample_prod_settings' );
            $this->loader->add_action( 'wp_ajax_dsfps_get_simple_and_variation_product_list_ajax', $plugin_admin, 'dsfps_get_simple_and_variation_product_list_ajax' );
            $this->loader->add_action( 'wp_ajax_dsfps_get_users_list_ajax', $plugin_admin, 'dsfps_get_users_list_ajax' );
            $this->loader->add_action( 'wp_ajax_dsfps_plugin_setup_wizard_submit', $plugin_admin, 'dsfps_plugin_setup_wizard_submit' );
            $this->loader->add_action( 'wp_ajax_dsfps_sample_order_email_save_meta', $plugin_admin, 'dsfps_sample_order_email_save_meta__premium_only' );
            $this->loader->add_action( 'admin_init', $plugin_admin, 'dsfps_send_wizard_data_after_plugin_activation' );
            $this->loader->add_action( 'after_setup_theme', $plugin_admin, 'dsfps_add_sample_products_page' );
            $this->loader->add_filter(
                'manage_edit-shop_order_columns',
                $plugin_admin,
                'dsfps_sample_shop_order_type_column',
                20
            );
            $this->loader->add_filter(
                'manage_woocommerce_page_wc-orders_columns',
                $plugin_admin,
                'dsfps_sample_shop_order_type_column',
                20
            );
            $this->loader->add_action(
                'manage_shop_order_posts_custom_column',
                $plugin_admin,
                'dsfps_sample_order_type_column_content',
                20,
                2
            );
            $this->loader->add_action(
                'manage_woocommerce_page_wc-orders_custom_column',
                $plugin_admin,
                'dsfps_sample_order_type_column_content',
                20,
                2
            );
            if ( !empty( $page ) && false !== strpos( $page, 'fps' ) ) {
                $this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'dsfps_admin_footer_review' );
            }
            /** Welcome Screen */
            $this->loader->add_action( 'admin_init', $plugin_admin, 'dsfps_welcome_screen_do_activation_redirect' );
        }

        /**
         * Register all of the hooks related to the public-facing functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_public_hooks() {
            $plugin_public = new DSFPS_Free_Product_Sample_Pro_Public($this->get_plugin_name(), $this->get_version());
            $fps_settings_enable_disable = get_option( 'fps_settings_enable_disable' );
            if ( $fps_settings_enable_disable === 'on' ) {
                $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'dsfps_pro_enqueue_public_scripts' );
                $this->loader->add_action( 'after_setup_theme', $plugin_public, 'dsfps_load_sample_button_after_setup_theme' );
                $this->loader->add_filter(
                    'woocommerce_add_to_cart_validation',
                    $plugin_public,
                    'dsfps_add_to_cart_validation',
                    10,
                    4
                );
                $this->loader->add_filter( 'wp_head', $plugin_public, 'dsfps_get_current_user_details' );
                $this->loader->add_action( 'wp_ajax_dsfps_add_to_cart_action_using_ajax', $plugin_public, 'dsfps_add_to_cart_action_using_ajax' );
                $this->loader->add_action( 'wp_ajax_nopriv_dsfps_add_to_cart_action_using_ajax', $plugin_public, 'dsfps_add_to_cart_action_using_ajax' );
                $this->loader->add_action( 'woocommerce_before_mini_cart_contents', $plugin_public, 'dsfps_mini_cart_re_calculation_ajax' );
                $this->loader->add_action( 'wp_loaded', $plugin_public, 'dsfps_add_to_cart_action' );
                $this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'dsfps_store_id' );
                $this->loader->add_filter(
                    'woocommerce_get_cart_item_from_session',
                    $plugin_public,
                    'dsfps_get_cart_items_from_session',
                    10,
                    2
                );
                $this->loader->add_action(
                    'woocommerce_checkout_create_order_line_item',
                    $plugin_public,
                    'dsfps_save_posted_data_into_order',
                    10,
                    3
                );
                $this->loader->add_filter(
                    'woocommerce_before_calculate_totals',
                    $plugin_public,
                    'dsfps_apply_sample_price_to_cart_item',
                    10
                );
                $this->loader->add_filter(
                    'wc_add_to_cart_message_html',
                    $plugin_public,
                    'dsfps_add_to_cart_message',
                    99,
                    4
                );
                $this->loader->add_filter(
                    'woocommerce_cart_item_name',
                    $plugin_public,
                    'dsfps_alter_item_name',
                    10,
                    3
                );
                $this->loader->add_filter(
                    'woocommerce_cart_item_price',
                    $plugin_public,
                    'dsfps_cart_item_price_filter',
                    10,
                    3
                );
                // phpcs:disable
                $this->loader->add_filter(
                    'woocommerce_cart_item_subtotal',
                    $plugin_public,
                    'dsfps_item_subtotal',
                    99,
                    3
                );
                // phpcs:enable
                $this->loader->add_filter(
                    'woocommerce_order_again_cart_item_data',
                    $plugin_public,
                    'dsfps_allow_re_order_samples_on_order_again',
                    10,
                    3
                );
                $dsfps_woocommerce_blocks_loaded_ran = did_action( 'woocommerce_blocks_loaded' );
                if ( !$dsfps_woocommerce_blocks_loaded_ran ) {
                    $this->loader->add_action(
                        'woocommerce_blocks_loaded',
                        $plugin_public,
                        'dsfps_block_prepend_text_to_cart_item',
                        20
                    );
                } else {
                    $plugin_public->dsfps_block_prepend_text_to_cart_item();
                }
                $this->loader->add_filter(
                    'wp_nav_menu_items',
                    $plugin_public,
                    'dsfps_add_sample_page_menu_item',
                    10,
                    2
                );
                $this->loader->add_filter( 'body_class', $plugin_public, 'dsfps_sample_prod_wc_body_class' );
                $this->loader->add_filter( 'body_class', $plugin_public, 'dsfps_add_class_to_hide_local_pickup_fields' );
                $this->loader->add_action(
                    'woocommerce_after_cart_item_quantity_update',
                    $plugin_public,
                    'dsfps_set_old_quantity_in_session',
                    20,
                    4
                );
                $this->loader->add_action( 'wp_ajax_dsfps_single_product_add_to_cart_using_ajax', $plugin_public, 'dsfps_single_product_add_to_cart_using_ajax' );
                $this->loader->add_action( 'wp_ajax_nopriv_dsfps_single_product_add_to_cart_using_ajax', $plugin_public, 'dsfps_single_product_add_to_cart_using_ajax' );
            }
        }

        /**
         * Return the plugin action links.  This will only be called if the plugin
         * is active.
         *
         * @param array $actions associative array of action names to anchor tags
         *
         * @return array associative array of plugin action links
         * @since 1.0.1
         */
        public function dsfps_plugin_action_links( $actions ) {
            $custom_actions = array(
                'configure' => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( array(
                    'page' => 'fps-sample-settings',
                ), admin_url( 'admin.php' ) ) ), __( 'Settings', 'free-product-sample' ) ),
                'docs'      => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( 'https://docs.thedotstore.com/collection/470-product-sample' ), __( 'Docs', 'free-product-sample' ) ),
                'support'   => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( 'www.thedotstore.com/support' ), __( 'Support', 'free-product-sample' ) ),
            );
            // add the links to the front of the actions list
            return array_merge( $custom_actions, $actions );
        }

        /**
         * Add review stars in plugin row meta
         *
         * @since 1.0.0
         */
        public function dsfps_plugin_row_meta_action_links( $plugin_meta, $plugin_file, $plugin_data ) {
            if ( isset( $plugin_data['TextDomain'] ) && $plugin_data['TextDomain'] !== 'free-product-sample' ) {
                return $plugin_meta;
            }
            $url = '';
            $url = esc_url( 'https://wordpress.org/plugins/free-product-sample/#reviews' );
            $plugin_meta[] = sprintf( '<a href="%s" target="_blank" style="color:#f5bb00;">%s</a>', $url, esc_html( '★★★★★' ) );
            return $plugin_meta;
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         *
         * @since    1.0.0
         */
        public function run() {
            $this->loader->run();
        }

        /**
         * The name of the plugin used to uniquely identify it within the context of
         * WordPress and to define internationalization functionality.
         *
         * @return    string    The name of the plugin.
         * @since     1.0.0
         */
        public function get_plugin_name() {
            return $this->plugin_name;
        }

        /**
         * The reference to the class that orchestrates the hooks with the plugin.
         *
         * @return    Advanced_Flat_Rate_Shipping_For_WooCommerce_Pro_Loader    Orchestrates the hooks of the plugin.
         * @since     1.0.0
         */
        public function get_loader() {
            return $this->loader;
        }

        /**
         * Retrieve the version number of the plugin.
         *
         * @return    string    The version number of the plugin.
         * @since     1.0.0
         */
        public function get_version() {
            return $this->version;
        }

    }

}