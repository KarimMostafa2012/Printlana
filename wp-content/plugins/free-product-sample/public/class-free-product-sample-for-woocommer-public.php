<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.multidots.com
 * @since      1.0.0
 *
 * @package    DSFPS_Free_Product_Sample_Pro
 * @subpackage DSFPS_Free_Product_Sample_Pro/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    DSFPS_Free_Product_Sample_Pro
 * @subpackage DSFPS_Free_Product_Sample_Pro/public
 * @author     Multidots <inquiry@multidots.in>
 */
if ( !class_exists( 'DSFPS_Free_Product_Sample_Pro_Public' ) ) {
    class DSFPS_Free_Product_Sample_Pro_Public {
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
         * @param string $plugin_name The name of the plugin.
         * @param string $version     The version of this plugin.
         *
         * @since    1.0.0
         */
        public function __construct( $plugin_name, $version ) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }

        /**
         * Register the scripts for the public area.
         *
         * @since    1.0.0
         *
         */
        public function dsfps_pro_enqueue_public_scripts() {
            $woo_ajax_status = get_option( 'woocommerce_enable_ajax_add_to_cart' );
            wp_enqueue_style(
                $this->plugin_name . 'public-css',
                plugin_dir_url( __FILE__ ) . 'css/free-product-sample-for-woocommer-public-style.css',
                array(),
                'all'
            );
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url( __FILE__ ) . 'js/free-product-sample-for-woocommerce-public.js',
                array(),
                $this->version,
                true
            );
            if ( 'yes' === $woo_ajax_status ) {
                // Load ajax script when block theme is enable and wc-ajax is not loaded.
                if ( !wp_script_is( 'woocommerce-add-to-cart', 'enqueued' ) && !wp_script_is( 'woocommerce-add-to-cart', 'registered' ) ) {
                    wp_enqueue_script(
                        'woocommerce-add-to-cart',
                        plugins_url( 'assets/js/frontend/add-to-cart.min.js', WC_PLUGIN_FILE ),
                        array('jquery'),
                        WC_VERSION,
                        true
                    );
                    wp_localize_script( 'woocommerce-add-to-cart', 'wc_add_to_cart_params', array(
                        'ajax_url'                => WC()->ajax_url(),
                        'wc_ajax_url'             => WC_AJAX::get_endpoint( "%%endpoint%%" ),
                        'i18n_view_cart'          => esc_attr__( 'View cart', 'free-product-sample' ),
                        'cart_url'                => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
                        'is_cart'                 => is_cart(),
                        'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add' ),
                    ) );
                }
                // Check if 'wc-cart-fragments' script is already enqueued or registered
                if ( !wp_script_is( 'wc-cart-fragments', 'enqueued' ) && wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
                    // Enqueue the 'wc-cart-fragments' script
                    wp_enqueue_script( 'wc-cart-fragments' );
                }
                wp_enqueue_script(
                    $this->plugin_name . 'product-ajax-js',
                    plugin_dir_url( __FILE__ ) . 'js/free-product-sample-ajax-add-to-cart-public.js',
                    array(),
                    $this->version,
                    true
                );
                // Get sample button label
                $fps_button_label = get_option( 'fps_settings_button_label' );
                $fps_sample_btn_text = ( isset( $fps_button_label ) && !empty( $fps_button_label ) ? $fps_button_label : __( 'Order a Sample', 'free-product-sample' ) );
                $fps_btn_final_text = str_replace( "{PRICE}", '', $fps_sample_btn_text );
                // Localize script
                wp_localize_script( $this->plugin_name . 'product-ajax-js', 'dsfps_coditional_vars', array(
                    'ajaxurl'          => admin_url( 'admin-ajax.php' ),
                    'cart_url'         => get_permalink( wc_get_page_id( 'cart' ) ),
                    'view_cart'        => __( 'View cart', 'free-product-sample' ),
                    'sample_btn_label' => esc_html( $fps_btn_final_text ),
                    'max_sample_qty'   => $this->dsfps_get_max_quantity_for_sample( get_the_ID() ),
                ) );
            }
            // Register script for mini cart customizations
            if ( !wp_script_is( $this->plugin_name . '-mini-cart-customizations', 'registered' ) ) {
                wp_register_script(
                    $this->plugin_name . '-mini-cart-customizations',
                    plugin_dir_url( __FILE__ ) . 'js/dsfps-mini-cart-customizations.js',
                    array('wp-hooks', 'wc-blocks-checkout'),
                    $this->version,
                    true
                );
            }
            // Enqueue our script
            wp_enqueue_script( $this->plugin_name . '-mini-cart-customizations' );
        }

        public function dsfps_get_max_quantity_for_sample( $product_id ) {
            $fps_sample_quantity_type = get_option( 'fps_sample_quantity_type' );
            $fps_quantity_per_product = get_option( 'fps_quantity_per_product' );
            $fps_quantity_per_order = get_option( 'fps_quantity_per_order' );
            $dsfps_quantity_limit_per_product = get_post_meta( $product_id, 'dsfps_quantity_limit_per_product', true );
            $max_sample_qty = '';
            if ( 'per_product' === $fps_sample_quantity_type && !empty( $fps_quantity_per_product ) || !empty( $dsfps_quantity_limit_per_product ) ) {
                $max_sample_qty = ( !empty( $dsfps_quantity_limit_per_product ) ? $dsfps_quantity_limit_per_product : $fps_quantity_per_product );
            } elseif ( 'per_order' === $fps_sample_quantity_type && !empty( $fps_quantity_per_order ) ) {
                $max_sample_qty = $fps_quantity_per_order;
            }
            return $max_sample_qty;
        }

        /**
         * get current user details
         *
         * @since    1.0.0
         *
         */
        public function dsfps_get_current_user_details() {
            if ( is_user_logged_in() ) {
                $user_data = wp_get_current_user();
                $current_user = $user_data->roles;
                $current_user[] = $user_data->user_login;
                return $current_user;
            } else {
                return array();
            }
        }

        /**
         * Function for insert sample product button on product details page
         * 
         * @since    1.0.0
         */
        public function dsfps_add_sample_product_button_for_prod_page() {
            global $product;
            $fps_settings_enable_disable = get_option( 'fps_settings_enable_disable' );
            $fps_settings_button_label = get_option( 'fps_settings_button_label' );
            $fps_sample_enable_type = get_option( 'fps_sample_enable_type' );
            $fps_select_product_list = get_option( 'fps_select_product_list' );
            $fps_select_users_list = get_option( 'fps_select_users_list' );
            $button_color = get_option( 'fps_sample_button_color' );
            $button_bg_color = get_option( 'fps_sample_button_bg_color' );
            $fps_select_product_list = array_map( 'intval', $fps_select_product_list );
            $sample_btn_style = '';
            if ( isset( $button_color ) && !empty( $button_color ) || isset( $button_bg_color ) && !empty( $button_bg_color ) ) {
                $sample_btn_style = 'color:' . $button_color . ';background:' . $button_bg_color . ';border-color:' . $button_bg_color . ';';
            }
            // get product id
            $product_id = $product->get_id();
            $product_obj = wc_get_product( $product_id );
            $current_products = $product_obj->get_children();
            $sample_price = '';
            if ( isset( $current_products ) && !empty( $current_products ) ) {
                foreach ( $current_products as $variation_id ) {
                    $sample_price = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
                }
            } else {
                $sample_price = $this->dsfps_set_sample_prod_price( $product_id );
            }
            $currency_symbol = get_woocommerce_currency_symbol();
            $final_sample_price = '';
            if ( isset( $currency_symbol ) && !empty( $currency_symbol ) ) {
                $final_sample_price = $currency_symbol . '<span id="fps-sample-btn-price">' . $sample_price . '</span>';
            } else {
                $final_sample_price = '<span id="fps-sample-btn-price">' . $sample_price . '</span>';
            }
            // add sample button
            $sample_button = '';
            $fps_sample_btn_text = ( isset( $fps_settings_button_label ) && !empty( $fps_settings_button_label ) ? $fps_settings_button_label : __( 'Order a Sample', 'free-product-sample' ) );
            $fps_sample_btn_final_text = str_replace( "{PRICE}", $final_sample_price, $fps_sample_btn_text );
            $fps_ajax_feature = get_option( 'fps_ajax_enable_disable' );
            $fps_ajax_flag = ( isset( $fps_ajax_feature ) && 'on' === $fps_ajax_feature ? 'yes' : 'no' );
            $ajax_class = ( $fps_ajax_flag === 'yes' ? 'dsfps-free-sample-ajax-btn' : 'dsfps-free-sample-submit-btn' );
            $on_ajax_disable_name = ( $fps_ajax_flag === 'yes' ? '-disable' : '' );
            wp_nonce_field( 'dsfps_add_to_cart_nounce', '_wpnonce' );
            echo '<input type="hidden" id="fps-ajax-add-to-cart-flag" value="' . esc_attr( $fps_ajax_flag, 'free-product-sample' ) . '">';
            if ( $product->is_type( 'simple' ) ) {
                echo '<input type="hidden" name="" class="dsfps-add-to-cart-id" id="dsfps-hidden-input" value="">';
                $sample_button = '<button type="button" name="dsfps-simple-add-to-cart' . $on_ajax_disable_name . '" value="' . get_the_ID() . '" class="single_add_to_cart_button button alt dsfps-free-sample-btn ' . $ajax_class . ' wp-element-button" style="' . $sample_btn_style . '">' . wp_kses( __( $fps_sample_btn_final_text, 'free-product-sample' ), array(
                    'span' => array(
                        'id' => array(),
                    ),
                ) ) . '</button>';
            } else {
                if ( $product->is_type( 'variable' ) ) {
                    echo '<input type="hidden" name="" class="dsfps-add-to-cart-id" id="dsfps-hidden-input" value="">';
                    $sample_button = '<button type="button" name="dsfps-variable-add-to-cart' . $on_ajax_disable_name . '" value="' . get_the_ID() . '" class="single_add_to_cart_button button alt dsfps-free-sample-btn dsfps-variable-add-to-cart ' . $ajax_class . ' wp-element-button" style="' . $sample_btn_style . '">' . wp_kses( __( $fps_sample_btn_final_text, 'free-product-sample' ), array(
                        'span' => array(
                            'id' => array(),
                        ),
                    ) ) . '</button>';
                }
            }
            $product_term_id = $product->get_category_ids();
            // get current user
            $user_data = $this->dsfps_get_current_user_details();
            $fps_users_restrict_flag = 0;
            if ( isset( $fps_select_users_list ) && is_array( $fps_select_users_list ) && !empty( $fps_select_users_list ) ) {
                if ( isset( $user_data ) && is_array( $user_data ) && !empty( $user_data ) ) {
                    if ( is_user_logged_in() ) {
                        if ( in_array( $user_data[1], $fps_select_users_list, true ) ) {
                            $fps_users_restrict_flag = 1;
                            // apply for users which is set by admin side
                        }
                    }
                }
            }
            $fps_products_restrict_flag = 0;
            if ( $fps_sample_enable_type === 'product_wise' ) {
                if ( isset( $fps_select_product_list ) && is_array( $fps_select_product_list ) && !empty( $fps_select_product_list ) ) {
                    if ( in_array( $product_id, $fps_select_product_list, true ) ) {
                        $fps_products_restrict_flag = 1;
                    }
                } else {
                    $fps_products_restrict_flag = 1;
                }
            }
            // conditions for sample button
            if ( $fps_settings_enable_disable === 'on' ) {
                if ( 1 === $fps_users_restrict_flag ) {
                    if ( 1 === $fps_products_restrict_flag ) {
                        echo wp_kses_post( $sample_button );
                    }
                } elseif ( empty( $fps_select_users_list ) ) {
                    if ( 1 === $fps_products_restrict_flag ) {
                        echo wp_kses_post( $sample_button );
                    }
                }
            }
        }

        /**
         * Function for insert sample product button on shop page
         * 
         * @since    1.0.0
         */
        public function dsfps_add_sample_product_button_for_shop_page() {
            global $product;
            $fps_settings_enable_disable = get_option( 'fps_settings_enable_disable' );
            $fps_settings_button_label = get_option( 'fps_settings_button_label' );
            $fps_sample_enable_type = get_option( 'fps_sample_enable_type' );
            $fps_select_product_list = get_option( 'fps_select_product_list' );
            $fps_select_users_list = get_option( 'fps_select_users_list' );
            $fps_settings_hide_on_shop = get_option( 'fps_settings_hide_on_shop' );
            $fps_settings_hide_on_category = get_option( 'fps_settings_hide_on_category' );
            $button_color = get_option( 'fps_sample_button_color' );
            $button_bg_color = get_option( 'fps_sample_button_bg_color' );
            $fps_select_product_list = array_map( 'intval', $fps_select_product_list );
            $sample_btn_style = '';
            if ( isset( $button_color ) && !empty( $button_color ) || isset( $button_bg_color ) && !empty( $button_bg_color ) ) {
                $sample_btn_style = 'color:' . $button_color . ';background:' . $button_bg_color . ';border-color:' . $button_bg_color . ';';
            }
            // get product id
            $product_id = $product->get_id();
            $sample_price = $this->dsfps_set_sample_prod_price( $product_id );
            $currency_symbol = get_woocommerce_currency_symbol();
            $final_sample_price = '';
            if ( isset( $currency_symbol ) && !empty( $currency_symbol ) ) {
                $final_sample_price = $currency_symbol . '<span id="fps-sample-btn-price">' . $sample_price . '</span>';
            } else {
                $final_sample_price = '<span id="fps-sample-btn-price">' . $sample_price . '</span>';
            }
            // add sample button
            $sample_button = '';
            $fps_sample_btn_text = ( isset( $fps_settings_button_label ) && !empty( $fps_settings_button_label ) ? $fps_settings_button_label : 'Order a Sample' );
            $fps_sample_btn_final_text = str_replace( "{PRICE}", $final_sample_price, $fps_sample_btn_text );
            $manage_sample_stock = get_post_meta( $product_id, 'dsfps_manage_stock', true );
            $get_sample_stocks = get_post_meta( $product_id, 'dsfps_stock', true );
            $sample_max_qty = '';
            if ( isset( $manage_sample_stock ) && !empty( $manage_sample_stock ) ) {
                if ( isset( $get_sample_stocks ) ) {
                    $sample_max_qty = $get_sample_stocks;
                }
            }
            if ( is_product_tag() ) {
                return;
            }
            // Check if product is purchasable and in stock
            if ( !$product->is_purchasable() || !$product->is_in_stock() ) {
                return;
            }
            if ( $product->is_type( 'simple' ) ) {
                if ( isset( $sample_max_qty ) && '0' === $sample_max_qty ) {
                    $fps_title_type = get_option( 'fps_product_title_type' );
                    $fps_prefix_title = get_option( 'fps_product_prefix_title' );
                    $fps_suffix_title = get_option( 'fps_product_suffix_title' );
                    $fps_sample_btn_final_text = '';
                    if ( isset( $fps_title_type ) && $fps_title_type === 'prefix' ) {
                        if ( isset( $fps_prefix_title ) && !empty( $fps_prefix_title ) ) {
                            $fps_sample_btn_final_text = esc_html__( $fps_prefix_title . ' - Out of stock', 'free-product-sample' );
                        } else {
                            $fps_sample_btn_final_text = esc_html__( 'Sample - Out of stock', 'free-product-sample' );
                        }
                    } elseif ( isset( $fps_title_type ) && $fps_title_type === 'suffix' ) {
                        if ( isset( $fps_suffix_title ) && !empty( $fps_suffix_title ) ) {
                            $fps_sample_btn_final_text = esc_html__( $fps_suffix_title . ' - Out of stock', 'free-product-sample' );
                        } else {
                            $fps_sample_btn_final_text = esc_html__( 'Sample - Out of stock', 'free-product-sample' );
                        }
                    } else {
                        $fps_sample_btn_final_text = esc_html__( 'Sample - Out of stock', 'free-product-sample' );
                    }
                    $fps_sample_btn_final_text = apply_filters( 'dsfps_sample_out_of_stock_btn_label', $fps_sample_btn_final_text );
                    $sample_button = '<a href="' . $product->get_permalink() . '" data-quantity="1" name="dsfps-simple-add-to-cart-disable" value="' . get_the_ID() . '" class="button product_type_simple dsfps_ajax_add_to_cart ajax_add_to_cart wp-element-button" data-product_id="' . get_the_ID() . '" rel="nofollow" style="' . $sample_btn_style . '">' . wp_kses( __( $fps_sample_btn_final_text, 'free-product-sample' ), array(
                        'span' => array(
                            'id' => array(),
                        ),
                    ) ) . '</a>';
                } else {
                    $sample_button = '<a href="?add-to-cart=' . get_the_ID() . '" data-quantity="1" name="dsfps-simple-add-to-cart-disable" value="' . get_the_ID() . '" class="button product_type_simple dsfps_ajax_add_to_cart ajax_add_to_cart wp-element-button" data-product_id="' . get_the_ID() . '" rel="nofollow" style="' . $sample_btn_style . '">' . wp_kses( __( $fps_sample_btn_final_text, 'free-product-sample' ), array(
                        'span' => array(
                            'id' => array(),
                        ),
                    ) ) . '</a>';
                }
            }
            $product_term_id = $product->get_category_ids();
            // get current user
            $user_data = $this->dsfps_get_current_user_details();
            $fps_users_restrict_flag = 0;
            if ( isset( $fps_select_users_list ) && is_array( $fps_select_users_list ) && !empty( $fps_select_users_list ) ) {
                if ( isset( $user_data ) && is_array( $user_data ) && !empty( $user_data ) ) {
                    if ( is_user_logged_in() ) {
                        if ( in_array( $user_data[1], $fps_select_users_list, true ) ) {
                            $fps_users_restrict_flag = 1;
                            // apply for users which is set by admin side
                        }
                    }
                }
            }
            $fps_products_restrict_flag = 0;
            if ( $fps_sample_enable_type === 'product_wise' ) {
                if ( isset( $fps_select_product_list ) && is_array( $fps_select_product_list ) && !empty( $fps_select_product_list ) ) {
                    if ( in_array( $product_id, $fps_select_product_list, true ) ) {
                        $fps_products_restrict_flag = 1;
                    }
                } else {
                    $fps_products_restrict_flag = 1;
                }
            }
            // Allow users to filter custom conditions for hiding the sample button
            $hide_button = apply_filters( 'fps_hide_sample_button_condition_for_archive', false );
            // conditions for sample button
            if ( $fps_settings_enable_disable === 'on' ) {
                if ( $fps_settings_hide_on_shop === 'on' && $fps_settings_hide_on_category === 'on' ) {
                    if ( is_product_category() || is_shop() || $hide_button ) {
                        return;
                    }
                    if ( 1 === $fps_users_restrict_flag ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    } elseif ( empty( $fps_select_users_list ) ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    }
                } elseif ( $fps_settings_hide_on_category === 'on' ) {
                    if ( is_product_category() || $hide_button ) {
                        return;
                    }
                    if ( 1 === $fps_users_restrict_flag ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    } elseif ( empty( $fps_select_users_list ) ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    }
                } elseif ( $fps_settings_hide_on_shop === 'on' ) {
                    if ( is_shop() || $hide_button ) {
                        return;
                    }
                    if ( 1 === $fps_users_restrict_flag ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    } elseif ( empty( $fps_select_users_list ) ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    }
                } else {
                    if ( $hide_button ) {
                        return;
                    }
                    if ( 1 === $fps_users_restrict_flag ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    } elseif ( empty( $fps_select_users_list ) ) {
                        if ( 1 === $fps_products_restrict_flag ) {
                            echo wp_kses_post( $sample_button );
                        }
                    }
                }
            }
        }

        /**
         * Function for insert sample product button
         * 
         * @since    1.1.1
         */
        public function dsfps_load_sample_button_after_setup_theme() {
            $position_hook = '';
            $position_hook = 'woocommerce_after_add_to_cart_button';
            $fps_button_location = apply_filters( 'dsfps_single_product_button_hook', array($position_hook, 100) );
            add_action( $fps_button_location[0], array($this, 'dsfps_add_sample_product_button_for_prod_page'), $fps_button_location[1] );
            add_action( 'woocommerce_after_shop_loop_item', array($this, 'dsfps_add_sample_product_button_for_shop_page') );
            if ( in_array( 'yith-woocommerce-added-to-cart-popup-premium/init.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
                add_action( 'yith_wacp_after_related_item', array($this, 'dsfps_add_sample_product_button_for_shop_page') );
            }
            // Added filter code for cart page sample restriction
            $hook_for_cart_qty_restriction = apply_filters( 'dsfps_wc_check_cart_items', 'woocommerce_check_cart_items' );
            add_action( $hook_for_cart_qty_restriction, array($this, 'dsfps_validate_product_max_quantity') );
        }

        /**
         * Add prefix and suffix to product name on cart/checkout block.
         * 
         * @since    1.4.0
         */
        /**
         * Register Store API data for sample products
         *
         * @since    1.0.0
         */
        private function dsfps_register_store_api_data() {
            woocommerce_store_api_register_endpoint_data( array(
                'endpoint'        => Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
                'namespace'       => 'dsfps_free_product_sample',
                'data_callback'   => function ( $cart_item ) {
                    $product = $cart_item['data'];
                    $product_name = $product->get_name();
                    $is_sample = !empty( $cart_item['fps_free_sample'] );
                    return array(
                        'dsfps_product_type' => ( $is_sample ? 'sample' : '' ),
                        'name'               => ( $is_sample ? $this->dsfps_get_sample_name_with_prefix( $product_name ) : $product_name ),
                    );
                },
                'schema_callback' => function () {
                    return array(
                        'dsfps_product_type' => array(
                            'description' => __( 'Whether this is a sample product', 'free-product-sample' ),
                            'type'        => 'string',
                            'readonly'    => true,
                        ),
                        'name'               => array(
                            'description' => __( 'Product name with sample prefix if applicable', 'free-product-sample' ),
                            'type'        => 'string',
                            'readonly'    => true,
                        ),
                    );
                },
                'schema_type'     => ARRAY_A,
            ) );
        }

        /**
         * Add script dependencies for WooCommerce blocks
         *
         * @param array  $dependencies Array of script dependencies.
         * @param string $script_handle Script handle being filtered.
         * @return array Modified dependencies.
         */
        public function dsfps_add_block_script_dependencies( $dependencies, $script_handle ) {
            if ( in_array( $script_handle, array('wc-cart-block-frontend', 'wc-checkout-block-frontend', 'wc-mini-cart-block-frontend'), true ) ) {
                $dependencies[] = $this->plugin_name . '-mini-cart-customizations';
            }
            return $dependencies;
        }

        /**
         * Initialize blocks integration for sample products
         *
         * @since    1.0.0
         */
        public function dsfps_block_prepend_text_to_cart_item() {
            // Register Store API data
            $this->dsfps_register_store_api_data();
            // Add script dependencies
            add_filter(
                'woocommerce_blocks_register_script_dependencies',
                array($this, 'dsfps_add_block_script_dependencies'),
                10,
                2
            );
        }

        /**
         * Handle add to cart
         *
         * @since 1.0.0
         * @param string
         */
        public function dsfps_add_to_cart_action( $url = false ) {
            $nonce_action = 'dsfps_add_to_cart_nounce';
            if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                return;
            }
            if ( !isset( $_REQUEST['dsfps-simple-add-to-cart'] ) || !is_numeric( wp_unslash( sanitize_text_field( $_REQUEST['dsfps-simple-add-to-cart'] ) ) ) ) {
                return;
            }
            wc_nocache_headers();
            $product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( wp_unslash( $_REQUEST['dsfps-simple-add-to-cart'] ) ) );
            $was_added_to_cart = false;
            $adding_to_cart = wc_get_product( $product_id );
            if ( !$adding_to_cart ) {
                return;
            }
            $add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );
            if ( 'variable' === $add_to_cart_handler ) {
                $was_added_to_cart = $this->dsfps_add_to_cart_handler_variable( $product_id );
            } elseif ( 'simple' === $add_to_cart_handler ) {
                $was_added_to_cart = $this->dsfps_add_to_cart_handler_simple( $product_id );
            }
            // If we added the product to the cart we can now optionally do a redirect.
            if ( $was_added_to_cart && 0 === wc_notice_count( 'error' ) ) {
                $url = apply_filters( 'woocommerce_add_to_cart_redirect', $url, $adding_to_cart );
                if ( $url ) {
                    wp_safe_redirect( $url );
                    exit;
                } elseif ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
                    wp_safe_redirect( wc_get_cart_url() );
                    exit;
                }
            }
        }

        /**
         * Handle adding simple products to the cart.
         *
         * @since 1.0.0
         * @param int $product_id Product ID to add to the cart.
         * @return bool success or not
         */
        private function dsfps_add_to_cart_handler_simple( $product_id ) {
            $nonce_action = 'dsfps_add_to_cart_nounce';
            if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                return;
            }
            $quantity = ( empty( $_REQUEST['quantity'] ) ? 1 : wp_unslash( absint( $_REQUEST['quantity'] ) ) );
            $passed_validation = apply_filters(
                'woocommerce_add_to_cart_validation',
                true,
                $product_id,
                $quantity
            );
            $cart_item_data = array(
                'fps_sample_items' => 'sample',
            );
            $variation_id = 0;
            $variation = array();
            $in_cart = false;
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product_in_cart = $cart_item['product_id'];
                if ( $product_in_cart === $product_id ) {
                    $in_cart = true;
                }
            }
            if ( !isset( $_REQUEST['dsfps-simple-add-to-cart'] ) || !is_numeric( wp_unslash( sanitize_text_field( $_REQUEST['dsfps-simple-add-to-cart'] ) ) ) ) {
                if ( $passed_validation && $in_cart && false !== WC()->cart->add_to_cart(
                    $product_id,
                    $quantity,
                    $variation_id,
                    $variation,
                    $cart_item_data
                ) ) {
                    return true;
                } elseif ( $passed_validation && false !== WC()->cart->add_to_cart(
                    $product_id,
                    $quantity,
                    $variation_id,
                    $variation,
                    $cart_item_data
                ) ) {
                    return true;
                }
            } else {
                if ( $passed_validation && $in_cart && false !== WC()->cart->add_to_cart(
                    $product_id,
                    $quantity,
                    $variation_id,
                    $variation,
                    $cart_item_data
                ) ) {
                    wc_add_to_cart_message( array(
                        $product_id => $quantity,
                    ), true );
                    return true;
                } elseif ( $passed_validation && false !== WC()->cart->add_to_cart(
                    $product_id,
                    $quantity,
                    $variation_id,
                    $variation,
                    $cart_item_data
                ) ) {
                    wc_add_to_cart_message( array(
                        $product_id => $quantity,
                    ), true );
                    return true;
                }
            }
            return false;
        }

        /**
         * Handle adding variable products to the cart.
         *
         * @since 1.0.0
         * @throws Exception If add to cart fails.
         * @param int $product_id Product ID to add to the cart.
         * @return bool success or not
         */
        private function dsfps_add_to_cart_handler_variable( $product_id ) {
            try {
                $nonce_action = 'dsfps_add_to_cart_nounce';
                if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                    return;
                }
                $variation_id = ( empty( $_REQUEST['variation_id'] ) ? '' : absint( wp_unslash( $_REQUEST['variation_id'] ) ) );
                $quantity = ( empty( $_REQUEST['quantity'] ) ? 1 : wp_unslash( absint( $_REQUEST['quantity'] ) ) );
                $missing_attributes = array();
                $variations = array();
                $cart_item_data = array(
                    'fps_sample_items' => 'sample',
                );
                $adding_to_cart = wc_get_product( $product_id );
                if ( !$adding_to_cart ) {
                    return false;
                }
                // If the $product_id was in fact a variation ID, update the variables.
                if ( $adding_to_cart->is_type( 'variable' ) ) {
                    $variation_id = $product_id;
                    $product_id = $adding_to_cart->get_parent_id();
                    $adding_to_cart = wc_get_product( $product_id );
                    if ( !$adding_to_cart ) {
                        return false;
                    }
                }
                // Gather posted attributes.
                $posted_attributes = array();
                foreach ( $adding_to_cart->get_attributes() as $attribute ) {
                    if ( !$attribute['is_variation'] ) {
                        continue;
                    }
                    $attribute_key = 'attribute_' . sanitize_title( $attribute['name'] );
                    if ( isset( $_REQUEST[$attribute_key] ) ) {
                        if ( $attribute['is_taxonomy'] ) {
                            // Don't use wc_clean as it destroys sanitized characters.
                            $value = sanitize_title( wp_unslash( $_REQUEST[$attribute_key] ) );
                        } else {
                            $value = html_entity_decode( wc_clean( sanitize_title( wp_unslash( $_REQUEST[$attribute_key] ) ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
                        }
                        $posted_attributes[$attribute_key] = $value;
                    }
                }
                // If no variation ID is set, attempt to get a variation ID from posted attributes.
                if ( empty( $variation_id ) ) {
                    $data_store = WC_Data_Store::load( 'product' );
                    $variation_id = $data_store->find_matching_product_variation( $adding_to_cart, $posted_attributes );
                }
                // Do we have a variation ID?
                if ( empty( $variation_id ) ) {
                    throw new Exception(__( 'Please choose product options&hellip;', 'free-product-sample' ));
                }
                // Check the data we have is valid.
                $variation_data = wc_get_product_variation_attributes( $variation_id );
                foreach ( $adding_to_cart->get_attributes() as $attribute ) {
                    if ( !$attribute['is_variation'] ) {
                        continue;
                    }
                    // Get valid value from variation data.
                    $attribute_key = 'attribute_' . sanitize_title( $attribute['name'] );
                    $valid_value = ( isset( $variation_data[$attribute_key] ) ? $variation_data[$attribute_key] : '' );
                    /**
                     * If the attribute value was posted, check if it's valid.
                     *
                     * If no attribute was posted, only error if the variation has an 'any' attribute which requires a value.
                     */
                    if ( isset( $posted_attributes[$attribute_key] ) ) {
                        $value = $posted_attributes[$attribute_key];
                        // Allow if valid or show error.
                        if ( $valid_value === $value ) {
                            $variations[$attribute_key] = $value;
                        } elseif ( '' === $valid_value && in_array( $value, $attribute->get_slugs(), true ) ) {
                            // If valid values are empty, this is an 'any' variation so get all possible values.
                            $variations[$attribute_key] = $value;
                        } else {
                            /* translators: %s: Attribute name. */
                            throw new Exception(sprintf( __( 'Invalid value posted for %s', 'free-product-sample' ), wc_attribute_label( $attribute['name'] ) ));
                        }
                    } elseif ( '' === $valid_value ) {
                        $missing_attributes[] = wc_attribute_label( $attribute['name'] );
                    }
                }
                if ( !empty( $missing_attributes ) ) {
                    /* translators: %s: Attribute name. */
                    throw new Exception(sprintf( _n(
                        '%s is a required field',
                        '%s are required fields',
                        count( $missing_attributes ),
                        'free-product-sample'
                    ), wc_format_list_of_items( $missing_attributes ) ));
                }
            } catch ( Exception $e ) {
                wc_clear_notices();
                // Clear other WC notices
                wc_add_notice( $e->getMessage(), 'error' );
                return false;
            }
            $passed_validation = apply_filters(
                'woocommerce_add_to_cart_validation',
                true,
                $product_id,
                $quantity,
                $variation_id,
                $variations
            );
            if ( $passed_validation && false !== WC()->cart->add_to_cart(
                $product_id,
                $quantity,
                $variation_id,
                $variations,
                $cart_item_data
            ) ) {
                wc_add_to_cart_message( array(
                    $product_id => $quantity,
                ), true );
                return true;
            }
            return false;
        }

        /**
         * Get the total quantity from the cart by given product ID
         *
         * @param $product_id
         *
         * @return int
         */
        private function dsfps_get_product_qty_from_cart_by_id( $product_id ) {
            foreach ( WC()->cart->get_cart() as $val ) {
                if ( isset( $val["fps_free_sample"] ) ) {
                    $_product = $val['data'];
                    $product = wc_get_product( $_product->get_id() );
                    if ( $product->get_type() === 'variation' ) {
                        if ( $product_id === $val["variation_id"] ) {
                            return $val['quantity'];
                        }
                    } else {
                        if ( $product_id === $_product->get_id() ) {
                            return $val['quantity'];
                        }
                    }
                }
            }
            return 0;
        }

        /**
         * Get sample product name with prefix/suffix
         *
         * @param $product_name
         * 
         * @since 1.1.3
         *
         * @return string
         */
        private function dsfps_get_sample_name_with_prefix( $product_name ) {
            $sample_name = '';
            $sample_name = wp_kses_post( 'Sample - ' . $product_name );
            return $sample_name;
        }

        /**
         * Validation on add to cart process
         *
         * @since 1.0.0
         */
        public function dsfps_add_to_cart_validation(
            $passed,
            $product_id,
            $quantity,
            $variation_id = null
        ) {
            if ( isset( $_REQUEST['action'] ) && ('dsfps_single_product_add_to_cart_using_ajax' === $_REQUEST['action'] || 'dsfps_add_to_cart_action_using_ajax' === $_REQUEST['action']) ) {
                $original_product = false;
                // Make it false if click on sample add to cart button
            } else {
                $original_product = true;
                // Make it true if click on original add to cart button
            }
            $product_name = '';
            if ( isset( $_REQUEST['dsfps-simple-add-to-cart'] ) || isset( $_REQUEST['dsfps-variable-add-to-cart'] ) || isset( $_REQUEST['action'] ) && ('dsfps_single_product_add_to_cart_using_ajax' === $_REQUEST['action'] || 'dsfps_add_to_cart_action_using_ajax' === $_REQUEST['action']) ) {
                $nonce_action = 'dsfps_add_to_cart_nounce';
                if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                    return;
                }
                $original_product = false;
                // Make it false if click on sample add to cart button
                $fps_sample_quantity_type = get_option( 'fps_sample_quantity_type' );
                $fps_quantity_per_product = get_option( 'fps_quantity_per_product' );
                $fps_max_quantity_message = get_option( 'fps_max_quantity_message' );
                $product_id_for_stock = $product_id;
                $product_id = ( !empty( $variation_id ) && 0 !== $variation_id ? $variation_id : $product_id );
                $product = wc_get_product( $product_id );
                $product_name = $product->get_name();
                $sample_name = $this->dsfps_get_sample_name_with_prefix( $product_name );
                /** Quantity on Product section Start here */
                if ( 'per_product' === $fps_sample_quantity_type && !empty( $fps_quantity_per_product ) || !empty( $get_dsfps_quantity_limit_per_product ) ) {
                    $cart_product_quantity = $this->dsfps_get_product_qty_from_cart_by_id( $product_id );
                    $cart_product_quantity += $quantity;
                    if ( $cart_product_quantity > $fps_quantity_per_product ) {
                        $passed = false;
                    }
                    if ( !$passed ) {
                        if ( isset( $fps_max_quantity_message ) && !empty( $fps_max_quantity_message ) ) {
                            $fps_max_quantity_message = str_replace( "{PRODUCT}", $sample_name, $fps_max_quantity_message );
                            $fps_max_quantity_message = str_replace( "{QTY}", $fps_quantity_per_product, $fps_max_quantity_message );
                            wc_clear_notices();
                            // Clear other WC notices
                            wc_add_notice( __( $fps_max_quantity_message, 'free-product-sample' ), 'error' );
                        } else {
                            wc_clear_notices();
                            // Clear other WC notices
                            wc_add_notice( __( 'Sorry! you reached maximum sample limit for this product!', 'free-product-sample' ), 'error' );
                        }
                    }
                }
            }
            return $passed;
        }

        /**
         * Set old quantity on session to revert back on max limit validation
         *
         * @since 1.1.3
         */
        public function dsfps_set_old_quantity_in_session( $cart_item_key, $quantity, $old_quantity ) {
            foreach ( WC()->cart->get_cart() as $loop_cart_item_key => $val ) {
                if ( isset( $val["fps_free_sample"] ) && $loop_cart_item_key === $cart_item_key ) {
                    // Save the old quantity of the product in the session
                    WC()->session->set( 'old_quantity_' . $loop_cart_item_key, $old_quantity );
                }
            }
        }

        /**
         * Validation on cart process
         *
         * @since 1.0.0
         */
        public function dsfps_validate_product_max_quantity() {
            $fps_sample_quantity_type = get_option( 'fps_sample_quantity_type' );
            $fps_quantity_per_product = get_option( 'fps_quantity_per_product' );
            $fps_quantity_per_order = get_option( 'fps_quantity_per_order' );
            $fps_max_quantity_message = get_option( 'fps_max_quantity_message' );
            $fps_max_quantity_per_order_msg = get_option( 'fps_max_quantity_per_order_msg' );
            $cartQtyArr = [];
            $cart_notice = [];
            $replaced_product_name = [];
            $replaced_product_qty = [];
            $not_enough_stock = false;
            $sample_passed = true;
            foreach ( WC()->cart->get_cart() as $cart_item_key => $val ) {
                if ( isset( $val["fps_free_sample"] ) ) {
                    $product_id = $val["product_id"];
                    $cartQtyKey = $val["fps_free_sample"];
                    $variation_id = $val["variation_id"];
                    $cartQty = $val['quantity'];
                    $manage_stock = $val['data']->get_manage_stock();
                    $cartQtyArr[] = $val['quantity'];
                    $product = wc_get_product( $cartQtyKey );
                    $item_obj = wc_get_product( $val['data']->get_id() );
                    $product_name = $item_obj->get_name();
                    $cart_quantity = 0;
                    $get_dsfps_quantity_limit_per_product = get_post_meta( $product_id, 'dsfps_quantity_limit_per_product', true );
                    // Get the old quantity from the session
                    $old_quantity = '';
                    $get_old_quantity = WC()->session->get( 'old_quantity_' . $cart_item_key );
                    if ( isset( $get_old_quantity ) && !empty( $get_old_quantity ) ) {
                        $old_quantity = $get_old_quantity;
                    }
                    if ( 'per_product' === $fps_sample_quantity_type && !empty( $fps_quantity_per_product ) || !empty( $get_dsfps_quantity_limit_per_product ) ) {
                        if ( isset( $cartQty ) && !empty( $cartQty ) ) {
                            if ( intval( $cartQty ) > intval( $fps_quantity_per_product ) ) {
                                $product_name = get_the_title( $cartQtyKey );
                                $sample_name = $this->dsfps_get_sample_name_with_prefix( $product_name );
                                if ( isset( $fps_max_quantity_message ) && !empty( $fps_max_quantity_message ) ) {
                                    $replaced_product_qty = str_replace( "{QTY}", $fps_quantity_per_product, $fps_max_quantity_message );
                                    $replaced_product_name = str_replace( "{PRODUCT}", $sample_name, $replaced_product_qty );
                                    $cart_notice = __( $replaced_product_name, 'free-product-sample' );
                                    wc_clear_notices();
                                    // Clear other WC notices
                                    wc_add_notice( __( $cart_notice, 'free-product-sample' ), 'error' );
                                    $sample_passed = false;
                                } else {
                                    wc_clear_notices();
                                    // Clear other WC notices
                                    wc_add_notice( __( 'Sorry! you reached maximum sample limit for this product!', 'free-product-sample' ), 'error' );
                                    $sample_passed = false;
                                }
                                WC()->cart->set_quantity( $cart_item_key, $old_quantity );
                                WC()->session->__unset( 'old_quantity_' . $cart_item_key );
                            }
                        }
                    }
                    // manage sample products stock
                    $manage_sample_stock = get_post_meta( $cartQtyKey, 'dsfps_manage_stock', true );
                    $get_sample_stocks = get_post_meta( $cartQtyKey, 'dsfps_stock', true );
                    $manage_variation_sample = get_post_meta( $variation_id, 'fps_manage_variation_sample', true );
                    $get_variation_stocks = get_post_meta( $variation_id, 'dsfps_variation_stock', true );
                    $sample_name = $this->dsfps_get_sample_name_with_prefix( $product_name );
                    $wc_notice = __( 'Sorry, we do not have enough "' . $sample_name . '" in stock to fulfill your order (' . $get_sample_stocks . ' available). We apologize for any inconvenience caused.', 'free-product-sample' );
                    $wc_variation_notice = __( 'Sorry, we do not have enough "' . $sample_name . '" in stock to fulfill your order (' . $get_variation_stocks . ' available). We apologize for any inconvenience caused.', 'free-product-sample' );
                    if ( $product->get_type() === 'variable' ) {
                        if ( isset( $manage_variation_sample ) && !empty( $manage_variation_sample ) ) {
                            if ( $manage_stock === true ) {
                                if ( isset( $get_variation_stocks ) ) {
                                    if ( intval( $cartQty ) > intval( $get_variation_stocks ) ) {
                                        wc_clear_notices();
                                        // Clear other WC notices
                                        wc_add_notice( $wc_variation_notice, 'error' );
                                        $sample_passed = false;
                                    }
                                }
                            } else {
                                if ( isset( $manage_sample_stock ) && !empty( $manage_sample_stock ) ) {
                                    if ( isset( $get_sample_stocks ) ) {
                                        if ( intval( $cartQty ) > intval( $get_sample_stocks ) ) {
                                            wc_clear_notices();
                                            // Clear other WC notices
                                            wc_add_notice( $wc_notice, 'error' );
                                            $sample_passed = false;
                                        }
                                    }
                                }
                            }
                        } else {
                            $product_qty_in_cart = $this->dsfps_get_sample_product_cart_item_qty();
                            $cart_product_qty = $product_qty_in_cart[intval( $cartQtyKey )];
                            if ( isset( $manage_sample_stock ) && !empty( $manage_sample_stock ) ) {
                                if ( isset( $get_sample_stocks ) ) {
                                    if ( $cart_product_qty > intval( $get_sample_stocks ) ) {
                                        $not_enough_stock = true;
                                    }
                                }
                            }
                        }
                    } else {
                        if ( isset( $manage_sample_stock ) && !empty( $manage_sample_stock ) ) {
                            if ( isset( $get_sample_stocks ) ) {
                                if ( intval( $cartQty ) > intval( $get_sample_stocks ) ) {
                                    wc_clear_notices();
                                    // Clear other WC notices
                                    wc_add_notice( $wc_notice, 'error' );
                                    $sample_passed = false;
                                }
                            }
                        }
                    }
                }
            }
            if ( $not_enough_stock ) {
                wc_clear_notices();
                // Clear other WC notices
                wc_add_notice( $wc_notice, 'error' );
                $sample_passed = false;
            }
            if ( 'per_order' === $fps_sample_quantity_type && !empty( $fps_quantity_per_order ) ) {
                if ( isset( $cartQtyArr ) && is_array( $cartQtyArr ) && !empty( $cartQtyArr ) ) {
                    $cart_quantity = array_sum( $cartQtyArr );
                    if ( intval( $fps_quantity_per_order ) < $cart_quantity ) {
                        if ( isset( $fps_max_quantity_per_order_msg ) && !empty( $fps_max_quantity_per_order_msg ) ) {
                            $replaced_order_max_qty = str_replace( "{MAX_QTY}", $fps_quantity_per_order, $fps_max_quantity_per_order_msg );
                            $replaced_cart_qty = str_replace( "{CART_QTY}", $cart_quantity, $replaced_order_max_qty );
                            $cart_notice = __( $replaced_cart_qty, 'free-product-sample' );
                            wc_clear_notices();
                            // Clear other WC notices
                            wc_add_notice( __( $cart_notice, 'free-product-sample' ), 'error' );
                            $sample_passed = false;
                        } else {
                            wc_clear_notices();
                            // Clear other WC notices
                            wc_add_notice( sprintf( __( 'The maximum allows order quantity is %1$s for sample product and you have %2$s in your cart.', 'free-product-sample' ), $fps_quantity_per_order, $cart_quantity ), 'error' );
                            $sample_passed = false;
                        }
                    }
                }
            }
            // Samples and actual products order restriction
            $actual_product_qty_in_cart = $this->dsfps_get_original_product_cart_item_qty();
            $sample_product_qty_in_cart = $this->dsfps_get_sample_product_cart_item_qty();
            if ( empty( $actual_product_qty_in_cart ) && !empty( $sample_product_qty_in_cart ) ) {
                $hide_local_pickup_filter = apply_filters( 'dsfps_hide_local_pickup_for_sample', false );
                if ( $hide_local_pickup_filter ) {
                    function dsfps_sample_can_be_pickupd_up() {
                        return false;
                    }

                    add_filter( 'wc_local_pickup_plus_product_can_be_picked_up', 'dsfps_sample_can_be_pickupd_up' );
                    add_filter( 'wc_local_pickup_plus_product_must_be_picked_up', 'dsfps_sample_can_be_pickupd_up' );
                    function dsfps_hide_lp_handling_toggles_for_sample() {
                        return '';
                    }

                    add_filter( 'wc_local_pickup_plus_get_cart_item_handling_toggle_html', 'dsfps_hide_lp_handling_toggles_for_sample' );
                    add_filter( 'wc_local_pickup_plus_get_pickup_location_cart_item_field_html', 'dsfps_hide_lp_handling_toggles_for_sample' );
                }
            }
            if ( has_filter( 'dsfps_wc_check_cart_items' ) ) {
                if ( !$sample_passed && is_checkout() ) {
                    wc_clear_notices();
                    wp_safe_redirect( wc_get_cart_url() );
                    exit;
                }
            }
        }

        /**
         * Add body class to hide local pickup fields 
         *
         * @since      1.0.0
         * @param      $product_id
         */
        public function dsfps_add_class_to_hide_local_pickup_fields( $classes ) {
            $actual_product_qty_in_cart = $this->dsfps_get_original_product_cart_item_qty();
            $sample_product_qty_in_cart = $this->dsfps_get_sample_product_cart_item_qty();
            if ( empty( $actual_product_qty_in_cart ) && !empty( $sample_product_qty_in_cart ) ) {
                if ( has_filter( 'dsfps_hide_local_pickup_for_sample' ) ) {
                    $classes[] = 'dsfps_hide_local_pickup_fields';
                }
            }
            return $classes;
        }

        /**
         * AJAX add to cart.
         *
         * @since      1.0.0
         * @param      $product_id
         */
        public function dsfps_add_to_cart_action_using_ajax() {
            $get_product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $product_id = ( !empty( $get_product_id ) ? intval( wp_unslash( $get_product_id ) ) : 0 );
            $quantity = 1;
            $get_variation_id = filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $variation_id = ( !empty( $get_variation_id ) ? intval( wp_unslash( $get_variation_id ) ) : 0 );
            $cart_validation = apply_filters(
                'woocommerce_add_to_cart_validation',
                true,
                $product_id,
                $quantity,
                $variation_id
            );
            $passed_validation = $this->dsfps_add_to_cart_validation(
                $cart_validation,
                $product_id,
                $quantity,
                $variation_id
            );
            wc_nocache_headers();
            $was_added_to_cart = false;
            $adding_to_cart = wc_get_product( $product_id );
            if ( !$adding_to_cart ) {
                return;
            }
            if ( $passed_validation ) {
                $add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );
                if ( 'simple' === $add_to_cart_handler ) {
                    $was_added_to_cart = $this->dsfps_add_to_cart_handler_simple( $product_id );
                    $cart = WC()->cart->cart_contents;
                    foreach ( $cart as $cart_item_id => $cart_new_item ) {
                        $product_in_cart = $cart_new_item['product_id'];
                        if ( $product_in_cart === intval( $product_id ) ) {
                            if ( isset( $cart_new_item['fps_sample_items'] ) ) {
                                $cart_new_item['fps_free_sample'] = $product_id;
                                $cart_new_item['fps_sample_price'] = $this->dsfps_set_sample_prod_price( $product_id );
                                WC()->cart->cart_contents[$cart_item_id] = $cart_new_item;
                            }
                        }
                    }
                    WC()->cart->set_session();
                } elseif ( 'variable' === $add_to_cart_handler ) {
                    if ( isset( $variation_id ) && !empty( $variation_id ) && '0' !== $variation_id ) {
                        $cart_item_data = array(
                            'fps_sample_items' => 'sample',
                        );
                        $variation = array();
                        if ( WC()->cart->add_to_cart(
                            $product_id,
                            $quantity,
                            $variation_id,
                            $variation,
                            $cart_item_data
                        ) ) {
                            $cart = WC()->cart->cart_contents;
                            foreach ( $cart as $cart_item_id => $cart_new_item ) {
                                $product_in_cart = $cart_new_item['product_id'];
                                if ( $product_in_cart === intval( $product_id ) ) {
                                    if ( isset( $cart_new_item['fps_sample_items'] ) ) {
                                        $cart_new_item['fps_free_sample'] = $product_id;
                                        $cart_new_item['fps_sample_price'] = $this->dsfps_set_sample_prod_price( $product_id );
                                        WC()->cart->cart_contents[$cart_item_id] = $cart_new_item;
                                    }
                                }
                            }
                            WC()->cart->set_session();
                        }
                    }
                }
            } else {
                echo wp_kses_post( '<div class="dsfps-add-to-cart-failed"></div>' );
                wc_print_notices();
            }
            wp_die();
        }

        /**
         * Force cart re-calculation when displaying the mini-cart.
         *
         * @return void
         */
        public function dsfps_mini_cart_re_calculation_ajax() {
            if ( is_cart() || is_checkout() ) {
                return;
            }
            WC()->cart->calculate_totals();
        }

        /**
         * Set sample product in the cart
         *
         * @since      1.0.0
         * @param      string
         */
        public function dsfps_store_id( $cart_item ) {
            $nonce_action = 'dsfps_add_to_cart_nounce';
            if ( isset( $_REQUEST['dsfps-simple-add-to-cart'] ) || isset( $_REQUEST['dsfps-variable-add-to-cart'] ) ) {
                if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                    return;
                }
                if ( isset( $_REQUEST['action'] ) && 'dsfps_single_product_add_to_cart_using_ajax' !== $_REQUEST['action'] ) {
                    return;
                }
                $cart_item['fps_free_sample'] = ( isset( $_REQUEST['dsfps-simple-add-to-cart'] ) ? sanitize_text_field( $_REQUEST['dsfps-simple-add-to-cart'] ) : sanitize_text_field( $_REQUEST['dsfps-variable-add-to-cart'] ) );
                $product_id = ( isset( $_REQUEST['dsfps-simple-add-to-cart'] ) ? sanitize_text_field( $_REQUEST['dsfps-simple-add-to-cart'] ) : sanitize_text_field( $_REQUEST['dsfps-variable-add-to-cart'] ) );
                if ( isset( $cart_item["variation_id"] ) ) {
                    $variation_id = $cart_item['variation_id'];
                } else {
                    $variation_id = '';
                }
                $cart_item['fps_sample_price'] = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
            }
            return $cart_item;
        }

        /**
         * Set sample product in session
         *
         * @since      1.0.0
         * @param      array, array
         */
        public function dsfps_get_cart_items_from_session( $cart_item, $values ) {
            if ( isset( $values['dsfps-simple-add-to-cart'] ) || isset( $values['dsfps-variable-add-to-cart'] ) ) {
                $nonce_action = 'dsfps_add_to_cart_nounce';
                if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                    return;
                }
                if ( isset( $values['action'] ) && 'dsfps_single_product_add_to_cart_using_ajax' !== $values['action'] ) {
                    return;
                }
                $cart_item['fps_free_sample'] = ( isset( $values['dsfps-simple-add-to-cart'] ) ? $values['dsfps-simple-add-to-cart'] : $values['dsfps-variable-add-to-cart'] );
                $product_id = ( isset( $_REQUEST['dsfps-simple-add-to-cart'] ) || isset( $_REQUEST['dsfps-variable-add-to-cart'] ) ? sanitize_text_field( $_REQUEST['dsfps-simple-add-to-cart'] ) : sanitize_text_field( $_REQUEST['dsfps-variable-add-to-cart'] ) );
                if ( isset( $cart_item["variation_id"] ) ) {
                    $variation_id = $cart_item['variation_id'];
                } else {
                    $variation_id = '';
                }
                $cart_item['fps_sample_price'] = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
            }
            return $cart_item;
        }

        /**
         * Set sample product price
         *
         * @since      1.0.0
         * @param      int, int
         */
        public function dsfps_set_sample_prod_price( $product_id = 0, $variation_id = 0 ) {
            // get sample product price
            $fps_sample_price_type = get_option( 'fps_sample_price_type' );
            $fps_sample_flat_price = get_option( 'fps_sample_flat_price' );
            if ( $fps_sample_price_type === 'flat_price' && !empty( $fps_sample_flat_price ) ) {
                $price = (float) $fps_sample_flat_price;
            } else {
                $price = 0;
            }
            // return float
            return number_format(
                (float) $price,
                2,
                '.',
                ''
            );
        }

        /**
         * Set sample price in the order meta
         *
         * @since      1.0.0
         * @param      object, array
         */
        public function dsfps_apply_sample_price_to_cart_item( $cart ) {
            if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
                return;
            }
            // Avoiding hook repetition (when using price calculations for example)
            if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
                return;
            }
            foreach ( $cart->get_cart() as $value ) {
                if ( isset( $value["fps_free_sample"] ) ) {
                    $product = $value['data'];
                    $product_id = $value['product_id'];
                    if ( isset( $value["variation_id"] ) ) {
                        $variation_id = $value['variation_id'];
                    } else {
                        $variation_id = '';
                    }
                    $sample_price = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
                    ( method_exists( $product, 'set_price' ) ? $product->set_price( $sample_price ) : ($product->price = $sample_price) );
                }
            }
        }

        /**
         * Sample product added in the cart message
         *
         * @since      1.0.0
         * @param      int, array
         */
        public function dsfps_add_to_cart_message( $message, $products ) {
            $titles = '';
            if ( isset( $_REQUEST['dsfps-simple-add-to-cart'] ) || isset( $_REQUEST['dsfps-variable-add-to-cart'] ) ) {
                $nonce_action = 'dsfps_add_to_cart_nounce';
                if ( isset( $_REQUEST['_wpnonce'] ) && !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), $nonce_action ) ) {
                    return;
                }
                $count = 0;
                $titles = array();
                foreach ( $products as $product_id => $qty ) {
                    $sample = esc_html__( 'Sample - ', 'free-product-sample' );
                    $titles[] = apply_filters( 'woocommerce_add_to_cart_qty_html', ( $qty > 1 ? absint( $qty ) . ' &times; ' : '' ), $product_id ) . apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'free-product-sample' ), wp_strip_all_tags( $sample . get_the_title( $product_id ) ) ), $product_id );
                    $count += $qty;
                }
                $titles = array_filter( $titles );
                /* translators: %s: product name */
                $added_text = sprintf( _n(
                    '%s has been added to your cart.',
                    '%s have been added to your cart.',
                    $count,
                    'free-product-sample'
                ), wc_format_list_of_items( $titles ) );
                // Output success messages.
                $message = sprintf(
                    '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s',
                    esc_url( wc_get_cart_url() ),
                    esc_html__( 'View cart', 'free-product-sample' ),
                    esc_html__( $added_text, 'free-product-sample' )
                );
                return $message;
            }
            return $message;
        }

        /**
         * Add sample label before the product
         *
         * @since      1.0.0
         * @param      string, array, array
         */
        public function dsfps_alter_item_name( $product_name, $cart_item ) {
            if ( isset( $cart_item['fps_free_sample'] ) ) {
                $product_name = $this->dsfps_get_sample_name_with_prefix( $product_name );
            }
            return $product_name;
        }

        /**
         * Set sample price instead real price
         *
         * @since      1.0.0
         * @param      float, array, array
         */
        public function dsfps_cart_item_price_filter( $price, $cart_item ) {
            if ( isset( $cart_item['fps_free_sample'] ) ) {
                $product_id = $cart_item['product_id'];
                if ( isset( $cart_item["variation_id"] ) ) {
                    $variation_id = $cart_item['variation_id'];
                } else {
                    $variation_id = '';
                }
                $set_price = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
                $price = wc_price( $set_price );
            }
            return $price;
        }

        /**
         * Set subtotal
         *
         * @since      1.0.0
         * @param      float, array, array
         */
        public function dsfps_item_subtotal( $subtotal, $cart_item ) {
            if ( isset( $cart_item['fps_free_sample'] ) ) {
                $product_id = $cart_item['product_id'];
                if ( isset( $cart_item["variation_id"] ) ) {
                    $variation_id = $cart_item['variation_id'];
                } else {
                    $variation_id = '';
                }
                $price = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
                $newsubtotal = wc_price( (float) $price * (int) $cart_item['quantity'] );
                $subtotal = $newsubtotal;
            }
            return $subtotal;
        }

        /**
         * Set icon for the file attachment.
         *
         * @since      1.4.0
         * @param      string, string
         * @return     string
         * @see        dsfps_get_file_icon()
         */
        public function dsfps_get_file_icon( $file_url, $icon_directory = '' ) {
            if ( empty( $icon_directory ) ) {
                $icon_directory = plugin_dir_url( __FILE__ ) . '/images/';
            }
            $file_extension = strtolower( pathinfo( $file_url, PATHINFO_EXTENSION ) );
            switch ( $file_extension ) {
                case 'pdf':
                    $icon = $icon_directory . 'pdf-icon.svg';
                    break;
                case 'mp3':
                    $icon = $icon_directory . 'mp3-icon.svg';
                    break;
                case 'mp4':
                    $icon = $icon_directory . 'mp4-icon.svg';
                    break;
                case 'xls':
                case 'xlsx':
                    $icon = $icon_directory . 'xlsx-icon.svg';
                    break;
                case 'jpg':
                case 'jpeg':
                    $icon = $icon_directory . 'jpg-icon.svg';
                    break;
                case 'png':
                    $icon = $icon_directory . 'png-icon.svg';
                    break;
                case 'doc':
                case 'docx':
                    $icon = $icon_directory . 'doc-icon.svg';
                    break;
                case 'zip':
                    $icon = $icon_directory . 'zip-icon.svg';
                    break;
                default:
                    $icon = $icon_directory . 'attachment-icon.svg';
                    break;
            }
            return "<img width='30px' src='" . $icon . "' alt='" . $file_extension . " icon'>";
        }

        /**
         * Add product meta for sample to identity in the admin order details
         *
         * @since      1.0.0
         * @param      int, array
         */
        public function dsfps_save_posted_data_into_order( $item, $cart_item_key, $values ) {
            if ( isset( $values['fps_free_sample'] ) ) {
                $dsfps_admin_object = new DSFPS_Free_Product_Sample_Pro_Admin('', '');
                $sample = $dsfps_admin_object->dsfps_sample_prefix_for_order_meta();
                $product_id = $values['fps_free_sample'];
                $variation_id = '';
                if ( isset( $values["variation_id"] ) && 0 !== $values["variation_id"] ) {
                    $variation_id = $values['variation_id'];
                }
                $sample_price = $this->dsfps_set_sample_prod_price( $product_id, $variation_id );
                $currency_symbol = get_woocommerce_currency_symbol();
                $final_price = '';
                if ( isset( $currency_symbol ) && !empty( $currency_symbol ) ) {
                    $final_price = $currency_symbol . $sample_price;
                } else {
                    $final_price = $sample_price;
                }
                $item->update_meta_data( 'PRODUCT_TYPE', $sample );
                $item->update_meta_data( 'SAMPLE_PRICE', $final_price );
            }
        }

        /**
         * Hide & Show sample page menu item 
         *
         * @since      1.0.0
         */
        public function dsfps_add_sample_page_menu_item( $items, $args ) {
            $fps_settings_include_store_menu = get_option( 'fps_settings_include_store_menu' );
            if ( isset( $fps_settings_include_store_menu ) && 'on' === $fps_settings_include_store_menu ) {
                $menu_location = apply_filters( 'default_sample_shop_menu_location', 'primary' );
                if ( $args->theme_location === $menu_location ) {
                    ob_start();
                    ?>
			    	<li><a href="<?php 
                    echo esc_url( get_permalink( get_page_by_path( 'fps-sample-product' ) ) );
                    // phpcs:ignore
                    ?>"><?php 
                    esc_html_e( "Sample Products", "free-product-sample" );
                    ?></a></li>
			    	<?php 
                    $items .= ob_get_clean();
                }
            }
            return $items;
        }

        /**
         * Add woocommerce class in body on sample products listing page
         *
         * @param array $classes Existing body classes.
         * @return array Amended body classes.
         */
        public function dsfps_sample_prod_wc_body_class( $classes ) {
            $page_id = get_option( 'dsfps_sample_page' );
            $new_class = ( is_page( $page_id ) ? 'woocommerce' : null );
            if ( $new_class ) {
                $classes[] = $new_class;
            }
            return $classes;
        }

        /**
         * AJAX add to cart on product detail page.
         *
         * @since      1.0.0
         * @param      $product_id
         */
        public function dsfps_single_product_add_to_cart_using_ajax() {
            $get_product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $product_id = ( !empty( $get_product_id ) ? intval( wp_unslash( $get_product_id ) ) : 0 );
            $get_quantity = filter_input( INPUT_POST, 'quantity', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $product_qty = ( !empty( $get_quantity ) ? sanitize_text_field( wp_unslash( $get_quantity ) ) : 0 );
            $get_variation_id = filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $variation_id = ( !empty( $get_variation_id ) ? intval( wp_unslash( $get_variation_id ) ) : 0 );
            $cart_validation = apply_filters(
                'woocommerce_add_to_cart_validation',
                true,
                $product_id,
                $product_qty,
                $variation_id
            );
            $passed_validation = $this->dsfps_add_to_cart_validation(
                $cart_validation,
                $product_id,
                $product_qty,
                $variation_id
            );
            wc_nocache_headers();
            $was_added_to_cart = false;
            $adding_to_cart = wc_get_product( $product_id );
            if ( !$adding_to_cart ) {
                return;
            }
            $add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );
            if ( 'simple' === $add_to_cart_handler ) {
                $was_added_to_cart = $this->dsfps_add_to_cart_handler_simple( $product_id );
                $cart = WC()->cart->cart_contents;
                foreach ( $cart as $cart_item_id => $cart_new_item ) {
                    $product_in_cart = $cart_new_item['product_id'];
                    if ( $product_in_cart === intval( $product_id ) ) {
                        if ( isset( $cart_new_item['fps_sample_items'] ) ) {
                            $cart_new_item['fps_free_sample'] = $product_id;
                            $cart_new_item['fps_sample_price'] = $this->dsfps_set_sample_prod_price( $product_id );
                            WC()->cart->cart_contents[$cart_item_id] = $cart_new_item;
                        }
                    }
                }
                WC()->cart->set_session();
            } elseif ( 'variable' === $add_to_cart_handler ) {
                if ( isset( $variation_id ) && !empty( $variation_id ) && '0' !== $variation_id ) {
                    $cart_item_data = array(
                        'fps_sample_items' => 'sample',
                    );
                    $variation = [];
                    // Retrieve all data from the POST request, sanitizing it properly.
                    $post_data = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                    // Ensure $post_data is an array.
                    if ( is_array( $post_data ) ) {
                        // Process only keys that start with 'attribute_'.
                        foreach ( $post_data as $key => $value ) {
                            if ( strpos( $key, 'attribute_' ) === 0 && !empty( $value ) ) {
                                $variation[$key] = sanitize_text_field( $value );
                            }
                        }
                    }
                    if ( $passed_validation && WC()->cart->add_to_cart(
                        $product_id,
                        $product_qty,
                        $variation_id,
                        $variation,
                        $cart_item_data
                    ) ) {
                        $cart = WC()->cart->cart_contents;
                        foreach ( $cart as $cart_item_id => $cart_new_item ) {
                            $product_in_cart = $cart_new_item['product_id'];
                            if ( $product_in_cart === intval( $product_id ) ) {
                                if ( isset( $cart_new_item['fps_sample_items'] ) ) {
                                    $cart_new_item['fps_free_sample'] = $product_id;
                                    $cart_new_item['fps_sample_price'] = $this->dsfps_set_sample_prod_price( $product_id );
                                    WC()->cart->cart_contents[$cart_item_id] = $cart_new_item;
                                }
                            }
                        }
                        WC()->cart->set_session();
                    }
                }
            }
            if ( $passed_validation ) {
                $ajax_dialog_status = get_option( 'fps_ajax_enable_dialog' );
                $fps_ajax_sucess_message = get_option( 'fps_ajax_sucess_message' );
                if ( 'on' === $ajax_dialog_status ) {
                    $ajax_message = ( isset( $fps_ajax_sucess_message ) && !empty( $fps_ajax_sucess_message ) ? $fps_ajax_sucess_message : __( 'Done!', 'free-product-sample' ) );
                    $success_msg = '<div class="dsfps-ajax-success-msg"><p><span class="dashicons dashicons-yes"></span>' . __( $ajax_message, 'free-product-sample' ) . '</p></div>';
                    echo wp_kses_post( $success_msg );
                }
            } else {
                echo wp_kses_post( '<div class="dsfps-add-to-cart-failed"></div>' );
                wc_print_notices();
            }
            wp_die();
        }

        /**
         * Get cart items quantities of original products - merged so we can do accurate stock checks on items across multiple lines.
         *
         * @since 1.1.2
         * 
         * @return array
         */
        public function dsfps_get_original_product_cart_item_qty() {
            $quantities = array();
            if ( function_exists( 'WC' ) && WC()->cart ) {
                foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
                    $product = $values['data'];
                    if ( !isset( $values["fps_free_sample"] ) ) {
                        $quantities[$product->get_stock_managed_by_id()] = ( isset( $quantities[$product->get_stock_managed_by_id()] ) ? $quantities[$product->get_stock_managed_by_id()] + $values['quantity'] : $values['quantity'] );
                    }
                }
            }
            return $quantities;
        }

        /**
         * Get cart items quantities of sample products - merged so we can do accurate stock checks on items across multiple lines.
         *
         * @since 1.1.2
         * 
         * @return array
         */
        public function dsfps_get_sample_product_cart_item_qty() {
            $quantities = array();
            if ( function_exists( 'WC' ) && WC()->cart ) {
                foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
                    $product = $values['data'];
                    if ( isset( $values["fps_free_sample"] ) ) {
                        $variation_id = $values['variation_id'];
                        $manage_variation_sample = get_post_meta( $variation_id, 'fps_manage_variation_sample', true );
                        $product_id = intval( $values["fps_free_sample"] );
                        if ( isset( $manage_variation_sample ) && empty( $manage_variation_sample ) ) {
                            $quantities[$product_id] = ( isset( $quantities[$product_id] ) ? $quantities[$product_id] + $values['quantity'] : $values['quantity'] );
                        }
                    }
                }
            }
            return $quantities;
        }

        /**
         * On "Order Again" from My Account page, check if sample order then allow to re-order sample
         *
         * @since 1.1.3
         */
        public function dsfps_allow_re_order_samples_on_order_again( $cart_item_data, $order_item, $order ) {
            // Check if sample to normal order is enabled
            $fps_convert_samples_to_order = get_option( 'fps_convert_samples_to_order', '' );
            if ( empty( $fps_convert_samples_to_order ) || 'on' !== $fps_convert_samples_to_order ) {
                // Get sample order data
                $product_id = ( isset( $order_item["variation_id"] ) && !empty( $order_item["variation_id"] ) ? $order_item["variation_id"] : $order_item["product_id"] );
                // Check if the samples custom key exists in the original order item then add it
                if ( isset( $order_item['PRODUCT_TYPE'] ) && isset( $order_item['SAMPLE_PRICE'] ) ) {
                    $cart_item_data['fps_sample_items'] = $order_item['PRODUCT_TYPE'];
                    $cart_item_data['fps_free_sample'] = $product_id;
                    $cart_item_data['fps_sample_price'] = $order_item['SAMPLE_PRICE'];
                }
            }
            return $cart_item_data;
        }

    }

}
/** Added new shortcode for list sample products */
add_shortcode( 'dsfps_sample_products_list', 'dsfps_sample_products_shortcode_callback' );
/**
 * Function For display the product sliders
 *
 * @since    1.0.0
 */
function dsfps_sample_products_shortcode_callback() {
    $fps_settings_enable_disable = get_option( 'fps_settings_enable_disable' );
    $fps_sample_enable_type = get_option( 'fps_sample_enable_type' );
    $fps_select_product_list = get_option( 'fps_select_product_list' );
    if ( isset( $fps_settings_enable_disable ) && 'on' === $fps_settings_enable_disable ) {
        $products_per_row = ( function_exists( 'wc_get_default_products_per_row' ) ? wc_get_default_products_per_row() : 3 );
        $product_rows_per_page = ( function_exists( 'wc_get_default_product_rows_per_page' ) ? wc_get_default_product_rows_per_page() : 4 );
        // setup query
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $products_per_row * $product_rows_per_page,
            'paged'          => ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ),
        );
        if ( $fps_sample_enable_type === 'product_wise' ) {
            if ( isset( $fps_select_product_list ) && is_array( $fps_select_product_list ) && !empty( $fps_select_product_list ) ) {
                // query for specific products list
                $args['post__in'] = $fps_select_product_list;
            }
        }
        // query database
        $products_query = new WP_Query($args);
        ob_start();
        if ( $products_query->have_posts() ) {
            ?>
			<div class="dsfps-sample-products-list-main woocommerce">
				<div class="woocommerce-pagination">
		            <?php 
            echo wp_kses_post( paginate_links( array(
                'total'   => $products_query->max_num_pages,
                'current' => max( 1, get_query_var( 'paged' ) ),
                'format'  => '?paged=%#%',
                'type'    => 'list',
            ) ) );
            ?>
			    </div>
				<div class="dsfps-sample-products-list">
					<?php 
            woocommerce_product_loop_start();
            while ( $products_query->have_posts() ) {
                $products_query->the_post();
                $product_data = wc_get_product( get_the_ID() );
                if ( $product_data->is_type( 'simple' ) || $product_data->is_type( 'variable' ) ) {
                    wc_get_template_part( 'content', 'product' );
                }
            }
            // end of the loop.
            woocommerce_product_loop_end();
            ?>
				</div>
				<div class="woocommerce-pagination">
		            <?php 
            echo wp_kses_post( paginate_links( array(
                'total'   => $products_query->max_num_pages,
                'current' => max( 1, get_query_var( 'paged' ) ),
                'format'  => '?paged=%#%',
                'type'    => 'list',
            ) ) );
            ?>
			    </div>
			</div>
		<?php 
        } else {
            esc_html_e( 'No product matching your criteria.', 'free-product-sample' );
        }
        woocommerce_reset_loop();
        wp_reset_postdata();
        return ob_get_clean();
    } else {
        esc_html_e( 'No product matching your criteria.', 'free-product-sample' );
    }
}
