<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 *
 * @package    DSFPS_Free_Product_Sample_Pro
 * @subpackage DSFPS_Free_Product_Sample_Pro/admin
 * @author     Multidots <inquiry@multidots.in>
 */
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;
if ( !class_exists( 'DSFPS_Free_Product_Sample_Pro_Admin' ) ) {
    class DSFPS_Free_Product_Sample_Pro_Admin {
        /**
         * The ID of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string $plugin_name The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string $version The current version of this plugin.
         */
        private $version;

        /**
         * Initialize the class and set its properties.
         *
         * @param string $plugin_name The name of this plugin.
         * @param string $version     The version of this plugin.
         *
         * @since    1.0.0
         */
        public function __construct( $plugin_name, $version ) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }

        /**
         * Register the stylesheets for the admin area.
         *
         * @param string $hook display current page name
         *
         * @since    1.0.0
         *
         */
        public function dsfps_pro_enqueue_styles( $hook ) {
            if ( false !== strpos( $hook, 'page_fps' ) ) {
                wp_enqueue_style(
                    $this->plugin_name . 'select2-min-style',
                    plugin_dir_url( __FILE__ ) . 'css/select2.min.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'jquery-ui-min-style',
                    plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'jquery-timepicker-style',
                    plugin_dir_url( __FILE__ ) . 'css/jquery.timepicker.min.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'font-awesome-style',
                    plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css',
                    array(),
                    'all'
                );
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_style(
                    $this->plugin_name . 'main-style',
                    plugin_dir_url( __FILE__ ) . 'css/style.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'media-style',
                    plugin_dir_url( __FILE__ ) . 'css/media.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'notice-css',
                    plugin_dir_url( __FILE__ ) . 'css/notice.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'plugin-new-style',
                    plugin_dir_url( __FILE__ ) . 'css/plugin-new-style.css',
                    array(),
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . 'plugin-setup-wizard',
                    plugin_dir_url( __FILE__ ) . 'css/plugin-setup-wizard.css',
                    array(),
                    'all'
                );
                if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
                    wp_enqueue_style(
                        $this->plugin_name . 'upgrade-dashboard-style',
                        plugin_dir_url( __FILE__ ) . 'css/upgrade-dashboard.css',
                        array(),
                        'all'
                    );
                }
            }
        }

        /**
         * Register the JavaScript for the admin area.
         *
         * @param string $hook display current page name
         *
         * @since    1.0.0
         *
         */
        public function dsfps_pro_enqueue_scripts( $hook ) {
            if ( false !== strpos( $hook, '_fps' ) ) {
                wp_enqueue_script(
                    $this->plugin_name . 'select2',
                    plugin_dir_url( __FILE__ ) . 'js/select2.min.js',
                    array('jquery'),
                    $this->version,
                    true
                );
                wp_enqueue_script(
                    $this->plugin_name . '-help-scout-beacon-js',
                    plugin_dir_url( __FILE__ ) . 'js/help-scout-beacon.js',
                    array('jquery'),
                    $this->version,
                    false
                );
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery-tiptip' );
                // Freemius checkout popup library for upgrade
                if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
                    wp_enqueue_script(
                        $this->plugin_name . 'freemius_pro',
                        'https://checkout.freemius.com/checkout.min.js',
                        array('jquery'),
                        $this->version,
                        true
                    );
                }
                wp_enqueue_script(
                    $this->plugin_name . 'admin-js',
                    plugin_dir_url( __FILE__ ) . 'js/free-product-sample-for-woocommerce-admin.js',
                    array(),
                    $this->version,
                    true
                );
                wp_localize_script( $this->plugin_name . 'admin-js', 'dsfps_coditional_vars', array(
                    'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
                    'dpb_api_url'             => DSFPS_STORE_URL,
                    'fps_ajax_nonce'          => wp_create_nonce( 'fps_ajax_request_nonce' ),
                    'setup_wizard_ajax_nonce' => wp_create_nonce( 'wizard_ajax_nonce' ),
                ) );
            }
        }

        /*
         * Shipping method Pro Menu
         *
         * @since    1.0.0
         */
        public function dsfps_admin_menu_intigration() {
            global $GLOBALS;
            if ( empty( $GLOBALS['admin_page_hooks']['dots_store'] ) ) {
                add_menu_page(
                    'Dotstore Plugins',
                    __( 'Dotstore Plugins', 'free-product-sample' ),
                    'null',
                    'dots_store',
                    'dot_store_menu_page',
                    'dashicons-marker',
                    25
                );
            }
            add_submenu_page(
                'dots_store',
                'Product Sample',
                __( 'Product Sample', 'free-product-sample' ),
                'manage_options',
                'fps-sample-settings',
                array($this, 'dsfps_admin_settings_page')
            );
            add_submenu_page(
                'dots_store',
                'Getting Started',
                __( 'Getting Started', 'free-product-sample' ),
                'manage_options',
                'fps-get-started',
                array($this, 'dsfps_get_started_page')
            );
            add_submenu_page(
                'dots_store',
                'Get Premium',
                'Get Premium',
                'manage_options',
                'fps-upgrade-dashboard',
                array($this, 'dsfps_free_user_upgrade_page')
            );
        }

        /**
         * Add custom css for dotstore icon in admin area
         *
         * @since  1.1.3
         *
         */
        public function dsfps_dot_store_icon_css() {
            echo '<style>
		    .toplevel_page_dots_store .dashicons-marker::after{content:"";border:3px solid;position:absolute;top:14px;left:15px;border-radius:50%;opacity: 0.6;}
		    li.toplevel_page_dots_store:hover .dashicons-marker::after,li.toplevel_page_dots_store.current .dashicons-marker::after{opacity: 1;}
		    @media only screen and (max-width: 960px){
		    	.toplevel_page_dots_store .dashicons-marker::after{left:14px;}
		    }
		  	</style>';
        }

        /**
         * Quick guide page
         *
         * @since    1.0.0
         */
        public function dsfps_get_started_page() {
            require_once plugin_dir_path( __FILE__ ) . 'partials/dsfps-get-started-page.php';
        }

        /**
         * Plugin information page
         *
         * @since    1.0.0
         */
        public function dsfps_admin_settings_page() {
            require_once plugin_dir_path( __FILE__ ) . 'partials/dsfps-admin-settings-page.php';
        }

        /**
         * Premium version info page
         *
         */
        public function dsfps_free_user_upgrade_page() {
            require_once plugin_dir_path( __FILE__ ) . '/partials/dots-upgrade-dashboard.php';
        }

        /**
         * Remove submenu from admin screeen
         *
         * @since    1.0.0
         */
        public function dsfps_remove_admin_submenus() {
            remove_submenu_page( 'dots_store', 'dots_store' );
            remove_submenu_page( 'dots_store', 'fps-get-started' );
            remove_submenu_page( 'dots_store', 'fps-upgrade-dashboard' );
        }

        /**
         * Show admin footer review text.
         *
         * @since    1.0.0
         */
        public function dsfps_admin_footer_review() {
            $url = '';
            $url = esc_url( 'https://wordpress.org/plugins/free-product-sample/#reviews' );
            $html = sprintf( wp_kses( __( '<strong>We need your support</strong> to keep updating and improving the plugin. Please <a href="%1$s" target="_blank">help us by leaving a good review</a> :) Thanks!', 'free-product-sample' ), array(
                'strong' => array(),
                'a'      => array(
                    'href'   => array(),
                    'target' => 'blank',
                ),
            ) ), esc_url( $url ) );
            echo wp_kses_post( $html );
        }

        /**
         * Save For Later welcome page
         *
         * @since    1.0.0
         */
        public function dsfps_welcome_screen_do_activation_redirect() {
            // if no activation redirect
            if ( !get_transient( '_welcome_screen_activation_redirect_ds_product_sample' ) ) {
                return;
            }
            // Delete the redirect transient
            delete_transient( '_welcome_screen_activation_redirect_ds_product_sample' );
            // Redirect to save for later welcome  page
            wp_safe_redirect( add_query_arg( array(
                'page' => 'fps-sample-settings',
            ), admin_url( 'admin.php' ) ) );
            exit;
        }

        /**
         * Get product list for samples 
         *
         * @param array  $selected
         *
         * @return string $html
         * @uses   WC_Product::is_type()
         *
         * @since  1.1.1
         *
         * @uses   wc_get_product()
         */
        public function dsfps_get_simple_and_variation_product_options( $selected = array() ) {
            if ( isset( $selected ) && !empty( $selected ) ) {
                $posts_per_page = -1;
            } else {
                $posts_per_page = 10;
            }
            $product_query = new WP_Query(array(
                'post_type'      => 'product',
                'post_status'    => array('publish, draft'),
                'posts_per_page' => $posts_per_page,
                'post__in'       => $selected,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));
            $html = '';
            if ( isset( $product_query->posts ) && !empty( $product_query->posts ) ) {
                foreach ( $product_query->posts as $get_all_product ) {
                    $_product = wc_get_product( $get_all_product->ID );
                    if ( $_product->is_type( 'simple' ) || $_product->is_type( 'variable' ) ) {
                        $new_product_id = $get_all_product->ID;
                        $selected = array_map( 'intval', $selected );
                        $selectedVal = ( is_array( $selected ) && !empty( $selected ) && in_array( $new_product_id, $selected, true ) ? 'selected=selected' : '' );
                        $html .= '<option value="' . esc_attr( $new_product_id, 'free-product-sample' ) . '" ' . esc_attr( $selectedVal, 'free-product-sample' ) . '>' . esc_html( get_the_title( $new_product_id ), 'free-product-sample' ) . '</option>';
                    }
                }
            }
            return $html;
        }

        /**
         * Get product list on search for samples
         *
         * @return string $html
         * 
         * @uses   wc_get_product()
         * @uses   WC_Product::is_type()
         * @uses   dsfps_allowed_html_tags()
         *
         * @since  1.1.1
         *
         */
        public function dsfps_get_simple_and_variation_product_list_ajax() {
            // Security check
            check_ajax_referer( 'fps_ajax_request_nonce', 'security' );
            // Get products list
            $json = true;
            $filter_product_list = [];
            $request_value = filter_input( INPUT_GET, 'value', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $posts_per_page = filter_input( INPUT_GET, 'posts_per_page', FILTER_SANITIZE_NUMBER_INT );
            $_page = filter_input( INPUT_GET, '_page', FILTER_SANITIZE_NUMBER_INT );
            $post_value = ( isset( $request_value ) ? sanitize_text_field( $request_value ) : '' );
            $new_product_ids = array();
            function dsfps_posts_where(  $where, $wp_query  ) {
                global $wpdb;
                $search_term = $wp_query->get( 'search_pro_title' );
                if ( isset( $search_term ) ) {
                    $search_term_like = $wpdb->esc_like( $search_term );
                    $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $search_term_like ) . '%\'';
                }
                return $where;
            }

            $product_args = array(
                'post_type'        => 'product',
                'posts_per_page'   => $posts_per_page,
                'offset'           => ($_page - 1) * $posts_per_page,
                'search_pro_title' => $post_value,
                'post_status'      => array('publish', 'draft'),
                'orderby'          => 'title',
                'order'            => 'ASC',
            );
            add_filter(
                'posts_where',
                'dsfps_posts_where',
                10,
                2
            );
            $get_wp_query = new WP_Query($product_args);
            remove_filter(
                'posts_where',
                'dsfps_posts_where',
                10,
                2
            );
            $get_all_products = $get_wp_query->posts;
            if ( isset( $get_all_products ) && !empty( $get_all_products ) ) {
                foreach ( $get_all_products as $get_all_product ) {
                    $_product = wc_get_product( $get_all_product->ID );
                    if ( $_product->is_type( 'simple' ) || $_product->is_type( 'variable' ) ) {
                        $new_product_ids[] = $get_all_product->ID;
                    }
                }
            }
            $html = '';
            if ( isset( $new_product_ids ) && !empty( $new_product_ids ) ) {
                foreach ( $new_product_ids as $new_product_id ) {
                    $html .= '<option value="' . esc_attr( $new_product_id ) . '">' . '#' . esc_html( $new_product_id ) . ' - ' . esc_html( get_the_title( $new_product_id ) ) . '</option>';
                    $filter_product_list[] = array($new_product_id, get_the_title( $new_product_id ));
                }
            }
            if ( $json ) {
                echo wp_json_encode( $filter_product_list );
                wp_die();
            }
            echo wp_kses( $html, $this->dsfps_allowed_html_tags() );
            wp_die();
        }

        /**
         * Get users list on search AJAX
         *
         * @since 1.2.0
         *
         */
        public function dsfps_get_users_list_ajax() {
            // Security check
            check_ajax_referer( 'fps_ajax_request_nonce', 'security' );
            // Get users list
            $json = true;
            $filter_user_list = [];
            $request_value = filter_input( INPUT_GET, 'value', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $posts_per_page = filter_input( INPUT_GET, 'posts_per_page', FILTER_SANITIZE_NUMBER_INT );
            $_page = filter_input( INPUT_GET, '_page', FILTER_SANITIZE_NUMBER_INT );
            $post_value = ( isset( $request_value ) ? sanitize_text_field( $request_value ) : '' );
            $users_args = array(
                'number'         => $posts_per_page,
                'offset'         => ($_page - 1) * $posts_per_page,
                'search'         => '*' . $post_value . '*',
                'search_columns' => array('user_login'),
                'orderby'        => 'user_login',
                'order'          => 'ASC',
            );
            $get_all_users = get_users( $users_args );
            $html = '';
            if ( isset( $get_all_users ) && !empty( $get_all_users ) ) {
                foreach ( $get_all_users as $get_all_user ) {
                    $html .= '<option value="' . esc_attr( $get_all_user->data->user_login ) . '">' . esc_html( $get_all_user->data->user_login ) . '</option>';
                    $filter_user_list[] = array($get_all_user->data->user_login, $get_all_user->data->user_login);
                }
            }
            if ( $json ) {
                echo wp_json_encode( $filter_user_list );
                wp_die();
            }
            echo wp_kses( $html, $this->dsfps_allowed_html_tags() );
            wp_die();
        }

        /**
         * Get users list
         *
         * @param array  $selected
         *
         * @return string or array $html
         * @since 1.2.0
         *
         */
        public function dsfps_get_users_list( $selected = array() ) {
            $userIDs = array();
            if ( isset( $selected ) && !empty( $selected ) ) {
                $posts_per_page = -1;
                foreach ( $selected as $user_slug ) {
                    $user = get_user_by( 'slug', $user_slug );
                    if ( $user ) {
                        $userIDs[] = $user->ID;
                    }
                }
            } else {
                $posts_per_page = 10;
            }
            $get_users = array(
                'include' => $userIDs,
                'number'  => $posts_per_page,
            );
            $get_all_users = get_users( $get_users );
            $html = '';
            if ( isset( $get_all_users ) && !empty( $get_all_users ) ) {
                foreach ( $get_all_users as $get_all_user ) {
                    $selectedVal = ( is_array( $selected ) && !empty( $selected ) && in_array( $get_all_user->data->user_login, $selected, true ) ? 'selected=selected' : '' );
                    $html .= '<option value="' . esc_attr( $get_all_user->data->user_login ) . '" ' . esc_attr( $selectedVal ) . '>' . esc_html( $get_all_user->data->user_login ) . '</option>';
                }
            }
            return $html;
        }

        /**
         * Insert sample products page
         *
         * @since 1.0.0
         */
        public function dsfps_add_sample_products_page() {
            // Initialize the page ID to -1. This indicates no action has been taken.
            $post_id = -1;
            // Setup the author, slug, and title for the post
            $author_id = 1;
            $slug = 'fps-sample-product';
            $title = 'Sample Products';
            // If the page doesn't already exist, then create it
            if ( null === get_page_by_path( '/fps-sample-product/', OBJECT, 'page' ) ) {
                // phpcs:ignore
                $post_id = wp_insert_post( array(
                    'comment_status' => 'closed',
                    'ping_status'    => 'closed',
                    'post_content'   => '[dsfps_sample_products_list]',
                    'post_author'    => $author_id,
                    'post_name'      => $slug,
                    'post_title'     => $title,
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                ) );
                update_option( 'dsfps_sample_page', $post_id );
            } else {
                $post_id = -2;
            }
        }

        /**
         * Update one type video flag
         *
         * @since 1.0.1
         */
        public function dsfps_update_video_guide_flag() {
            $get_video_flag = filter_input( INPUT_POST, 'video_flag', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $video_flag = ( !empty( $get_video_flag ) ? sanitize_text_field( wp_unslash( $get_video_flag ) ) : '' );
            if ( 'no_video' === $video_flag ) {
                update_option( 'fps_activation_flag_for_video', '0' );
            }
        }

        /**
         * Adding new column on admin orders listing page for sample orders
         *
         * @since 1.1.0
         */
        public function dsfps_sample_shop_order_type_column( $columns ) {
            $reordered_columns = array();
            // Inserting columns to a specific location
            foreach ( $columns as $key => $column ) {
                $reordered_columns[$key] = $column;
                if ( $key === 'order_status' ) {
                    // Inserting after "Status" column
                    $reordered_columns['fps-sample-order-column'] = __( 'Order Type', 'free-product-sample' );
                }
            }
            return $reordered_columns;
        }

        /**
         * Adding orders new column content
         *
         * @since 1.1.0
         */
        public function dsfps_sample_order_type_column_content( $column, $order_id ) {
            switch ( $column ) {
                case 'fps-sample-order-column':
                    if ( isset( $order_id ) && !empty( $order_id ) ) {
                        $order = wc_get_order( $order_id );
                        $items = [];
                        if ( isset( $order ) && !empty( $order ) ) {
                            $items = $order->get_items();
                        }
                        $sample_meta_arr = [];
                        if ( isset( $items ) && is_array( $items ) ) {
                            foreach ( $items as $item ) {
                                $sample_meta_arr[] = $item->get_meta( 'PRODUCT_TYPE', true );
                            }
                        }
                        $sample_key = $this->dsfps_sample_prefix_for_order_meta();
                        if ( in_array( $sample_key, $sample_meta_arr, true ) || in_array( 'Sample', $sample_meta_arr, true ) || in_array( 'Probe', $sample_meta_arr, true ) ) {
                            echo esc_html__( 'Sample', 'free-product-sample' );
                        } else {
                            echo esc_html__( 'Normal', 'free-product-sample' );
                        }
                    }
                    break;
            }
        }

        /**
         * Sync product with global settings when block editor is enable. 
         *
         * @since      1.3.1
         * @param      int $product_id
         */
        public function dsfps_sample_products_sync_product_with_global( $product_id ) {
            if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                $get_dsfps_make_it_sample = get_post_meta( $product_id, 'dsfps_make_it_sample', true );
                $fps_select_product_list = get_option( 'fps_select_product_list' );
                if ( isset( $get_dsfps_make_it_sample ) && 'yes' === $get_dsfps_make_it_sample ) {
                    if ( is_array( $fps_select_product_list ) ) {
                        if ( !in_array( strval( $product_id ), $fps_select_product_list, true ) ) {
                            $fps_select_product_list[] = strval( $product_id );
                            update_option( 'fps_select_product_list', $fps_select_product_list );
                        }
                    }
                    update_post_meta( $product_id, 'dsfps_make_it_sample', $get_dsfps_make_it_sample );
                } else {
                    if ( is_array( $fps_select_product_list ) ) {
                        $key = array_search( strval( $product_id ), $fps_select_product_list, true );
                        if ( $key !== false ) {
                            unset($fps_select_product_list[$key]);
                            update_option( 'fps_select_product_list', $fps_select_product_list );
                        }
                    }
                    update_post_meta( $product_id, 'dsfps_make_it_sample', '' );
                }
            }
        }

        /**
         * Save sample product settings data
         *
         * @since 1.0.0
         */
        public function dsfps_save_sample_prod_settings() {
            // Security check
            check_ajax_referer( 'fps_ajax_request_nonce', 'security' );
            // Save sample settings
            $get_fps_settings_enable_disable = filter_input( INPUT_POST, 'fps_settings_enable_disable', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_settings_button_label = filter_input( INPUT_POST, 'fps_settings_button_label', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_settings_hide_on_shop = filter_input( INPUT_POST, 'fps_settings_hide_on_shop', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_settings_hide_on_category = filter_input( INPUT_POST, 'fps_settings_hide_on_category', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_settings_include_store_menu = filter_input( INPUT_POST, 'fps_settings_include_store_menu', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_sample_enable_type = filter_input( INPUT_POST, 'fps_sample_enable_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_select_product_list = filter_input(
                INPUT_POST,
                'fps_select_product_list',
                FILTER_SANITIZE_NUMBER_INT,
                FILTER_REQUIRE_ARRAY
            );
            $get_fps_sample_price_type = filter_input( INPUT_POST, 'fps_sample_price_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_sample_flat_price = filter_input( INPUT_POST, 'fps_sample_flat_price', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_sample_quantity_type = filter_input( INPUT_POST, 'fps_sample_quantity_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_quantity_per_product = filter_input( INPUT_POST, 'fps_quantity_per_product', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_quantity_per_order = filter_input( INPUT_POST, 'fps_quantity_per_order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_max_quantity_message = filter_input( INPUT_POST, 'fps_max_quantity_message', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_max_quantity_per_order_msg = filter_input( INPUT_POST, 'fps_max_quantity_per_order_msg', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_select_users_list = filter_input(
                INPUT_POST,
                'fps_select_users_list',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_REQUIRE_ARRAY
            );
            $get_fps_sample_button_color = filter_input( INPUT_POST, 'fps_sample_button_color', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_sample_button_bg_color = filter_input( INPUT_POST, 'fps_sample_button_bg_color', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_ajax_enable_disable = filter_input( INPUT_POST, 'fps_ajax_enable_disable', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_ajax_enable_dialog = filter_input( INPUT_POST, 'fps_ajax_enable_dialog', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $get_fps_ajax_sucess_message = filter_input( INPUT_POST, 'fps_ajax_sucess_message', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $fps_settings_enable_disable = ( !empty( $get_fps_settings_enable_disable ) ? sanitize_text_field( wp_unslash( $get_fps_settings_enable_disable ) ) : '' );
            $fps_settings_button_label = ( !empty( $get_fps_settings_button_label ) ? sanitize_text_field( wp_unslash( $get_fps_settings_button_label ) ) : '' );
            $fps_settings_hide_on_shop = ( !empty( $get_fps_settings_hide_on_shop ) ? sanitize_text_field( wp_unslash( $get_fps_settings_hide_on_shop ) ) : '' );
            $fps_settings_hide_on_category = ( !empty( $get_fps_settings_hide_on_category ) ? sanitize_text_field( wp_unslash( $get_fps_settings_hide_on_category ) ) : '' );
            $fps_settings_include_store_menu = ( !empty( $get_fps_settings_include_store_menu ) ? sanitize_text_field( wp_unslash( $get_fps_settings_include_store_menu ) ) : '' );
            $fps_sample_enable_type = ( !empty( $get_fps_sample_enable_type ) ? sanitize_text_field( wp_unslash( $get_fps_sample_enable_type ) ) : '' );
            $fps_select_product_list = ( isset( $get_fps_select_product_list ) ? array_map( 'sanitize_text_field', $get_fps_select_product_list ) : array() );
            $fps_sample_price_type = ( !empty( $get_fps_sample_price_type ) ? sanitize_text_field( wp_unslash( $get_fps_sample_price_type ) ) : '' );
            $fps_sample_flat_price = ( !empty( $get_fps_sample_flat_price ) ? sanitize_text_field( wp_unslash( $get_fps_sample_flat_price ) ) : '' );
            $fps_sample_quantity_type = ( !empty( $get_fps_sample_quantity_type ) ? sanitize_text_field( wp_unslash( $get_fps_sample_quantity_type ) ) : '' );
            $fps_quantity_per_product = ( !empty( $get_fps_quantity_per_product ) ? sanitize_text_field( wp_unslash( $get_fps_quantity_per_product ) ) : '' );
            $fps_quantity_per_order = ( !empty( $get_fps_quantity_per_order ) ? sanitize_text_field( wp_unslash( $get_fps_quantity_per_order ) ) : '' );
            $fps_max_quantity_message = ( !empty( $get_fps_max_quantity_message ) ? sanitize_text_field( wp_unslash( $get_fps_max_quantity_message ) ) : '' );
            $fps_max_quantity_per_order_msg = ( !empty( $get_fps_max_quantity_per_order_msg ) ? sanitize_text_field( wp_unslash( $get_fps_max_quantity_per_order_msg ) ) : '' );
            $fps_select_users_list = ( isset( $get_fps_select_users_list ) ? array_map( 'sanitize_text_field', $get_fps_select_users_list ) : array() );
            $fps_sample_button_color = ( !empty( $get_fps_sample_button_color ) ? sanitize_text_field( wp_unslash( $get_fps_sample_button_color ) ) : '' );
            $fps_sample_button_bg_color = ( !empty( $get_fps_sample_button_bg_color ) ? sanitize_text_field( wp_unslash( $get_fps_sample_button_bg_color ) ) : '' );
            $fps_ajax_enable_disable = ( !empty( $get_fps_ajax_enable_disable ) ? sanitize_text_field( wp_unslash( $get_fps_ajax_enable_disable ) ) : '' );
            $fps_ajax_enable_dialog = ( !empty( $get_fps_ajax_enable_dialog ) ? sanitize_text_field( wp_unslash( $get_fps_ajax_enable_dialog ) ) : '' );
            $fps_ajax_sucess_message = ( !empty( $get_fps_ajax_sucess_message ) ? sanitize_text_field( wp_unslash( $get_fps_ajax_sucess_message ) ) : '' );
            if ( isset( $fps_settings_enable_disable ) && !empty( $fps_settings_enable_disable ) ) {
                update_option( 'fps_settings_enable_disable', $fps_settings_enable_disable );
            } else {
                update_option( 'fps_settings_enable_disable', '' );
            }
            if ( isset( $fps_settings_button_label ) && !empty( $fps_settings_button_label ) ) {
                update_option( 'fps_settings_button_label', $fps_settings_button_label );
            } else {
                update_option( 'fps_settings_button_label', '' );
            }
            if ( isset( $fps_settings_hide_on_shop ) && !empty( $fps_settings_hide_on_shop ) ) {
                update_option( 'fps_settings_hide_on_shop', $fps_settings_hide_on_shop );
            } else {
                update_option( 'fps_settings_hide_on_shop', '' );
            }
            if ( isset( $fps_settings_hide_on_category ) && !empty( $fps_settings_hide_on_category ) ) {
                update_option( 'fps_settings_hide_on_category', $fps_settings_hide_on_category );
            } else {
                update_option( 'fps_settings_hide_on_category', '' );
            }
            if ( isset( $fps_settings_include_store_menu ) && !empty( $fps_settings_include_store_menu ) ) {
                update_option( 'fps_settings_include_store_menu', $fps_settings_include_store_menu );
            } else {
                update_option( 'fps_settings_include_store_menu', '' );
            }
            if ( isset( $fps_sample_enable_type ) && !empty( $fps_sample_enable_type ) ) {
                update_option( 'fps_sample_enable_type', $fps_sample_enable_type );
            } else {
                update_option( 'fps_sample_enable_type', '' );
            }
            $dsfps_select_product_list = get_option( 'fps_select_product_list' );
            if ( isset( $fps_select_product_list ) && !empty( $fps_select_product_list ) && is_array( $fps_select_product_list ) ) {
                foreach ( $fps_select_product_list as $product_id ) {
                    update_post_meta( $product_id, 'dsfps_make_it_sample', 'yes' );
                }
                if ( isset( $dsfps_select_product_list ) && !empty( $dsfps_select_product_list ) ) {
                    $removed_product_ids = array_diff( $dsfps_select_product_list, $fps_select_product_list );
                    if ( !empty( $removed_product_ids ) && is_array( $removed_product_ids ) ) {
                        foreach ( $removed_product_ids as $removed_product_id ) {
                            update_post_meta( $removed_product_id, 'dsfps_make_it_sample', '' );
                        }
                    }
                }
                update_option( 'fps_select_product_list', $fps_select_product_list );
            } else {
                update_option( 'fps_select_product_list', array() );
            }
            if ( isset( $fps_sample_price_type ) && !empty( $fps_sample_price_type ) ) {
                update_option( 'fps_sample_price_type', $fps_sample_price_type );
            } else {
                update_option( 'fps_sample_price_type', '' );
            }
            if ( isset( $fps_sample_flat_price ) && !empty( $fps_sample_flat_price ) ) {
                update_option( 'fps_sample_flat_price', $fps_sample_flat_price );
            } else {
                update_option( 'fps_sample_flat_price', '' );
            }
            if ( isset( $fps_sample_quantity_type ) && !empty( $fps_sample_quantity_type ) ) {
                update_option( 'fps_sample_quantity_type', $fps_sample_quantity_type );
            } else {
                update_option( 'fps_sample_quantity_type', '' );
            }
            if ( isset( $fps_quantity_per_product ) && !empty( $fps_quantity_per_product ) ) {
                update_option( 'fps_quantity_per_product', $fps_quantity_per_product );
            } else {
                update_option( 'fps_quantity_per_product', '' );
            }
            if ( isset( $fps_quantity_per_order ) && !empty( $fps_quantity_per_order ) ) {
                update_option( 'fps_quantity_per_order', $fps_quantity_per_order );
            } else {
                update_option( 'fps_quantity_per_order', '' );
            }
            if ( isset( $fps_max_quantity_message ) && !empty( $fps_max_quantity_message ) ) {
                update_option( 'fps_max_quantity_message', $fps_max_quantity_message );
            } else {
                update_option( 'fps_max_quantity_message', '' );
            }
            if ( isset( $fps_max_quantity_per_order_msg ) && !empty( $fps_max_quantity_per_order_msg ) ) {
                update_option( 'fps_max_quantity_per_order_msg', $fps_max_quantity_per_order_msg );
            } else {
                update_option( 'fps_max_quantity_per_order_msg', '' );
            }
            if ( isset( $fps_select_users_list ) && !empty( $fps_select_users_list ) ) {
                update_option( 'fps_select_users_list', $fps_select_users_list );
            } else {
                update_option( 'fps_select_users_list', array() );
            }
            if ( isset( $fps_sample_button_color ) && !empty( $fps_sample_button_color ) ) {
                update_option( 'fps_sample_button_color', $fps_sample_button_color );
            } else {
                update_option( 'fps_sample_button_color', '' );
            }
            if ( isset( $fps_sample_button_bg_color ) && !empty( $fps_sample_button_bg_color ) ) {
                update_option( 'fps_sample_button_bg_color', $fps_sample_button_bg_color );
            } else {
                update_option( 'fps_sample_button_bg_color', '' );
            }
            if ( isset( $fps_ajax_enable_disable ) && !empty( $fps_ajax_enable_disable ) ) {
                update_option( 'fps_ajax_enable_disable', $fps_ajax_enable_disable );
            } else {
                update_option( 'fps_ajax_enable_disable', '' );
            }
            if ( isset( $fps_ajax_enable_dialog ) && !empty( $fps_ajax_enable_dialog ) ) {
                update_option( 'fps_ajax_enable_dialog', $fps_ajax_enable_dialog );
            } else {
                update_option( 'fps_ajax_enable_dialog', '' );
            }
            if ( isset( $fps_ajax_sucess_message ) && !empty( $fps_ajax_sucess_message ) ) {
                update_option( 'fps_ajax_sucess_message', $fps_ajax_sucess_message );
            } else {
                update_option( 'fps_ajax_sucess_message', '' );
            }
            wp_die();
        }

        /**
         * Allow html tags
         *
         * @since 1.1.0
         */
        public function dsfps_allowed_html_tags() {
            $allowed_tags = array(
                'a'        => array(
                    'href'         => array(),
                    'title'        => array(),
                    'class'        => array(),
                    'target'       => array(),
                    'data-tooltip' => array(),
                ),
                'ul'       => array(
                    'class' => array(),
                ),
                'li'       => array(
                    'class' => array(),
                ),
                'div'      => array(
                    'class' => array(),
                    'id'    => array(),
                ),
                'select'   => array(
                    'rel-id'   => array(),
                    'id'       => array(),
                    'name'     => array(),
                    'class'    => array(),
                    'multiple' => array(),
                    'style'    => array(),
                ),
                'input'    => array(
                    'id'         => array(),
                    'value'      => array(),
                    'name'       => array(),
                    'class'      => array(),
                    'type'       => array(),
                    'data-index' => array(),
                ),
                'textarea' => array(
                    'id'    => array(),
                    'name'  => array(),
                    'class' => array(),
                ),
                'option'   => array(
                    'id'       => array(),
                    'selected' => array(),
                    'name'     => array(),
                    'value'    => array(),
                ),
                'br'       => array(),
                'p'        => array(),
                'b'        => array(
                    'style' => array(),
                ),
                'em'       => array(),
                'strong'   => array(),
                'i'        => array(
                    'class' => array(),
                ),
                'span'     => array(
                    'class' => array(),
                ),
                'small'    => array(
                    'class' => array(),
                ),
                'label'    => array(
                    'class' => array(),
                    'id'    => array(),
                    'for'   => array(),
                ),
                'td'       => array(
                    'id'    => array(),
                    'name'  => array(),
                    'class' => array(),
                    "stlye" => array(),
                ),
                'tr'       => array(
                    'id'    => array(),
                    'name'  => array(),
                    'class' => array(),
                ),
                'tbody'    => array(
                    'id'    => array(),
                    'name'  => array(),
                    'class' => array(),
                ),
                'table'    => array(
                    'id'    => array(),
                    'name'  => array(),
                    'class' => array(),
                    "stlye" => array(),
                ),
            );
            return $allowed_tags;
        }

        /**
         * Get sample prefix for order meta key
         *
         * @since      1.1.3
         */
        public function dsfps_sample_prefix_for_order_meta() {
            $sample = '';
            $sample = 'Sample';
            return $sample;
        }

        /**
         * Get dynamic promotional bar of plugin
         *
         * @param   String  $plugin_slug  slug of the plugin added in the site option
         * @since    1.2.0
         * 
         * @return  null
         */
        public function dsfps_get_promotional_bar( $plugin_slug = '' ) {
            $promotional_bar_upi_url = DSFPS_STORE_URL . 'wp-json/dpb-promotional-banner/v2/dpb-promotional-banner?' . wp_rand();
            $promotional_banner_request = wp_remote_get( $promotional_bar_upi_url );
            //phpcs:ignore
            if ( empty( $promotional_banner_request->errors ) ) {
                $promotional_banner_request_body = $promotional_banner_request['body'];
                $promotional_banner_request_body = json_decode( $promotional_banner_request_body, true );
                echo '<div class="dynamicbar_wrapper">';
                if ( !empty( $promotional_banner_request_body ) && is_array( $promotional_banner_request_body ) ) {
                    foreach ( $promotional_banner_request_body as $promotional_banner_request_body_data ) {
                        $promotional_banner_id = $promotional_banner_request_body_data['promotional_banner_id'];
                        $promotional_banner_cookie = $promotional_banner_request_body_data['promotional_banner_cookie'];
                        $promotional_banner_image = $promotional_banner_request_body_data['promotional_banner_image'];
                        $promotional_banner_description = $promotional_banner_request_body_data['promotional_banner_description'];
                        $promotional_banner_button_group = $promotional_banner_request_body_data['promotional_banner_button_group'];
                        $dpb_schedule_campaign_type = $promotional_banner_request_body_data['dpb_schedule_campaign_type'];
                        $promotional_banner_target_audience = $promotional_banner_request_body_data['promotional_banner_target_audience'];
                        if ( !empty( $promotional_banner_target_audience ) ) {
                            $plugin_keys = array();
                            if ( is_array( $promotional_banner_target_audience ) ) {
                                foreach ( $promotional_banner_target_audience as $list ) {
                                    $plugin_keys[] = $list['value'];
                                }
                            } else {
                                $plugin_keys[] = $promotional_banner_target_audience['value'];
                            }
                            $display_banner_flag = false;
                            if ( in_array( 'all_customers', $plugin_keys, true ) || in_array( $plugin_slug, $plugin_keys, true ) ) {
                                $display_banner_flag = true;
                            }
                        }
                        if ( true === $display_banner_flag ) {
                            if ( 'default' === $dpb_schedule_campaign_type ) {
                                $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                $banner_cookie_visible_once = filter_input( INPUT_COOKIE, 'banner_show_once_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                $flag = false;
                                if ( empty( $banner_cookie_show ) && empty( $banner_cookie_visible_once ) ) {
                                    setcookie( 'banner_show_' . $promotional_banner_cookie, 'yes', time() + 86400 * 7 );
                                    //phpcs:ignore
                                    setcookie( 'banner_show_once_' . $promotional_banner_cookie, 'yes' );
                                    //phpcs:ignore
                                    $flag = true;
                                }
                                $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                if ( !empty( $banner_cookie_show ) || true === $flag ) {
                                    $banner_cookie = filter_input( INPUT_COOKIE, 'banner_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                    $banner_cookie = ( isset( $banner_cookie ) ? $banner_cookie : '' );
                                    if ( empty( $banner_cookie ) && 'yes' !== $banner_cookie ) {
                                        ?>
	                            	<div class="dpb-popup <?php 
                                        echo ( isset( $promotional_banner_cookie ) ? esc_html( $promotional_banner_cookie ) : 'default-banner' );
                                        ?>">
	                                    <?php 
                                        if ( !empty( $promotional_banner_image ) ) {
                                            ?>
	                                        <img src="<?php 
                                            echo esc_url( $promotional_banner_image );
                                            ?>"/>
	                                        <?php 
                                        }
                                        ?>
	                                    <div class="dpb-popup-meta">
	                                        <p>
	                                            <?php 
                                        echo wp_kses_post( str_replace( array('<p>', '</p>'), '', $promotional_banner_description ) );
                                        if ( !empty( $promotional_banner_button_group ) ) {
                                            foreach ( $promotional_banner_button_group as $promotional_banner_button_group_data ) {
                                                ?>
	                                                    <a href="<?php 
                                                echo esc_url( $promotional_banner_button_group_data['promotional_banner_button_link'] );
                                                ?>" target="_blank"><?php 
                                                echo esc_html( $promotional_banner_button_group_data['promotional_banner_button_text'] );
                                                ?></a>
	                                                    <?php 
                                            }
                                        }
                                        ?>
	                                    	</p>
	                                    </div>
	                                    <a href="javascript:void(0);" data-bar-id="<?php 
                                        echo esc_attr( $promotional_banner_id );
                                        ?>" data-popup-name="<?php 
                                        echo ( isset( $promotional_banner_cookie ) ? esc_attr( $promotional_banner_cookie ) : 'default-banner' );
                                        ?>" class="dpbpop-close"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path id="Icon_material-close" data-name="Icon material-close" d="M17.5,8.507,16.493,7.5,12.5,11.493,8.507,7.5,7.5,8.507,11.493,12.5,7.5,16.493,8.507,17.5,12.5,13.507,16.493,17.5,17.5,16.493,13.507,12.5Z" transform="translate(-7.5 -7.5)" fill="#acacac"/></svg></a>
	                                </div>
	                                <?php 
                                    }
                                }
                            } else {
                                $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                $banner_cookie_visible_once = filter_input( INPUT_COOKIE, 'banner_show_once_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                $flag = false;
                                if ( empty( $banner_cookie_show ) && empty( $banner_cookie_visible_once ) ) {
                                    setcookie( 'banner_show_' . $promotional_banner_cookie, 'yes' );
                                    //phpcs:ignore
                                    setcookie( 'banner_show_once_' . $promotional_banner_cookie, 'yes' );
                                    //phpcs:ignore
                                    $flag = true;
                                }
                                $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                if ( !empty( $banner_cookie_show ) || true === $flag ) {
                                    $banner_cookie = filter_input( INPUT_COOKIE, 'banner_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                    $banner_cookie = ( isset( $banner_cookie ) ? $banner_cookie : '' );
                                    if ( empty( $banner_cookie ) && 'yes' !== $banner_cookie ) {
                                        ?>
	                    			<div class="dpb-popup <?php 
                                        echo ( isset( $promotional_banner_cookie ) ? esc_html( $promotional_banner_cookie ) : 'default-banner' );
                                        ?>">
	                                    <?php 
                                        if ( !empty( $promotional_banner_image ) ) {
                                            ?>
	                                            <img src="<?php 
                                            echo esc_url( $promotional_banner_image );
                                            ?>"/>
	                                        <?php 
                                        }
                                        ?>
	                                    <div class="dpb-popup-meta">
	                                        <p>
	                                            <?php 
                                        echo wp_kses_post( str_replace( array('<p>', '</p>'), '', $promotional_banner_description ) );
                                        if ( !empty( $promotional_banner_button_group ) ) {
                                            foreach ( $promotional_banner_button_group as $promotional_banner_button_group_data ) {
                                                ?>
	                                                    <a href="<?php 
                                                echo esc_url( $promotional_banner_button_group_data['promotional_banner_button_link'] );
                                                ?>" target="_blank"><?php 
                                                echo esc_html( $promotional_banner_button_group_data['promotional_banner_button_text'] );
                                                ?></a>
	                                                    <?php 
                                            }
                                        }
                                        ?>
	                                        </p>
	                                    </div>
	                                    <a href="javascript:void(0);" data-bar-id="<?php 
                                        echo esc_attr( $promotional_banner_id );
                                        ?>" data-popup-name="<?php 
                                        echo ( isset( $promotional_banner_cookie ) ? esc_html( $promotional_banner_cookie ) : 'default-banner' );
                                        ?>" class="dpbpop-close"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path id="Icon_material-close" data-name="Icon material-close" d="M17.5,8.507,16.493,7.5,12.5,11.493,8.507,7.5,7.5,8.507,11.493,12.5,7.5,16.493,8.507,17.5,12.5,13.507,16.493,17.5,17.5,16.493,13.507,12.5Z" transform="translate(-7.5 -7.5)" fill="#acacac"/></svg></a>
	                                </div>
	                                <?php 
                                    }
                                }
                            }
                        }
                    }
                }
                echo '</div>';
            }
        }

        /**
         * Get and save plugin setup wizard data
         * 
         * @since    3.9.3
         * 
         */
        public function dsfps_plugin_setup_wizard_submit() {
            check_ajax_referer( 'wizard_ajax_nonce', 'nonce' );
            $survey_list = filter_input( INPUT_GET, 'survey_list', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            if ( !empty( $survey_list ) && 'Select One' !== $survey_list ) {
                update_option( 'dsfps_where_hear_about_us', $survey_list );
            }
            wp_die();
        }

        /**
         * Send setup wizard data to sendinblue
         * 
         * @since    3.9.3
         * 
         */
        public function dsfps_send_wizard_data_after_plugin_activation() {
            $send_wizard_data = filter_input( INPUT_GET, 'send-wizard-data', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            if ( isset( $send_wizard_data ) && !empty( $send_wizard_data ) ) {
                if ( !get_option( 'dsfps_data_submited_in_sendiblue' ) ) {
                    $dsfps_where_hear = get_option( 'dsfps_where_hear_about_us' );
                    $get_user = fps_fs()->get_user();
                    $data_insert_array = array();
                    if ( isset( $get_user ) && !empty( $get_user ) ) {
                        $data_insert_array = array(
                            'user_email'              => $get_user->email,
                            'ACQUISITION_SURVEY_LIST' => $dsfps_where_hear,
                        );
                    }
                    $feedback_api_url = DSFPS_STORE_URL . 'wp-json/dotstore-sendinblue-data/v2/dotstore-sendinblue-data?' . wp_rand();
                    $query_url = $feedback_api_url . '&' . http_build_query( $data_insert_array );
                    if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
                        $response = vip_safe_wp_remote_get(
                            $query_url,
                            3,
                            1,
                            20
                        );
                    } else {
                        $response = wp_remote_get( $query_url );
                        // phpcs:ignore
                    }
                    if ( !is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
                        update_option( 'dsfps_data_submited_in_sendiblue', '1' );
                        delete_option( 'dsfps_where_hear_about_us' );
                    }
                }
            }
        }

    }

}