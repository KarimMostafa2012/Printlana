<?php

namespace SW_WAPF_PRO\Includes\Controllers {

    use SW_WAPF_PRO\Includes\Classes\Cache;
    use SW_WAPF_PRO\Includes\Classes\Enumerable;
    use SW_WAPF_PRO\Includes\Classes\Helper;
    use SW_WAPF_PRO\Includes\Classes\Util;
    use SW_WAPF_PRO\Includes\Classes\Woocommerce_Service;
    use SW_WAPF_PRO\Includes\Models\Field;

    if ( ! defined( 'ABSPATH' ) ) {
        die;
    }

    class Linked_Products_Controller {

                private $weight_was_calculated = false;
        private $adding_to_cart = false;

        public function __construct() {

            add_action( 'woocommerce_loaded', function() {

                                if( apply_filters( 'wapf/features/linked_products', true ) ) {

                    add_filter( 'wapf/field_types',                             [ $this, 'add_products_field_type' ] );
                    add_filter( 'wapf/field_options',                           [ $this, 'add_products_field_options' ] );
                    add_filter( 'wapf/field_visibility_conditions',             [ $this, 'add_field_visibility_conditions' ] );
                    add_action( 'wp_ajax_wapf_product_picker',       [ $this, 'product_picker_ajax' ] );
                    add_action( 'wapf/admin/sanitize_field',         [ $this, 'sanitize_field_data'], 10, 2);

                    add_filter( 'wapf/field_template_model',                    [ $this, 'define_products_and_defaults_on_model' ], 10, 5 );

                    add_filter( 'wapf/html/field_container_classes',            [ $this, 'maybe_add_pricing_class'], 10, 2 );

                    add_filter( 'wapf/validate',                                [ $this, 'validate_cart' ], 10, 8 );

                    add_filter( 'wapf/cart/cart_item_field',                    [ $this, 'expand_cart_item_field' ], 10, 3 );

                    add_action( 'woocommerce_add_to_cart_validation',           [ $this, 'set_adding_to_cart' ], 50 );

                    add_action( 'woocommerce_add_to_cart',                      [ $this, 'add_linked_products_to_cart' ], 11, 6 );

                    add_action( 'woocommerce_before_calculate_totals',          [ $this, 'remove_linked_products' ] );

                    add_action( 'woocommerce_before_calculate_totals',          [ $this, 'set_linked_product_price' ] );

                                        add_action( 'woocommerce_before_calculate_totals',          [ $this, 'maybe_add_weight' ], 11 );

                    add_action( 'woocommerce_cart_item_remove_link',            [ $this, 'maybe_remove_removelink' ], 10, 2 );
                    add_action( 'woocommerce_cart_item_quantity',               [ $this, 'maybe_remove_cart_quantity_components' ], 10, 3 );

                    add_filter( 'wapf/store_api/cart/data_callback',            [ $this, 'extend_store_api' ], 10, 2 );
                    add_filter( 'wapf/store_api/cart/schema_callback',          [ $this, 'extend_store_api_schema' ] );
                    add_action( 'wp_footer',                                    [ $this, 'alter_cart_object'] );
                    add_action( 'woocommerce_checkout_create_order_line_item',  [ $this, 'add_child_meta_to_order_item' ], 20, 4 );

                    add_filter( 'wapf/order/order_item_field',                  [ $this, 'expand_order_item_field' ], 10, 3 );

                    add_action( 'woocommerce_reduce_order_stock',               [ $this, 'maybe_reduce_stock'] );

                    add_action( 'woocommerce_order_refunded',                   [ $this, 'maybe_increase_stock_after_refund' ], 10, 2 );

                    add_action( 'woocommerce_restore_order_stock',              [ $this, 'maybe_increase_stock_after_cancel' ] );

                    add_filter( 'woocommerce_cart_item_visible',                [ $this, 'is_linked_product_visible' ], 10, 3 );
                    add_filter( 'woocommerce_widget_cart_item_visible',         [ $this, 'is_linked_product_visible' ], 10, 3 );
                    add_filter( 'woocommerce_checkout_cart_item_visible',       [ $this, 'is_linked_product_visible' ], 10, 3 );
                    add_filter( 'woocommerce_order_item_visible',               [ $this, 'is_linked_product_visible_on_order' ], 10, 2 );

                    add_action( 'woocommerce_update_cart_validation',           [ $this, 'validate_update_quantity_in_cart' ], 10, 4 );
                    add_action( 'woocommerce_after_cart_item_quantity_update',  [ $this, 'update_quantity_in_cart' ], 1, 4 );

                    add_filter( 'woocommerce_ordered_again',                    [ $this, 'update_order_again_child_parent' ], 11, 3 );
                    add_action( 'wapf/order_again/before_cart_item_field',      [ $this, 'prepare_order_again_cart_item' ], 10, 4  );
                }

                            });

                  }

                public function set_adding_to_cart( $validation ) {
            if( $validation ) $this->adding_to_cart = true;
            return $validation;
        }

                #region Order Again

        public function prepare_order_again_cart_item(  $order_item, $field, $clone_idx, $raw_values ) {

            if( $field->type !== 'products' ) {
                return;
            }

            $cache_data = $this->build_cache_data( $field, $raw_values, $order_item->get_quantity(), $order_item->get_product_id(), $clone_idx );

            if( ! is_string( $cache_data ) ) {

                $cache_data['products'] = [];

                foreach( $cache_data['products_to_check'] as $product ) {

                    $product_id = $product->get_id();
                    $needed_qty = $cache_data['choices'][ $product_id ]['qty'];

                    if( ! isset( $cache_data['products'][ $product_id ] ) ) {
                        $cache_data['products'][ $product_id ] = [
                            'qty'           => 0,
                            'price_type'    => $this->get_product_price_type( $field, $cache_data['choices'][ $product_id ] ), 
                            'product'       => $product
                        ];
                    }

                    $cache_data['products'][ $product_id ]['qty'] += $needed_qty;
                }

            }

            Cache::add_linked_products( $cache_data, $field->id, $clone_idx );

        }

                public function update_order_again_child_parent( $order_id, $order_items, &$cart ) {
            foreach ( $cart as &$cart_item ) {
                if( ! empty( $cart_item['_wapf_child'] ) && is_array( $cart_item['_wapf_child'] ) ) {
                    foreach ( $cart as $item ) {
                        if( isset( $item['old_cart_item_key'] ) && $item['old_cart_item_key']  === $cart_item['_wapf_child']['parent'] ) {
                            $cart_item['_wapf_child']['parent'] = $item['key'];
                        }
                    }
                }
            }
        }

                #endregion

                #region Cosmetic functions

                public function is_linked_product_visible_on_order ( $visible, $order_item ) {

            if( is_admin() ) {
                return $visible;
            }

            $child = $order_item->get_meta( '_wapf_child' );

            if( ! empty( $child ) ) {
                return isset( $child[ 'hide_order' ] ) ? ! $child['hide_order'] : $visible;
            }

            return $visible;

        }

        public function is_linked_product_visible( $visible, $cart_item, $cart_item_key ) {

            if( ! $this->is_linked_product_cartitem( $cart_item ) ) {
                return $visible;
            }

            $hide_in_cart = ! empty( $cart_item['_wapf_child']['hide_cart'] );
            $hide_in_checkout = ! empty( $cart_item['_wapf_child']['hide_checkout'] );

            if ( $hide_in_cart && ( current_filter() === 'woocommerce_cart_item_visible' || current_filter() === 'woocommerce_widget_cart_item_visible' ) ) {
                return false;
            }

            if ( $hide_in_checkout && current_filter() === 'woocommerce_checkout_cart_item_visible' ) {
                return false;
            }

            return $visible;

                    }

                public function maybe_remove_cart_quantity_components( $product_quantity, $cart_item_key, $cart_item ) {

                        if(  $this->is_linked_product_cartitem( $cart_item ) && $cart_item['_wapf_child']['qty_type'] !== 'custom' ) {
                return esc_html( $cart_item['quantity'] );
            }

            return $product_quantity;

                    }

                public function maybe_remove_removelink( $remove_link, $cart_item_key ) {

                        $cart_item = WC()->cart->get_cart_item( $cart_item_key );

            if( $this->is_linked_product_cartitem( $cart_item ) ) {
                return '';
            }

            return $remove_link;

                    }

        public function extend_store_api( $data, $cart_item ) {

            if( $this->is_linked_product_cartitem( $cart_item ) ) {
                $data['hideControls'] = true;
            }

            return $data;

        }

                public function extend_store_api_schema( $schema ) {

                        $schema['hideControls'] = [
                'description' => __( 'Hide quantity controls on the cart item.', 'sw-wapf' ),
                'type' => 'boolean',
                'context' => [ 'view', 'edit' ],
                'readonly' => true,
            ];

                        return $schema;

                    }

                public function set_cart_item_editable( $value, $product, $cart_item ) {
            if( empty( $cart_item ) ) return $value;
            return $this->is_linked_product_cartitem( $cart_item ) ? false : $value;
        }

        public function alter_cart_object() {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if( window.wc && window.wc.blocksCheckout ) {
                        window.wc.blocksCheckout.registerCheckoutFilters('apf-hidecontrols', {
                            showRemoveItemLink: function( val, extensions, args ) {
                                if( args && args.context === 'cart' && args.cartItem.extensions?.apf.hideControls ) {
                                    return false;
                                }
                                return val;
                            },
                            cartItemClass: function( val, extensions, args ) {
                                if( args && args.context === 'cart' && args.cartItem.extensions?.apf.hideControls ) {
                                    return 'wapf-child-item';
                                }
                                return val;
                            }
                        });
                    }
                });
            </script>
            <?php
        }

                #endregion

                #region Cart functions

        public function validate_update_quantity_in_cart(  $is_valid, $cart_item_key, $values, $quantity ) {

            if( ! $is_valid || empty( $quantity ) ) {
                return $is_valid;
            }

            if( ! Util::should_add_linked_products_to_cart() ) {
                return $is_valid;
            }

            $cart_contents = WC()->cart->get_cart();

            if( ! empty( $cart_contents[ $cart_item_key ] ) ) {

                $parent_item = $cart_contents[ $cart_item_key ];
                $has_children = $this->cart_item_has_linked_products( $parent_item );

                if( $has_children ) {
                    foreach ( $cart_contents as $child_item_key => $child_item ) {
                        if( $this->is_linked_product_cartitem( $child_item, $cart_item_key ) ) {

                            $qty_type = $child_item['_wapf_child']['qty_type']; 
                            if( $qty_type === 'parent' && ! $child_item['data']->has_enough_stock( $quantity ) ) {
                                wc_add_notice( __( "Cannot change product quantity because a linked child product has insufficient stock.", 'sw-wapf' ), 'error');
                                return false;
                            }
                        }
                    }
                }

            }

            return $is_valid;

        }

        private function cart_item_has_linked_products( $cart_item ): bool {

            if( empty( $cart_item ) || empty( $cart_item['wapf'] ) ) {
                return false;
            }

                        foreach ( $cart_item['wapf'] as $field ) {
                if( isset( $field['type'] ) && $field['type'] === 'products' ) {
                    return true;
                }
            }

                        return false;

        }

        public function update_quantity_in_cart( $parent_item_key, $quantity, $old_quantity, $cart ) {

            if( $this->adding_to_cart ) {
                return;
            }

                        if( ! empty( $cart->cart_contents[ $parent_item_key ] ) ) {

                $parent_item = $cart->cart_contents[ $parent_item_key ];
                $has_children = $this->cart_item_has_linked_products( $parent_item );

                if( $has_children ) {
                    foreach ( $cart->cart_contents as $child_item_key => $child_item ) {

                        if( $this->is_linked_product_cartitem( $child_item, $parent_item_key ) ) {

                                                        $qty_type = $child_item['_wapf_child']['qty_type'];

                            if( $qty_type !== 'custom' ) {

                                                                if( $child_item['data']->is_sold_individually() ) {
                                    $cart->set_quantity( $child_item_key, 1, false );
                                    continue;
                                }

                                                                switch ( $qty_type ) {
                                    case 'one': $cart->set_quantity( $child_item_key, 1, false ); break;
                                    case 'parent': $cart->set_quantity( $child_item_key, $quantity, false ); break;
                                    case 'relative': 
                                        $difference = $quantity - $old_quantity;
                                        if( $difference > 0 ) $qty = $child_item['quantity'] + ( $child_item['quantity'] * $difference );
                                        else $qty = $child_item['quantity'] * ( $quantity / $old_quantity );
                                        $cart->set_quantity( $child_item_key, $qty, false ); break;
                                }
                            }
                        }
                    }
                }
            }

        }

                private function build_cache_data( Field $field, $values, $qty, $main_product_id, $clone_idx ) {

            $is_qty_field       = $field->is( 'qty_selector' );
            $product_choices    = [];
            $values             = (array) $values;
            $is_manual          = $field->options['product_selection'] === 'manual';
            $products           = [];

            if( $is_qty_field ) {
                if( isset( $field->options['min_choices'] ) || isset( $field->options['max_choices'] ) ) {
                    $qty_type = 'relative'; 
                } else {
                    $qty_type = 'custom'; 
                }
            } else {
                $qty_type = $field->options['qty_method'] ?? 'one';
            }

            if( $is_manual ) {

                if ( empty( $field->options[ 'choices' ] ) ) {
                    return [ 'error' => false ];
                }

                $product_ids_with_a_value = [];

                foreach ( $values as $i => $value ) {

                    $the_choice = null;

                    $slug = $is_qty_field ? $i : $value; 

                    $the_choice = Enumerable::from( $field->options[ 'choices' ] )->firstOrDefault( function( $x ) use ( $slug ) {
                        return $x[ 'slug' ] === $slug;
                    } );

                    if ( empty( $the_choice ) ) {
                        return sprintf( __( 'Some selections of "%s" are invalid. Please refresh the page and try again.', 'sw-wapf' ), $field->get_label() );
                    }

                    $the_choice['pricing_type'] = $this->get_product_price_type( $field, $the_choice );
                    $the_choice['qty'] = $is_qty_field ? $value : ( $qty_type === 'one' ? 1 : $qty );

                    $the_choice = apply_filters( 'wapf/linked_products/cart_choice', $the_choice, $field, $main_product_id );

                    $product_choices[ intval( $the_choice[ 'id' ] ) ] = $the_choice;

                    if( ! empty( $value ) ) {
                        $product_ids_with_a_value[] = $the_choice[ 'id' ];
                    }

                }

                $products = Woocommerce_Service::get_products_by_id( $product_ids_with_a_value );

                if ( count( $products ) !== count( array_filter( $values ) ) ) {
                    return sprintf( __( 'Some selections of "%s" are no longer available for purchase.', 'sw-wapf' ), $field->get_label() );
                }

            } else { 

                $allowed_products = Woocommerce_Service::get_products_by_query( $field->options['product_query'], $main_product_id );

                if( count( $allowed_products ) < count( $values ) ) {
                    return sprintf(__( 'Some selections of "%s" are invalid. Please refresh the page and try again.', 'sw-wapf' ), $field->get_label() );
                }

                foreach( $values as $key => $value ) {

                    $product_id = $is_qty_field ? $key : $value; 
                    $allowed_product = null;

                    foreach ( $allowed_products as $p ) {
                        if ( $p->get_id() == $product_id ) { 
                            $allowed_product = $p;
                            break;
                        }
                    }

                    if( ! $allowed_product ) {
                        return sprintf(__( 'Some selections of "%s" are invalid. Please refresh the page and try again.', 'sw-wapf' ), $field->get_label() );
                    }

                    $the_choice = [
                        'slug'  => $allowed_product->get_id(),
                        'id'    => $allowed_product->get_id(),
                        'qty'   => $is_qty_field ? $value : ( $qty_type === 'one' ? 1 : $qty )
                    ];

                    $the_choice['pricing_type']             = $this->get_product_price_type( $field, $field->options['product_query'] );
                    $the_choice                             = apply_filters( 'wapf/linked_products/cart_choice', $the_choice, $field, $main_product_id ); 
                    $product_choices[ $the_choice['id'] ]   = $the_choice;
                    $products[]                             = $allowed_product;

                }
            }

            return [
                'products_to_check' => $products,
                'qty_method'        => $qty_type,
                'choices'           => $product_choices, 
            ];

                    }

                public function validate_cart( $error, $values, Field $field, $main_product_id, $clone_idx, $qty, $get_value_from_cart_item, $cart_item_data ) {

            if( $error['error'] || $field->type !== 'products' ) {
                return $error;
            }

                        $cache_data = $this->build_cache_data( $field, $values, $qty, $main_product_id, $clone_idx );

                        if ( is_string( $cache_data ) ) {
                return [
                    'error' => true,
                    'message' => $cache_data
                ];
            }

            $cache_data[ 'products' ] = [];

            foreach ( $cache_data[ 'products_to_check' ] as $product ) {

                $product_id = $product->get_id();

                if ( ! $product->is_purchasable() ) {
                    return [
                        'error' => true,
                        'message' => sprintf( __( 'The product "%s" is no longer available for purchase.', 'sw-wapf' ), $product->get_name() )
                    ];
                }

                if ( ! $product->is_in_stock() ) {
                    return [
                        'error' => true,
                        'message' => sprintf( __( 'The product "%s" is no longer in stock.', 'sw-wapf' ), $product->get_name() )
                    ];
                }

                $needed_qty = $cache_data[ 'choices' ][ $product_id ][ 'qty' ];

                if ( ! $product->has_enough_stock( $needed_qty ) ) {
                    return [
                        'error' => true,
                        'message' => sprintf( __( 'The product "%s" doesn\'t have enough stock. Please select a smaller quantity. ', 'sw-wapf' ), $product->get_name() )
                    ];
                }

                if ( ! isset( $cache_data[ 'products' ][ $product_id ] ) ) {
                    $cache_data[ 'products' ][ $product_id ] = [
                        'qty' => 0,
                        'price_type' => $this->get_product_price_type( $field, $cache_data[ 'choices' ][ $product_id ] ), 
                        'product' => $product
                    ];
                }

                $cache_data[ 'products' ][ $product_id ][ 'qty' ] += $needed_qty;

            }

            if ( ! empty( $cache_data[ 'products' ] ) ) {
                unset( $cache_data[ 'products_to_check' ] );
                Cache::add_linked_products( $cache_data, $field->id, $clone_idx );
            }

                        return [ 'error' => false ];

        }

        public function expand_cart_item_field( $cart_item_field, Field $field, $clone_idx ) {

                        if( $cart_item_field['type'] === 'products' && ! Util::should_add_linked_products_to_cart() ) {

                $child_products = Cache::get_linked_products( $field->id, $clone_idx );

                if( empty( $child_products ) ) {
                    return $cart_item_field;
                }

                $product_data = [];

                foreach( $child_products['products'] as $product_id => $data ) {

                    $product_data[ $product_id ] = $data['qty'];

                                    }

                                if( ! empty( $product_data ) ) {
                    $cart_item_field[ 'child_product_data' ] = $product_data;
                }

                            }

            return $cart_item_field;

        }

                public function add_linked_products_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

            $this->add_linked_products_from_cart_item( $cart_item_key, $cart_item_data );

            Cache::remove_linked_products();

                        $this->adding_to_cart = false;

        }

                public function maybe_add_weight( $cart_obj ) {

                        if( $this->weight_was_calculated || Util::should_add_linked_products_to_cart() ) return;

                        foreach( $cart_obj->get_cart() as $cart_item ) {

                if( empty( $cart_item['wapf'] ) ) continue;

                                $cart_fields = Enumerable::from( $cart_item['wapf'] )->where( function( $x ){ return isset( $x['type'] ) && $x['type'] === 'products'; } )->toArray();

                                if( empty( $cart_fields ) ) continue;

                                $weight_to_add = 0;
                $product = $cart_item['data'];

                                if( $product->get_virtual() ) continue;

                                foreach ( $cart_fields as $cart_field ) {
                    if( ! empty( $cart_field['child_product_data'] ) ) {
                        foreach ( $cart_field['child_product_data'] as $product_id => $qty ) {
                            $product = wc_get_product( $product_id );
                            $weight = floatval( $product->get_weight() );
                            if( $weight > 0 ) $weight_to_add += ( $weight * $qty );
                        }
                    }
                }

                                if( $weight_to_add > 0 ) {
                    $new_weight = $weight_to_add + floatval( $product->get_weight() );
                    $product->set_weight( max( $new_weight, 0 ) );
                }

                            }

            $this->weight_was_calculated = true;

                    }

        public function set_linked_product_price( $cart_obj ) {

            $cart = $cart_obj->get_cart();

            foreach ( $cart as $cart_item ) {
                if( $this->is_linked_product_cartitem( $cart_item ) ) {
                    if( $cart_item['_wapf_child']['price_type'] === 'none' ) {
                        $cart_item['data']->set_price( 0 );
                        $cart_item['data']->set_sale_price( 0 );
                    }
                }
            }

        }

        public function remove_linked_products( $cart_obj ) {

            $cart = $cart_obj->get_cart();

            foreach ( $cart as $key => $cart_item ) {

                if( $this->is_linked_product_cartitem( $cart_item ) ) {

                    $parent_item = $cart[ $cart_item['_wapf_child'][ 'parent' ] ] ?? null;

                    if( ! $parent_item ) {
                        $cart_obj->remove_cart_item( $key );
                    }
                }
            }
        }

        private function add_linked_products_from_cart_item( $cart_item_key, &$cart_item_data ) {

            if( empty( $cart_item_data ) || empty( $cart_item_data['wapf'] ) ) {
                return;
            }

            if( ! Util::should_add_linked_products_to_cart() ) {
                return;
            }

            if( ! $this->cart_item_has_linked_products( $cart_item_data ) ) {
                return;
            }

            $product_controller = wapf_pro()->get_controller( 'product' );

                        foreach ( $cart_item_data[ 'wapf' ] as $child_product_item_field ) {

                if( ! isset( $child_product_item_field['type'] ) || $child_product_item_field['type'] !== 'products' ) {
                    continue;
                }

                $products = Cache::get_linked_products( $child_product_item_field['id'], $child_product_item_field['clone_idx'] );

                if( empty( $products ) ) continue;

                foreach( $products['products'] as $product_data ) {

                    if( empty( $product_data['qty'] ) ) {
                        continue;
                    }

                    $product        = $product_data['product'];
                    $is_variation   = strpos( $product->get_type(), 'variation' ) !== false;
                    $variation_id   = $is_variation ? $product->get_id() : 0;
                    $product_id     = $is_variation ? $product->get_parent_id() : $product->get_id();

                    remove_action( 'woocommerce_add_to_cart',       [ $product_controller, 'split_cart_items_by_quantity' ] );
                    remove_action( 'woocommerce_add_to_cart',       [ $this, 'add_linked_products_to_cart' ], 11 );
                    remove_action( 'woocommerce_add_cart_item_data',[ $product_controller, 'add_fields_to_cart_item' ] );

                    $item_data = [
                        '_wapf_child'       => [
                            'parent'        => $cart_item_key,
                            'qty_type'      => $products['qty_method'],
                            'price_type'    => $product_data['price_type'],
                            'hide_cart'     => $child_product_item_field['hide_cart'],
                            'hide_checkout' => $child_product_item_field['hide_checkout'],
                            'hide_order'    => $child_product_item_field['hide_order'],
                        ]
                    ];

                    WC()->cart->add_to_cart( $product_id, $product_data['qty'], $variation_id, [], $item_data );

                                        add_action( 'woocommerce_add_to_cart',          [ $product_controller, 'split_cart_items_by_quantity' ], 10, 6 );
                    add_action( 'woocommerce_add_to_cart',          [ $this, 'add_linked_products_to_cart' ], 11, 6 );
                    add_action( 'woocommerce_add_cart_item_data',   [ $product_controller, 'add_fields_to_cart_item' ], 10, 4 );

                                    }

            }

            Cache::remove_linked_products();

                    }

        #endregion

                #region Checkout and order functions

                public function add_child_meta_to_order_item( $order_item, $cart_item_key, $cart_item, $order ) {

            if( ! $this->is_linked_product_cartitem( $cart_item ) ) {
                return;
            }

            $order_item->add_meta_data(
                '_wapf_child',
                [
                    'hide_order' => $cart_item['_wapf_child']['hide_order']
                ]
            );

        }

                public function expand_order_item_field ( $order_meta_field, $cart_item, $cart_item_field ) {

                        if( ! empty( $cart_item_field['child_product_data'] ) ) {
                $order_meta_field['child_product_data'] = $cart_item_field['child_product_data'];
            }

            return $order_meta_field;

                    }

                public function maybe_increase_stock_after_refund( $order_id, $refund_id ) {

            $refund_order = wc_get_order( $refund_id );
            $order = wc_get_order( $order_id );

            if( ! $order instanceof \WC_Order ) {
                return;
            }

            $refunded_items = $refund_order->get_items();

            if ( empty( $refunded_items ) ) {
                $refunded_items = $order->get_items();
            }

            $stock_reduced = [];
            $stock_reduce_failed = [];

            foreach ( $refunded_items as $order_item ) {

                if( ! $order_item->meta_exists( '_wapf_meta' ) ) {
                    continue;
                }

                $meta_data = $order_item->get_meta( '_wapf_meta', true );

                if( empty( $meta_data ) || empty( $meta_data['fields'] ) ) {
                    continue;
                }

                $fields = $meta_data['fields'];

                foreach ( $fields as $field ) {
                    if( ! empty( $field['child_product_data'] ) ) {
                        $operation = self::update_stock( $field['child_product_data'], 'increase' );
                        $stock_reduced = array_merge( $stock_reduced, $operation['success'] );
                        $stock_reduce_failed = array_merge( $stock_reduce_failed, $operation['fail'] );
                    }
                }

            }

            if( empty( $stock_reduced ) && empty( $stock_reduce_failed ) ) {
                return;
            }

            if( ! empty( $stock_reduce_failed ) ) {
                $order->add_order_note( sprintf(
                    __( 'STOCK FAILURE: stock levels could\'nt be adjusted after a refund for these child products: %s.', 'sw-wapf' ),
                    join(', ', $stock_reduce_failed )
                ), 0, false );
            }

            if( ! empty( $stock_reduced ) ) {
                $order->add_order_note( sprintf(
                    __( 'Stock levels adjusted after a refund for the following child products: %s.', 'sw-wapf' ),
                    join(', ', $stock_reduced )
                ), 0, false );
            }

                    }

                public function maybe_increase_stock_after_cancel( $order ) {

                        if( ! $order instanceof \WC_Order ) {
                return;
            }

            $items = $order->get_items();

            $stock_reduced = [];
            $stock_reduce_failed = [];

            foreach ( $items as $order_item ) {

                if( ! $order_item->meta_exists( '_wapf_meta' ) ) {
                    continue;
                }

                $meta_data = $order_item->get_meta( '_wapf_meta', true );

                if( empty( $meta_data ) || empty( $meta_data['fields'] ) ) {
                    continue;
                }

                $fields = $meta_data['fields'];

                foreach ( $fields as $field ) {
                    if( ! empty( $field['child_product_data'] ) ) {
                        $operation = self::update_stock( $field['child_product_data'], 'increase' );
                        $stock_reduced = array_merge( $stock_reduced, $operation['success'] );
                        $stock_reduce_failed = array_merge( $stock_reduce_failed, $operation['fail'] );
                    }
                }

            }

            if( empty( $stock_reduced ) && empty( $stock_reduce_failed ) ) {
                return;
            }

            if( ! empty( $stock_reduce_failed ) ) {
                $order->add_order_note( sprintf(
                    __( 'STOCK FAILURE: stock levels could\'nt be increased for the following child products: %s.', 'sw-wapf' ),
                    join(', ', $stock_reduce_failed )
                ), 0, false );
            }

            if( ! empty( $stock_reduced ) ) {
                $order->add_order_note( sprintf(
                    __( 'Stock levels increased for the following child products: %s.', 'sw-wapf' ),
                    join(', ', $stock_reduced )
                ), 0, false );
            }

                    }

                public function maybe_reduce_stock( $order ) {

            if( ! $order instanceof \WC_Order ) {
                return;
            }

            $items = $order->get_items();

                        $stock_reduced = [];
            $stock_reduce_failed = [];

            foreach ( $items as $order_item ) {

                if( ! $order_item->meta_exists( '_wapf_meta' ) ) {
                    continue;
                }

                                $meta_data = $order_item->get_meta( '_wapf_meta', true );

                                if( empty( $meta_data ) || empty( $meta_data['fields'] ) ) {
                    continue;
                }

                                $fields = $meta_data['fields'];

                                foreach ( $fields as $field ) {

                    if( ! empty( $field['child_product_data'] ) ) {

                                                $operation = self::update_stock( $field['child_product_data'] );
                        $stock_reduced = array_merge( $stock_reduced, $operation['success'] );
                        $stock_reduce_failed = array_merge( $stock_reduce_failed, $operation['fail'] );

                                            }

                                    }

                            }

                        if( empty( $stock_reduced ) && empty( $stock_reduce_failed ) ) {
                return;
            }

                        if( ! empty( $stock_reduce_failed ) ) {
                $order->add_order_note( sprintf(
                    __( 'STOCK FAILURE: stock levels could\'nt be adjusted for the following child products: %s.', 'sw-wapf' ),
                    join(', ', $stock_reduce_failed )
                ), 0, false );
            }

                        if( ! empty( $stock_reduced ) ) {
                $order->add_order_note( sprintf(
                    __( 'Stock levels reduced for the following child products: %s.', 'sw-wapf' ),
                    join(', ', $stock_reduced )
                ), 0, false );
            }

        }

        private function update_stock( $stock_reduction_data, $operation = 'decrease' ): array {

            $success_ids = [];
            $filtered_data = [];

            foreach( $stock_reduction_data as $product_id => $qty ) {
                $product = wc_get_product( $product_id );
                if( $product && $product->managing_stock() )
                    $filtered_data[ $product_id ] = $qty;
            }

            foreach( $filtered_data as $product_id => $quantity ) {

                $quantity = intval( $quantity );

                if( $quantity <= 0 ) continue;

                $result = wc_update_product_stock( intval( $product_id ), $quantity, $operation );

                if( $result !== false )
                    $success_ids[] = $product_id;

            }

            $needed_products = array_keys( $filtered_data );
            $fail_ids = array_diff( $needed_products, $success_ids );

            return [
                'success' => $success_ids,
                'fail' => $fail_ids,
            ];

        }

        #endregion

                #region Frontend functions

                public function define_products_and_defaults_on_model( $model, Field $field, $fieldgroup_id, $product, $cart_item_field ) {

                        if( $field->type !== 'products' ) return $model;

            $manual = ! isset( $field->options['product_selection'] ) ||  $field->options['product_selection'] === 'manual';
            $product_choices = [];

            add_filter('woocommerce_product_backorders_require_notification', '__return_false', 166 );

            if( $manual ) {

                if( ! empty( $field->options['choices'] ) ) {

                    $ids = [];

                    foreach ( $field->options['choices'] as $data ) {
                        $ids[] = $data['id'];
                    }

                    $found_products = Woocommerce_Service::get_product_choices( $ids , $product );

                    foreach ( $field->options['choices'] as $i => $choice ) {

                        if( ! isset( $found_products[ $choice['id'] ] ) ) continue;

                        $the_product        = $found_products[ $choice['id'] ];
                        $default            = isset( $model['default'] ) && is_array( $model['default'] ) && in_array( $choice['slug'], $model['default'] );
                        $choice['selected'] = $default > 0;
                        $choice['product']  = $the_product;

                        $choice = $this->expand_product_choice( $choice, $field, $the_product );

                        $product_choices[] = $choice;

                    }

                }
            } else {
                if( ! empty( $field->options['product_query'] ) ) {

                    $found_products = Woocommerce_Service::get_products_by_query( $field->options[ 'product_query' ], $product );

                    foreach ( $found_products as $the_product ) {

                        $choice = [
                            'selected'      => isset( $model['default'] ) && is_array( $model['default'] ) && in_array( $the_product->get_id(), $model['default'] ),
                            'disabled'      => false,
                            'slug'          => $the_product->get_id(),
                            'product'       => $the_product,
                            'pricing_type'  => $field->options[ 'product_query' ][ 'pricing_type' ] ?? 'fixed'
                        ];

                        $choice = $this->expand_product_choice( $choice, $field, $the_product );

                        $product_choices[] = $choice;

                    }
                }
            }

            remove_filter('woocommerce_product_backorders_require_notification', '__return_false', 166 );

            $model['data']['product_choices'] = $product_choices;

            if( $model['is_edit'] && ! empty( $product_choices ) && ! empty( $cart_item_field ) && $field->type === 'products' && $field->is( 'qty_selector' ) && $field->options['product_selection'] !== 'manual' ) {

                $defaults = [];

                                foreach ( $product_choices as $choice ) {
                    $product_id = $choice['slug'];
                    $v = Enumerable::from( $cart_item_field['values'] )->firstOrDefault( function( $x ) use ( $product_id ) { return $x['slug'] == $product_id; });
                    $defaults[] = $v ? $v['label'] : 0;
                }

                                $model['default'] = $defaults;

                        }

            return $model;

                    }

        public function maybe_add_pricing_class( $classes, $field ) {

                        if ( $field->type !== 'products' ) {
                return $classes;
            }

            $is_manual = ! isset( $field->options['product_selection'] ) || $field->options['product_selection'] === 'manual';

            if ( $is_manual ) {
                if ( $field->pricing_enabled() ) {
                    $classes[] = 'has-pricing';
                }
            } else {
                $pricing_type = $field->options['product_query']['pricing_type'] ?? '';
                if ( $pricing_type !== 'none' ) {
                    $classes[] = 'has-pricing';
                }
            }

            return $classes;

                    }

                #endregion

                #region Backend function

                public function add_products_field_type( $fields ) {

                        $fields['products'] = [
                'id'            => 'products',
                'title'         => __('Products','sw-wapf'),
                'description'   => __('Allow customers to select existing products from your store.', 'sw-wapf'),
                'type'          => 'field',
                'group_label'   => __('Advanced', 'sw-wapf'),
                'icon'          => '<svg xmlns="http://www.w3.org/2000/svg" fill="#828282" width="16" height="16" viewBox="0 0 448 512"><path d="M447.9 176c0-10.6-2.6-21-7.6-30.3l-49.1-91.9c-4.3-13-16.5-21.8-30.3-21.8H87.1c-13.8 0-26 8.8-30.4 21.9L7.6 145.8c-5 9.3-7.6 19.7-7.6 30.3C.1 236.6 0 448 0 448c0 17.7 14.3 32 32 32h384c17.7 0 32-14.3 32-32 0 0-.1-211.4-.1-272zm-87-112l50.8 96H286.1l-12-96h86.8zM192 192h64v64h-64v-64zm49.9-128l12 96h-59.8l12-96h35.8zM87.1 64h86.8l-12 96H36.3l50.8-96zM32 448s.1-181.1.1-256H160v64c0 17.7 14.3 32 32 32h64c17.7 0 32-14.3 32-32v-64h127.9c0 74.9.1 256 .1 256H32z"/></svg>',
            ];

            return $fields;

                    }

                public function add_field_visibility_conditions( $conditions ) {

                        $products_conditions = [
 [
                    'type'          => 'products-dropdown',
                    'conditions'    => [
                        ['value' => '==', 'label' => __('Value is equal to','sw-wapf'), 'type' => 'product'],
                        ['value' => '!=', 'label' => __('Value is not equal to','sw-wapf'), 'type' => 'product'],
                        ['value' => 'empty', 'label' => __('Nothing selected','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Anything selected','sw-wapf'), 'type' => false],
                    ]
                ], [
                    'type'          => 'products-checkbox',
                    'conditions'    => [
                        ['value' => '==', 'label' => __('Selection contains','sw-wapf'), 'type' => 'product'], 
                        ['value' => '!=', 'label' => __('Selection does not contain','sw-wapf'), 'type' => 'product'],
                        ['value' => 'empty', 'label' => __('Nothing selected','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Anything selected','sw-wapf'), 'type' => false],
                    ]   
                ], [
                    'type'          => 'products-radio',
                    'conditions'    => [
                        ['value' => '==', 'label' => __('Value is equal to','sw-wapf'), 'type' => 'product'],
                        ['value' => '!=', 'label' => __('Value is not equal to','sw-wapf'), 'type' => 'product'],
                        ['value' => 'empty', 'label' => __('Nothing selected','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Anything selected','sw-wapf'), 'type' => false],
                    ]
                ], [
                    'type'          => 'products-image',
                    'conditions'    => [
                        ['value' => '==', 'label' => __('Selection contains','sw-wapf'), 'type' => 'product'],
                        ['value' => '!=', 'label' => __('Selection does not contain','sw-wapf'), 'type' => 'product'],
                        ['value' => 'empty', 'label' => __('Nothing selected','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Anything selected','sw-wapf'), 'type' => false],
                    ]
                ], [
                    'type'          => 'products-card',
                    'conditions'    => [
                        ['value' => '==', 'label' => __('Selection contains','sw-wapf'), 'type' => 'product'],
                        ['value' => '!=', 'label' => __('Selection does not contain','sw-wapf'), 'type' => 'product'],
                        ['value' => 'empty', 'label' => __('Nothing selected','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Anything selected','sw-wapf'), 'type' => false],
                    ]
                ], [
                    'type'          => 'products-vcard',
                    'conditions'    => [
                        ['value' => '==', 'label' => __('Selection contains','sw-wapf'), 'type' => 'product'],
                        ['value' => '!=', 'label' => __('Selection does not contain','sw-wapf'), 'type' => 'product'],
                        ['value' => 'empty', 'label' => __('Nothing selected','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Anything selected','sw-wapf'), 'type' => false],
                    ]
                ], [
                    'type'          => 'products-card-qty',
                    'conditions'    => [
                        ['value' => 'empty', 'label' => __('No quantity','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Any quantity','sw-wapf'), 'type' => false],
                    ]
                ], [
                    'type'          => 'products-vcard-qty',
                    'conditions'    => [
                        ['value' => 'empty', 'label' => __('No quantities given','sw-wapf'), 'type' => false],
                        ['value' => '!empty', 'label' => __('Any quantity given','sw-wapf'), 'type' => false],
                    ]
                ],
            ];

            return array_merge( $conditions, $products_conditions );

                    }

        public function add_products_field_options( $options ) {

                        $options['products'] = [
                [
                    'type'          => 'select',
                    'id'            => 'subtype',
                    'label'         => __('Display products as','sw-wapf'),
                    'options'       => [
                        __( 'Simple choices', 'sw-wapf' ) => [
                            'checkbox'  => __('Checkboxes','sw-wapf'),
                            'radio'     => __('Radio buttons','sw-wapf'),
                            'dropdown'  => __('Select list','sw-wapf'),
                            'image'     => __( 'Images', 'sw-wapf'),
                        ],
                        __( 'Product cards', 'sw-wapf' ) => [
                            'card'          => __( 'Horizontal cards', 'sw-wapf'),
                            'card-qty'      => __( 'Horizontal cards with quantities', 'sw-wapf'),
                            'vcard'         => __( 'Vertical cards', 'sw-wapf'),
                            'vcard-qty'     => __( 'Vertical cards with quantities', 'sw-wapf'),
                        ],

                                            ],
                    'default'       => 'checkbox',
                ], [
                    'type'                  => 'select',
                    'id'                    => 'label_pos',
                    'label'                 => __('Label position & display','sw-wapf'),
                    'description'           => __('How to display the swatch label?'),
                    'options'               => [
                        'default'           => __('Below image (inside selection)','sw-wapf'),
                        'out'               => __('Below image (outside selection)','sw-wapf'),
                        'hide'              => __('Hide the label','sw-wapf'),
                        'tooltip'           => __('As tooltip','sw-wapf'),
                    ],
                    'default'               => 'tooltip',
                    'show_if'               => "subtype | eq 'image'",
                    'tab'                   => 'appearance'
                ], [
                    'type'          => 'number',
                    'id'            => 'item_width',
                    'label'         => __( 'Swatch width', 'sw-wapf' ),
                    'postfix'       => __( 'pixels', 'sw-wapf' ),
                    'min'           => 30,
                    'max'           => 300,
                    'tab'           => 'appearance',
                    'show_if'       => "subtype | eq 'image'",
                    'default'       => 68
                ], [
                    'type'          => 'toggles',
                    'options'               => [
                        [ 'id' => 'incl_img', 'label' => __("Product image",'sw-wapf') ],
                        [ 'id' => 'incl_desc', 'label' => __("Product description",'sw-wapf') ],
                    ],
                    'label'                 => __('Included components','sw-wapf'),
                    'description'           => __("Define what info should be displayed on the cards.",'sw-wapf'),
                    'show_if'               => "subtype | in 'card,vcard,vcard-qty,card-qty'",
                    'tab'                   => 'appearance',
                ], [
                    'type'                  => 'selects',
                    'label'                 => __('Extra info components','sw-wapf'),
                    'description'           => __("Define what extra info should be shown on the cards.",'sw-wapf'),
                    'tab'                   => 'appearance',
                    'show_if'               => "subtype | in 'card,vcard,vcard-qty,card-qty'",
                    'lists'                 => [
                        [
                            'id'            => 'slot_1',
                            'title'         => __('Slot 1 (next to title)','sw-wapf'),
                            'options'       => [
                                'none'      => __('No data','sw-wapf'),
                                'price'     => __('Product price','sw-wapf'),
                                'stock'     => __('Stock','sw-wapf'),
                                'link'      => __('Details link','sw-wapf'),
                            ],
                            'default'       => 'price'
                        ], [
                            'id'            => 'slot_2',
                            'title'         => __('Slot 2 (bottom)','sw-wapf'),
                            'options'       => [
                                'none'      => __('No data','sw-wapf'),
                                'price'     => __('Product price','sw-wapf'),
                                'stock'     => __('Stock','sw-wapf'),
                                'link'      => __('Details link','sw-wapf'),                            
                            ],
                            'default'       => 'none'
                        ], [
                            'id'            => 'slot_3',
                            'title'         => __('Slot 3 (bottom)','sw-wapf'),
                            'options'       => [
                                'none'      => __('No data','sw-wapf'),
                                'price'     => __('Product price','sw-wapf'),
                                'stock'     => __('Stock','sw-wapf'),
                                'link'      => __('Details link','sw-wapf'),                           
                            ],
                            'default'       => 'none'
                        ]
                    ]
                ], [
                    'type'          => 'child-product-search',
                    'id'            => 'products',
                    'label'         => __("Included products",'sw-wapf'),
                    'multi_option'  => true,
                    'show_if'       => "subtype | notin 'vcard-qty,card-qty'",
                ], [
                    'type'          => 'child-product-search',
                    'id'            => 'products',
                    'label'         => __("Included products",'sw-wapf'),
                    'multi_option'  => true,
                    'show_if'       => "subtype | in 'vcard-qty,card-qty'",
                    'has_quantities'=> true,
                    'inputs'                => [
                        [
                            'title'         => 'Default',
                            'type'          => 'number',
                            'key'           => 'default'
                        ],
                        [
                            'title'         => 'Min.',
                            'type'          => 'number',
                            'key'           => 'min'
                        ],
                        [
                            'title'         => 'Max.',
                            'type'          => 'number',
                            'key'           => 'max'
                        ]
                    ],
                ], [
                    'type'                  => 'selects',
                    'label'                 => __('Items per row','sw-wapf'),
                    'description'           => __('Max. swatches per row.','sw-wapf'),
                    'tab'                   => 'appearance',
                    'show_if'               => "subtype | in 'card,card-qty'",
                    'lists'                 => [
                        [
                            'id'            => 'items_per_row',
                            'title'         => __('On desktop','sw-wapf'),
                            'options'       => [
                                1           => '1',
                                2           => '2',
                                3           => '3',
                            ],
                            'default'       => 1
                        ], [
                            'id'            => 'items_per_row_tablet',
                            'title'         => __('On tablet','sw-wapf'),
                            'options'       => [
                                1           => '1',
                                2           => '2',
                                3           => '3',
                            ],
                            'default'       => 1
                        ], [
                            'id'            => 'items_per_row_mobile',
                            'title'         => __('On mobile','sw-wapf'),
                            'options'       => [
                                1           => '1',
                                2           => '2',
                                3           => '3',
                            ],
                            'default'       => 1
                        ]
                    ]
                ],[
                    'type'                  => 'selects',
                    'label'                 => __('Items per row','sw-wapf'),
                    'description'           => __('Max. items per row.','sw-wapf'),
                    'show_if'               => "subtype | in 'vcard,vcard-qty'",
                    'tab'                   => 'appearance',
                    'lists'                 => [
                        [
                            'id'            => 'items_per_row',
                            'title'         => __('On desktop','sw-wapf'),
                            'options'       => [
                                1           => '1',
                                2           => '2',
                                3           => '3',
                                4           => '4',
                            ],
                            'default'       => 2
                        ], [
                            'id'            => 'items_per_row_tablet',
                            'title'         => __('On tablet','sw-wapf'),
                            'options'       => [
                                1           => '1',
                                2           => '2',
                                3           => '3',
                                4           => '4',
                            ],
                            'default'       => 1
                        ], [
                            'id'            => 'items_per_row_mobile',
                            'title'         => __('On mobile','sw-wapf'),
                            'options'       => [
                                1           => '1',
                                2           => '2',
                                3           => '3',
                                4           => '4',
                            ],
                            'default'       => 1
                        ]
                    ]
                ], [
                    'type'          => 'select',
                    'id'            => 'display',
                    'label'         => __('Quantity input display','sw-wapf'),
                    'description'   => __('How to display the quantity fields?','sw-wapf'),
                    'options'       => [
                        'default'   => __('As a standard number field','sw-wapf'),
                        'plus_min'  => __('With plus & minus buttons','sw-wapf')
                    ],
                    'default'       => 'default',
                    'show_if'       => "subtype | in 'card-qty,vcard-qty'",
                    'tab'           => 'appearance',
                ], [
                    'type'          => 'select',
                    'id'            => 'img_fit',
                    'label'         => __('Image fitting','sw-wapf'),
                    'description'   => __('How to display the image?','sw-wapf'),
                    'options'       => [
                        'cover'   => __('Fill container','sw-wapf'),
                        'contain'  => __('Scale to fit','sw-wapf')
                    ],
                    'modal'         => [
                        'button'    => __( 'What is this?', 'sw-wapf' ),
                        'title'     => __( 'Image fitting mode', 'sw-wapf' ),
                        'content'   => __( 'This setting determines how the image should be displayed within its container. <ul><li><strong>Fill container:</strong> The image fills the container (and maintains aspect ratio). It might crop parts of the image to ensure it fully covers the container.</li><li><strong>Scale to fit:</strong> The image is scaled to fit entirely within the container. It may leave empty space around it, so this is ideal for images with a transparent background.</li></ul>', 'sw-wapf' )
                    ],
                    'default'       => 'cover',
                    'show_if'       => "subtype | in 'vcard-qty,vcard'",
                    'tab'           => 'appearance',
                ], [
                    'type'          => 'select',
                    'id'            => 'qty_method',
                    'label'         => __("Quantity selection",'sw-wapf'),
                    'options'       => [
                        'one'       => __('Just one','sw-wapf'),
                        'parent'    => __('Same as parent','sw-wapf'),
                    ],
                    'note'          => __( 'How many products should be added to cart per selection?', 'sw-wapf' ),
                    'default'       => 'one',
                    'show_if'       => "subtype | notin 'card-qty,vcard-qty'",
                    'tab'       => 'general',
                ], [
                    'type'                  => 'numbers',
                    'label'                 => __('Selection limits','sw-wapf'),
                    'description'           => __('The min and max choices required.','sw-wapf'),
                    'note'                  => __("Set both to '1' if you only allow a single choice. Leave blank if you don't require a min or max.",'sw-wapf'),
                    'numbers'               => [
                        [
                            'title'         => __('Minimum needed','sw-wapf'),
                            'id'            => 'min_choices',
                            'min'           => '1',
                        ],
                        [
                            'title'         => __('Maximum allowed','sw-wapf'),
                            'id'            => 'max_choices',
                            'min'           => '1',
                        ]
                    ],
                    'show_if'       => "subtype | in 'card,vcard,checkbox,image'",
                ], [
                    'type'                  => 'numbers',
                    'label'                 => __('Selection limits','sw-wapf'),
                    'description'           => __('Set the min and max choices needed (over all choices)','sw-wapf'),
                    'note'                  => __("Leave blank if you don't require a min or max.",'sw-wapf'),
                    'numbers'               => [
                        [
                            'title'         => __('Minimum needed','sw-wapf'),
                            'id'            => 'min_choices',
                            'min'           => '1',
                        ],
                        [
                            'title'         => __('Maximum allowed','sw-wapf'),
                            'id'            => 'max_choices',
                            'min'           => '1',
                        ]
                    ],
                    'show_if'       => "subtype | in 'card-qty,vcard-qty'",
                ],
            ];

            return $options;
        }

        public function product_picker_ajax() {

                        if( ! current_user_can( wapf_get_setting('capability') ) ) {
                echo json_encode( [] );
                wp_die();
            }

                        $ref = wp_get_referer();
            $exclude = [];

                        if ( is_string( $ref ) && preg_match('/post\.php\?post=(\d+)&action=edit/', $ref, $matches ) ) {

                               $post_id = intval( $matches[1]) ;

                if ( $post_id > 0 && get_post_type( $post_id ) === 'product' ) {
                    $exclude[] = $post_id;
                }
            }

            echo json_encode( Woocommerce_Service::find_products_by_name( $_POST['term'], true, false, $exclude ) );
            wp_die();
        }

                public function sanitize_field_data( Field &$field, &$raw_field ) {

                        if( $field->type !== 'products' ) {
                return;
            }

                        if( isset( $raw_field['product_selection'] ) ) {

                $selection_mode = Helper::sanitize( $raw_field['product_selection'], 'options', 'manual', [ 'manual', 'category' ] );
                $field->options['product_selection'] = $selection_mode;

                if( $selection_mode !== 'manual' ) {

                    if( empty( $raw_field['product_query'] ) || empty( $raw_field['product_query']['query_id'] ) ) return;

                    $query_id = Helper::sanitize( $raw_field['product_query']['query_id'], 'int', 0 );

                    if( empty( $query_id ) ) return;

                    $query = [
                        'query_id' => $query_id,
                        'query_label' => Helper::sanitize( $raw_field['product_query']['query_label'], 'text', '' )
                    ];

                    if ( isset( $raw_field['product_query']['limit'] ) ) {
                        $query['limit'] = Helper::sanitize( $raw_field['product_query']['limit'], 'int', 5 );
                        $query['limit'] = min( 50, max( $query['limit'], 1 ) );
                    } else $query['limit'] = 5;

                    if ( isset( $raw_field['product_query']['sort'] ) ) {
                        $query['sort'] = Helper::sanitize( $raw_field['product_query']['sort'], 'options', 'date_desc', [ 'date_desc', 'date_asc', 'name_asc', 'name_desc' ] );
                    } else $query['sort'] = 'date_desc';

                    if ( isset( $raw_field['product_query']['pricing_type'] ) ) {
                        $query['pricing_type'] = Helper::sanitize( $raw_field['product_query']['pricing_type'], 'options', 'fixed', [ 'fixed', 'none' ] );
                    } else $query['pricing_type'] = 'fixed';

                                        $field->options['product_query'] = $query;

                }

                            }

                        if( isset( $field->subtype ) ) {

                if( in_array( $field->subtype, [ 'vcard-qty', 'card-qty', 'card', 'vcard' ] ) ) {

                    $field->options['slot_1'] = isset( $raw_field['slot_1'] ) ? Helper::sanitize( $raw_field['slot_1'], 'options', 'none', [ 'none', 'price', 'stock', 'link' ] ) : 'none';
                    $field->options['slot_2'] = isset( $raw_field['slot_2'] ) ? Helper::sanitize( $raw_field['slot_2'], 'options', 'none', [ 'none', 'price', 'stock', 'link' ] ) : 'none';
                    $field->options['slot_3'] = isset( $raw_field['slot_3'] ) ? Helper::sanitize( $raw_field['slot_3'], 'options', 'none', [ 'none', 'price', 'stock', 'link' ] ) : 'none';

                                        $field->options['incl_img'] = isset( $raw_field['incl_img'] ) ? Helper::sanitize( $raw_field['incl_img'], 'bool', false ) : true;
                    $field->options['incl_desc'] = isset( $raw_field['incl_desc'] ) ? Helper::sanitize( $raw_field['incl_desc'], 'bool', false ) : true;
                    $field->options['incl_link'] = isset( $raw_field['incl_link'] ) ? Helper::sanitize( $raw_field['incl_link'], 'bool', false ) : true;
                    $field->options['incl_price'] = isset( $raw_field['incl_price'] ) ? Helper::sanitize( $raw_field['incl_price'], 'bool', false ) : true;
                    $field->options['incld_stock'] = isset( $raw_field['incld_stock'] ) ? Helper::sanitize( $raw_field['incld_stock'], 'bool', false ) : true;
                } else {
                    unset( $raw_field['incl_img'] ); 
                    unset( $raw_field['incl_desc'] );
                    unset( $raw_field['incl_link'] );
                    unset( $raw_field['incl_price'] );
                    unset( $raw_field['incld_stock'] );

                                        unset( $raw_field['slot_1'] );
                    unset( $raw_field['slot_2'] );
                    unset( $raw_field['slot_3'] );

                                    }

                if( in_array( $field->subtype, [ 'radio', 'dropdown' ] ) ) {
                    unset( $field->options['min_choices'] );
                    unset( $field->options['max_choices'] );
                    unset( $raw_field['min_choices'] );
                    unset( $raw_field['max_choices'] );
                }

                                if( $field->subtype !== 'image' ) {
                    unset( $field->options['label_pos'] );
                    unset( $field->options['item_width'] );
                    unset( $raw_field['item_width'] );
                }

                                if( ! in_array( $field->subtype, [ 'vcard-qty', 'card-qty'] ) ) {
                    unset( $raw_field['display'] );
                    unset( $field->options['display'] );
                }

                if( in_array( $field->subtype, [ 'vcard-qty', 'card-qty' ] ) ) {
                    unset( $raw_field['qty_method'] );
                    unset( $field->options['qty_method'] );
                }

                if( ! in_array( $field->subtype, [ 'vcard-qty', 'card-qty', 'card', 'vcard' ] ) ) {
                    unset( $field->options['items_per_row'] );
                    unset( $field->options['items_per_row_mobile'] );
                    unset( $field->options['items_per_row_tablet'] );
                }

                if( ! in_array( $field->subtype, [ 'vcard-qty', 'vcard'] ) ) {
                    unset( $field->options['img_fit'] );
                    unset( $raw_field['img_fit'] );
                }

                            }

        }

                #endregion

                #region Private helpers

                private function has_slot_for( $field, $for ): bool {
            if( ! empty( $field->options['slot_1'] ) && $field->options['slot_1'] === $for ) return true;    
            if( ! empty( $field->options['slot_2'] ) && $field->options['slot_2'] === $for ) return true;    
            if( ! empty( $field->options['slot_3'] ) && $field->options['slot_3'] === $for ) return true;    
            return false;
        }

                private function expand_product_choice( $choice, Field $field, \WC_Product $product ) {

            $price_type = $this->get_product_price_type( $field, $choice );

            $choice['label']            = $product->get_name();
            $choice['pricing_type']     = $price_type;
            $choice['pricing_amount']   = $price_type === 'none' ? 0 : floatval( $product->get_price() );
            $choice['attachment']       = $product->get_image_id(); 

                        $choice['link']             = $this->has_slot_for( $field, 'link' ) ? $product->get_permalink() : '';
            $choice['price']            = $this->has_slot_for( $field, 'price' ) ? Helper::format_product_price( $product ) : '';
            if( $this->has_slot_for( $field, 'stock' ) ) {
                $choice['stock']        = $product->get_availability()['availability'];
                if( empty( $choice['stock'] ) ) $choice['stock'] = __( 'In stock', 'woocommerce' );
            }
            if ( ! empty( $field->options['incl_desc'] ) ) {
                $short_desc             = $product->get_short_description();
                $choice['desc']         = ! empty( $short_desc ) ? $short_desc : $product->get_description();
            }

            return apply_filters( 'wapf/linked_products/choice', $choice, $field, $product );

                    }

        private function is_linked_product_cartitem( $cart_item, $from_item_key = null ): bool {

            if( ! isset( $cart_item[ '_wapf_child' ] ) ) {
                return false;
            }

            if( $from_item_key ) {

                $parent_item = WC()->cart->get_cart_item( $from_item_key );
                return ! empty( $parent_item ) &&  $cart_item['_wapf_child']['parent'] === $from_item_key;

            }

            return true;

        }

        private function get_product_price_type( Field $field, $choice ): string {

            if( isset( $choice['pricing_type'] ) && $choice['pricing_type'] === 'none' ) {
                return 'none';
            }

            if( $field->is( 'qty_selector' ) ) {
                return 'nr';  
            }

                        $qty_method = $field->options[ 'qty_method' ] ?? 'one';

            return $qty_method === 'one' ? 'fixed' : 'qt';

                    }

                #endregion

    }
}