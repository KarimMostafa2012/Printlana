<?php

namespace SW_WAPF_PRO\Includes\Controllers {

    use SW_WAPF_PRO\Includes\Classes\Config;
    use SW_WAPF_PRO\Includes\Classes\Enumerable;
    use SW_WAPF_PRO\Includes\Classes\Field_Groups;
    use SW_WAPF_PRO\Includes\Classes\Helper;
    use SW_WAPF_PRO\Includes\Models\Field;

    if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	class Extended_Controller {

                private $weight_was_calculated = false;

		public function __construct() {

			add_action( 'wp_enqueue_scripts',                   [ $this, 'register_assets'] );

			add_action( 'admin_init',                           [ $this,'deactivate_other_versions'] );

            add_filter( 'wapf/field_options',                   [ $this, 'add_field_settings' ], 12 );

            add_filter( 'wapf/field_types',                     [ $this, 'add_field_types' ] );

            add_filter( 'wapf/cart/cart_item_field',            [ $this, 'calculator_cart_item_field' ], 10, 3 );
            add_filter( 'wapf/cart/item_values_label',          [ $this, 'calculator_cart_item_value_label' ], 10, 4 );

            add_filter( 'wapf/cart/cart_item_field',            [ $this, 'weight_cart_item_field' ], 10, 3 );
            add_action( 'woocommerce_before_calculate_totals',  [ $this, 'maybe_calculate_weight' ] );

            $path = wapf_get_setting( 'path' );
			include_once( $path . 'extend/formulas.php' );
			include_once( $path . 'extend/date.php' );

		}

                public function add_field_settings( $options ) {

            $weight_unit            = get_option( 'woocommerce_weight_unit' );
            $weight_title           = sprintf( __( 'Weight (%s)', 'swp-wapf' ), $weight_unit );
            $weight_title_qty       = sprintf( __( 'Weight per qty (%s)', 'swp-wapf' ), $weight_unit );
            $extra_weight_title     = sprintf( __( 'Extra weight (%s)', 'swp-wapf' ), $weight_unit );
            $weight_descr           = __('Increase product weight when this field is used.', 'sw-wapf');
            $add_weight_options_to  = [ 'card', 'vcard', 'select', 'checkboxes', 'radio', 'image-swatch', 'image-swatch-qty', 'multi-image-swatch', 'color-swatch', 'multi-color-swatch', 'text-swatch', 'multi-text-swatch' ];
            $days                   = [
                0 => __('Sunday','sw-wapf'),
                1 => __('Monday','sw-wapf'),
                2 => __('Tuesday','sw-wapf'),
                3 => __('Wednesday','sw-wapf'),
                4 => __('Thursday','sw-wapf'),
                5 => __('Friday','sw-wapf'),
                6 => __('Saturday','sw-wapf'),
            ];

            $options['calc'] = [
                [
                    'type'          => 'select',
                    'id'            => 'calc_type',
                    'label'         => __('Calculation type','sw-wapf'),
                    'options'       => [
                        'default'   => __('Informational calculation','sw-wapf'),
                        'cost'      => __('Cost calculation','sw-wapf')
                    ],
                    'default'       => 'default',
                    'description'   => __('Select the type of calculation.','sw-wapf'),
                    'note'          => __('Is the calculation being shown for informational purpose only, or should it also adjust the product price?', 'sw-wapf')
                ],
                [
                    'type'          => 'select',
                    'id'            => 'result_format',
                    'label'         => __('Result format','sw-wapf'),
                    'show_if'       => "calc_type | eq 'default'",
                    'options'       => [
                        'none'      => __('Don\'t apply formatting','sw-wapf'),
                        ''          => __('Format as number', 'sw-wapf'),
                    ],
                ],
                [
                    'type'          => 'formula-builder',
                    'id'            => 'formula',
                    'label'         => __('Formula','sw-wapf'),
                    'description'   => __('Build your formula.','sw-wapf'),
                ], [
                    'type'          => 'text',
                    'id'            => 'result_text',
                    'label'         => __('Result text','sw-wapf'),
                    'description'   => __('The text displayed before & after the result.','sw-wapf'),
                    'note'          => __('Enter the text to display before/after the calculation result. Use <i>{{result}}</i> to refer to the result. Leave blank if you only want to show the result.', 'sw-wapf')
                ],
            ];

            if( isset( $options['date'] ) && get_option( 'wapf_datepicker', 'no' ) === 'yes' ) {

                                $options['date'][] = [
                    'type' => 'select',
                    'multiple' => true,
                    'id' => 'disabled_days',
                    'label' => __( 'Disable days', 'sw-wapf' ),
                    'description' => __( "Define days that can't be selected.", 'sw-wapf' ),
                    'options' => $days,
                    'select2' => true,
                    'tab' => 'selection'
                ];

                $options['date'][] = [
                    'type' => 'text',
                    'id' => 'min_date',
                    'label' => __( 'Min. date', 'sw-wapf' ),
                    'note' => __( 'Use format mm-dd-yyyy or <a href="#" onclick="javascript:event.preventDefault();jQuery(\'.modal--dynamic-date\').show();">use a dynamic date</a>.', 'sw-wapf' ),
                    'description' => __( 'The minimum selectable date.', 'sw-wapf' ),
                    'modal' => [
                        'id' => 'modal--dynamic-date',
                        'title' => __( 'Dynamic dates', 'sw-wapf' ),
                        'content' => __( "You can use special codes to target a dynamic date in the future or past, relative from today. A few examples: <ul><li><code>0d</code> means today.</li><li><code>7d</code> means 7 days from today.</li><li><code>-10d</code> means 10 days ago.</li></ul> You can use <code>y</code> for years, <code>m</code> for months, and <code>d</code> for days. You can list multiple periods with a space in between. Some more examples: <ul><li><code>-1m -7d</code> means 1 month and 7 days ago.</li><li><code>1y 9m 3d</code> means 1 year, 9 months, and 3 days from today.</li></ul><h4>Reference other date fields</h4>You can also reference othere date fields by using the code <code>[field.{{id}}]</code>. Replace <code> {{id}} </code> with your field’s ID. Here’s an example: <code>[field.5e8c41711d1db]</code> For example, you can have 2 date fields asking for a 'start' date and an 'end' date. The user should not be able to select an end date before the start date. To do this, you can set the minimum date of the 'end' date to <code>[field.{{id}}]+1d</code>", 'sw-wapf' ),
                    ],
                    'tab' => 'selection'
                ];

                $options['date'][] = [
                    'type' => 'text',
                    'id' => 'max_date',
                    'label' => __( 'Max. date', 'sw-wapf' ),
                    'note' => __( 'Use format mm-dd-yyyy or <a href="#" onclick="javascript:event.preventDefault();jQuery(\'.modal--dynamic-date\').show();">use a dynamic date</a>.', 'sw-wapf' ),
                    'description' => __( 'The maximum selectable date.', 'sw-wapf' ),
                    'tab' => 'selection'
                ];

                $options['date'][] = [
                    'type' => 'text',
                    'id' => 'disabled_dates',
                    'label' => __( 'Disabled dates', 'sw-wapf' ),
                    'note' => __( 'Use format mm-dd-yyyy or mm-dd for yearly recurring dates. Separate multiple dates with a comma. Specify date ranges with a space between the start and end date.', 'sw-wapf' ),
                    'description' => __( "Which dates can't be selected?", 'sw-wapf' ),
                    'tab' => 'selection'
                ];

                $options['date'][] = [
                    'type' => 'select',
                    'options' => [
                        '' => __( 'Select a time', 'sw-wapf' ),
                        '00:00' => '12:00AM',
                        '00:30' => '12:30AM',
                        '01:00' => '1:00AM',
                        '01:30' => '1:30AM',
                        '02:00' => '2:00AM',
                        '02:30' => '2:30AM',
                        '03:00' => '3:00AM',
                        '03:30' => '3:30AM',
                        '04:00' => '4:00AM',
                        '04:30' => '4:30AM',
                        '05:00' => '5:00AM',
                        '05:30' => '5:30AM',
                        '06:00' => '6:00AM',
                        '06:30' => '6:30AM',
                        '07:00' => '7:00AM',
                        '07:30' => '7:30AM',
                        '08:00' => '8:00AM',
                        '08:30' => '8:30AM',
                        '09:00' => '9:00AM',
                        '09:30' => '9:30AM',
                        '10:00' => '10:00AM',
                        '10:30' => '10:30AM',
                        '11:00' => '11:00AM',
                        '11:30' => '11:30AM',
                        '12:00' => '12:00PM',
                        '12:30' => '12:30PM',
                        '13:00' => '1:00PM',
                        '13:30' => '1:30PM',
                        '14:00' => '2:00PM',
                        '14:30' => '2:30PM',
                        '15:00' => '3:00PM',
                        '15:30' => '3:30PM',
                        '16:00' => '4:00PM',
                        '16:30' => '4:30PM',
                        '17:00' => '5:00PM',
                        '17:30' => '5:30PM',
                        '18:00' => '6:00PM',
                        '18:30' => '6:30PM',
                        '19:00' => '7:00PM',
                        '19:30' => '7:30PM',
                        '20:00' => '8:00PM',
                        '20:30' => '8:30PM',
                        '21:00' => '9:00PM',
                        '21:30' => '9:30PM',
                        '22:00' => '10:00PM',
                        '22:30' => '10:30PM',
                        '23:00' => '11:00PM',
                        '23:30' => '11:30PM',
                    ],
                    'id' => 'disable_today_after',
                    'label' => __( 'Disable today after a specific time', 'sw-wapf' ),
                    'note' => __( "Can't select today's date if time is equal or after this setting. Leave blank to ignore.", 'sw-wapf' ),
                    'tab' => 'selection'
                ];

                            }

            foreach ( $options as $key => &$option ) {

                if( in_array( $key, $add_weight_options_to ) ) { 

                    $field_defs = Config::get_field_definition_for( $key );
                    $is_qty_swatch = is_array( $field_defs ) && ! empty( $field_defs['qty_selector'] );

                    foreach ( $option as &$o ) {

                        if ( ! empty( $o['id'] ) && $o['id'] === 'options' ) {

                            $input = [
                                'title' => $is_qty_swatch? $weight_title_qty : $weight_title,
                                'type'  => 'text',
                                'key'   => 'weight'
                            ];

                            if( ! empty( $o['inputs'] ) )
                                $o['inputs'][] = $input;
                            else $o['inputs'] = [ $input ];

                            break;
                        }
                    }
                } else { 

                    if( in_array( $key, [ 'p', 'img', 'section', 'sectionend', 'calc' ] ) ) continue;

                    $option[] = [
                        'type'                  => 'text',
                        'id'                    => 'weight',
                        'tab'                   => 'advanced',
                        'label'                 => $extra_weight_title,
                        'description'           => $weight_descr
                    ];

                }
            }

                        return $options;

        }

        public function add_field_types( $fields ) {

            $fields['calc'] = [
                'id'            => 'calc',
                'title'         => __('Calculation','sw-wapf'),
                'description'   => __('Show a calculation result or optionally adjust the product price.', 'sw-wapf'),
                'type'          => 'field',
                'group_label'   => __('Advanced', 'sw-wapf'),
                'icon'          => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" stroke-width="2" stroke="#828282" viewBox="0 0 66 66"><path d="m20.7 23-1.5 1.7.1 1.2h4c-.4 4-1.5 8.1-2.6 14.5a36.6 36.6 0 0 1-2.6 10.8c-.4.8-1 2-1.8 2L13 50.9c-.3-.2-.2-.2-1 0-.8.7-1.5 1.7-1.5 2.6.7 1.2 1.5 2.2 3 2.2C15 55.7 17 55 19 53c2.8-2.7 5-6.4 6.7-14.4C27 34.2 28 30 28.2 26l4.8-1.1 1-2h-5.3c1.4-8.7 2.5-10 3.8-10s2.7.7 3.1 2c.4.5 1 1 1.3.1.7-.4 1.5-1.4 1.6-2.4 0-1-1.2-2.3-3.3-2.3-2 0-5 1.3-7.4 3.8-2.2 2.3-2.6 5.8-4.1 8.9h-3Zm15.4 5.9c1.5-2 2.4-2.7 2.8-2.7 1 0 .9.5 1.7 4l1.4 3.7c-2.7 4.1-4.7 6.8-5.9 6.8-.4 0-.8-.5-1-.8-.3-.3.2-.5-1-.5-1 0-2.2 1.2-2.2 2.7 0 1.5 1 2.6 2.4 2.6 2.4 0 4.5-1.7 8.4-8.6l1.1 3.8c1 3.5 2.2 4.8 4.1 4.8 1.3 0 3.7-1.5 6.3-5.6l-1-1.3c-1.7 1.9-2.7 2.8-3.3 2.8-.7 0-1.3-1-2.1-3.6l-1.7-5.5c1-1.5 2-2.7 2.8-3.7 1-1.1 1.9-1.6 2.4-1.6.4 0 .8 1 1.2.4.2.4.4.6.8.6.8 0 2.2-1.1 2.2-2.6 0-1.3-.8-2.5-2.2-2.5-2.2 0-4.2 2-7.2 7.4l-1.4-2.3c-1-3.4-1.8-5-3-5-2 0-4.4 2-6.8 5.5l1.2 1.2Z"/></svg>'
            ];

                        return $fields;

        }

                #region Calculator

        public function calculator_cart_item_field( $cart_item_field, $field, $clone_idx ): array {

                        if( $field->type === 'calc' && isset( $field->options['calc_type'] ) && $field->options['calc_type'] === 'cost' ) {
                $cart_item_field['hide_price_hint']         = true;
                $cart_item_field['calc_type']               = 'cost';
                $cart_item_field['calc_text']               =  empty( $field->options['result_text'] ) ? '{result}' : $field->options['result_text'];
                $cart_item_field['values'][0]['price_type'] = 'fx';
                $cart_item_field['values'][0]['price']      = empty( $field->options['formula'] ) ? '0' : $field->options['formula'];
            }

            return $cart_item_field;
        }

                public function calculator_cart_item_value_label( $str, $cartitem_field, $cart_item, $simple_mode ): string {

            if( $cartitem_field['type'] === 'calc' && isset( $cartitem_field['calc_type'] ) && $cartitem_field['calc_type'] === 'cost' ) {

                if( ! empty( $cartitem_field['values'] ) ) {
                    $amount = apply_filters('wapf/html/pricing_hint/amount', $cartitem_field['values'][0]['calc_price'], $cart_item['data'], 'fx', 'cart' );
                    $pricing_hint = Helper::format_price( \SW_WAPF_PRO\Includes\Classes\Helper::adjust_addon_price($cart_item['data'],empty( $amount ) ? 0 : $amount, 'fx', 'cart' ) );
                    return str_replace( '{result}', $pricing_hint, $cartitem_field['calc_text'] );
                }

            }

            return $str;
        }

                #endregion

                #region Weight
        public function weight_cart_item_field( $cart_item_field, Field $field, $clone_idx ) {

            $cart_item_field['calc_weight'] =
                isset( $field->options['weight'] ) ||
                ( ! empty( $field->options['choices'] ) && Enumerable::from( $field->options['choices'] )->any( function($x){ return ! empty( $x['options']['weight'] ); }) );

                        return $cart_item_field;

                    }

                public function maybe_calculate_weight( $cart_obj ) {

                        if( $this->weight_was_calculated ) {
                return;
            }

            foreach( $cart_obj->get_cart() as $cart_item ) {

                if( empty( $cart_item['wapf'] ) ) continue;

                $should_calculate = Enumerable::from( $cart_item['wapf'] )->any( function( $x ){ return isset( $x['calc_weight'] ) && $x['calc_weight']; } );

                if( $should_calculate ) {

                    $additional_weight  = 0;
                    $field_groups       = Field_Groups::get_by_ids( $cart_item['wapf_field_groups'] );
                    $fields             = Enumerable::from( $field_groups )->merge( function( $x ){ return $x->fields; } )->toArray();
                    $quantity           = $cart_item[ 'quantity' ] ?? 1;

                    foreach ( $cart_item['wapf'] as $cart_field ) {

                        if( empty( $cart_field['values'] ) || empty( $cart_field['calc_weight'] ) ) continue;

                        $field = Enumerable::from( $fields )->firstOrDefault( function( $x ) use( $cart_field ) { return $x->id === $cart_field['id']; } );
                        if( ! $field ) continue;

                        foreach ( $cart_field['values'] as $value ) {

                            if( ! isset( $cart_field['raw'] ) || $cart_field['raw'] === '' ) continue;

                            $v = isset( $value['slug'] ) ? $value['label'] : $cart_field['raw'];

                            $weight_formula = 0;

                            if( isset( $value['slug'] ) ) {
                                $choice = Enumerable::from( $field->options['choices'] )->firstOrDefault( function($x) use( $value ) { return $x['slug'] === $value['slug']; } );
                                if( $choice && isset( $choice['options']['weight'] ) ) {
                                    $weight_formula = $choice[ 'options' ][ 'weight' ];
                                }
                            } else { 
                                if( isset( $field->options['weight'] ) )
                                    $weight_formula = $field->options['weight'];
                            }

                            $weight_formula = floatval( apply_filters(
                                'wapf/field_weight',
                                str_replace( ['[qty]','[x]'], [ $quantity, $v ], $weight_formula ),
                                [
                                    'field'     => $field,
                                    'value'     => $v,
                                    'cart_item' => $cart_item
                                ]
                            ) );

                            if( $field->is( 'qty_selector' ) ) {
                                $weight_formula = $weight_formula * intval( $v );
                            }

                            $additional_weight += $weight_formula;

                        }

                    }

                    $product = $cart_item['data'];

                                        if( ! $product->get_virtual() ) {
                        $new_weight = $additional_weight + floatval( $product->get_weight() );
                        $product->set_weight( max( $new_weight, 0 ) );
                    }

                }

            }

                        $this->weight_was_calculated = true;

                    }
        #endregion

                #region Basics
		public function register_assets() {

			$url = wapf_get_setting('url') . 'assets/';
			$version = wapf_get_setting( 'version' );

			wp_enqueue_script( 'wapf-extended', $url . 'js/extended.min.js', [ 'jquery', 'wapf-frontend' ], $version, true );

		}

		public function deactivate_other_versions() {
			if( current_user_can( 'activate_plugins' ) ) {
				deactivate_plugins( 'advanced-product-fields-for-woocommerce-pro/advanced-product-fields-for-woocommerce-pro.php' );
			}
		}
        #endregion

        	}
}