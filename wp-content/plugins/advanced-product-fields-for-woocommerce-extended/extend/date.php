<?php

use \SW_WAPF_PRO\Includes\Classes\Helper;
use \SW_WAPF_PRO\Includes\Classes\Fields;
use \SW_WAPF_PRO\Includes\Classes\Enumerable;

function wapfe_set_default_date( $str, $options ) {

    $date = wapfe_period_to_date( $str, new DateTime('now', wp_timezone() ) );
    $i = 0;

    do {

        $has_error = false;

        $date->modify('+' . $i . ' day');

        if( ! empty( $options['disabled_days'] ) ) {
            $days = array_map( 'trim', explode( ',','' . rtrim( $options['disabled_days'], ',' ) ) );
            if( array_search( $date->format('w'), $days, true ) !== false ) {
                $has_error = true;
            }
        }

        if( ! empty($options['disabled_dates'] ) ) {
            $dates = array_map(function($x){return Helper::string_to_date(trim($x));}, explode(',', rtrim( $options['disabled_dates'], ',' ) ) );
            foreach($dates as $d) {
                if($d == $date) {
                    $has_error = true;
                    break;
                }
            }
        }

        $i++;

    } while( $has_error && $i < 50 ); // failsafe at 50 iterations to prevent possible endless loop.

    return $date;

}

function wapfe_period_to_date($str, DateTime $date) {

    if( ! is_string( $str ) ) return $date;
    $str = trim( $str );
    if( empty( $str ) ) return $date;

	$years = 0;
	$months = 0;
	$days = 0;
	$splitted = explode(' ',$str);

	if ( count( $splitted ) > 0 ) {
		for ( $i = 0; $i < count( $splitted ); $i ++ ) {
			$x = strtolower( $splitted[ $i ] );

			if ( strpos( $x, 'y' ) !== false ) {
				$years += intval( str_replace( 'y', '', $x ) );
			}
			if ( strpos( $x, 'm' ) !== false ) {
				$months += intval( str_replace( 'm', '', $x ) );
			}
			if ( strpos( $x, 'd' ) !== false ) {
				$days += intval( str_replace( 'd', '', $x ) );
			}
		}
	}

    $dInterval = new DateInterval('P'.absint($days).'D');
	$mInterval = new DateInterval('P'.absint($months).'M');
	$yInterval = new DateInterval('P'.absint($years).'Y');

	if($days < 0) $dInterval->invert = 1;
	if($months < 0) $mInterval->invert = 1;
	if($years < 0) $yInterval->invert = 1;

    $date->add($dInterval)->add($mInterval)->add($yInterval);
	$date->setTime(0,0,0);

	return $date;
}

add_filter('wapf/html/field_attributes','wapfe_add_date_field_attributes',10,4);

function wapfe_add_date_field_attributes($attrs, \SW_WAPF_PRO\Includes\Models\Field $field, $product, $field_group_id) {
	if($field->type === 'date') {
		if(isset($field->options['disabled_days'])) {
			$attrs['data-disabled-days'] = $field->options['disabled_days'];
		}
		if(isset($field->options['disabled_dates'])) {
			$attrs['data-disabled-dates'] = $field->options['disabled_dates'];
		}

		if(isset($field->options['min_date'])) {
			$attrs['data-min'] = $field->options['min_date'];
		}
		if(isset($field->options['max_date'])) {
			$attrs['data-max'] = $field->options['max_date'];
		}
		if( ! empty( $field->options['disable_today_after'] ) ) {
			$attrs['data-disable-after'] = $field->options['disable_today_after'];
		}
	}
	return $attrs;
}

function wapfe_get_minmax_day( $type, $field, $is_order_again, $product_id, $clone_idx ) {

    if( empty( $field->options[ $type . '_date'] ) ) {
        return null;
    }

    $day = null;

    if(preg_match('/[0-9]{2}-[0-9]{2}([0-9]{4})?/', $field->options[ $type . '_date']) === 1) {
        $day = Helper::string_to_date($field->options[ $type . '_date']);
    } else if( strpos( $field->options[ $type . '_date'], '[field.') === 0 ) {
        preg_match('/\[field.(.+?)\]/', $field->options[ $type . '_date'], $matches);

        if( is_array( $matches) && count( $matches ) === 2 ) {

            $target_value = null;

            if( $is_order_again ) {
                if( isset( $cart_item_data['wapf'] ) ) {
                    $target_field = Enumerable::from($cart_item_data['wapf'])->firstOrDefault( function($x) use($field) { return $x['id'] === $field->id; });
                    if( $target_field ) $target_value = $target_field['raw'];
                }
            } else {
                $field_groups = wapf_get_field_groups_of_product( $product_id );
                $fields = Enumerable::from($field_groups)->merge( function($x){return $x->fields; })->toArray();
                $target_field = Enumerable::from($fields)->firstOrDefault( function( $x ) use ( $matches ) { return $x->id === $matches[1];} );
                if( $target_field ) $target_value = Fields::get_raw_field_value_from_request( $target_field, $clone_idx, true );
            }

            if( $target_value ) {
                $date_format = Helper::date_format_to_php_format( get_option('wapf_date_format','mm-dd-yyyy') );
                $day = wapfe_period_to_date( preg_replace('/\[(.+)\]/', '', $field->options[ $type . '_date' ] ), Helper::string_to_date( $target_value, $date_format ) );
            }

        }
    }
    else {
        $day = wapfe_period_to_date($field->options[ $type . '_date'], new DateTime('now', wp_timezone() ) );
    }

    return $day;

}

add_filter('wapf/validate','wapfe_validate_cart_data', 10, 8);

// Is this also fired with "oreder again"? I should check.
function wapfe_validate_cart_data($error, $value, $field, $product_id, $clone_index, $qty, $is_order_again, $cart_item_data) {

	if($field->type === 'date') {

		$date_format = get_option('wapf_date_format','mm-dd-yyyy');
		$date = DateTime::createFromFormat(Helper::date_format_to_php_format($date_format),$value, wp_timezone() );
		$date->setTime(0,0,0);

		if( ! empty( $field->options['disabled_dates'] ) ) {


            $all_dates = array_map('trim', explode( ',', rtrim( $field->options['disabled_dates'], ',' ) ) );

            foreach( $all_dates as $the_date ) {

                $range = array_map(function($x){ return Helper::string_to_date(trim($x)); }, explode(' ', $the_date));

                if( count( $range ) === 2 ) {
                    if( $range[0] <= $date && $date <= $range[1] ) {
                        return ['error' => true, 'message' => sprintf(__( 'The field "%s" contains a disallowed date.', 'sw-wapf' ), $field->get_label()) ];
                    }
                } else {
                    if( $range[0] == $date ) {
                        return ['error' => true, 'message' => sprintf(__( 'The field "%s" contains a disallowed date.', 'sw-wapf' ), $field->get_label()) ];
                    }
                }

            }

		}

		if( isset( $field->options['disabled_days'] ) && strlen( $field->options['disabled_days'] ) > 0 ) {
			$days = array_map( 'trim', explode(',',''.$field->options['disabled_days'] ) );
			if( array_search( $date->format('w'), $days, true ) !== false)
				return ['error' => true, 'message' => sprintf(__( 'The field "%s" has an invalid date.', 'sw-wapf' ), $field->get_label()) ];
		}

        $day = wapfe_get_minmax_day( 'min', $field, $is_order_again, $product_id, $clone_index);
        if( $day && $date < $day )
            return ['error' => true, 'message' => sprintf(__( 'The field "%s" is older than the minimum allowed date.', 'sw-wapf' ), $field->get_label()) ];

        $day = wapfe_get_minmax_day( 'max', $field, $is_order_again, $product_id, $clone_index);
        if( $day && $date > $day )
            return ['error' => true, 'message' => sprintf(__( 'The field "%s" is later than the maximum allowed date.', 'sw-wapf' ), $field->get_label()) ];

        if( ! empty( $field->options['disable_today_after'] ) ) {

            $now = new DateTime('now', wp_timezone() );

            if( $date->format('Y-m-d') === $now->format('Y-m-d') ) {

                $givenTime = DateTime::createFromFormat('H:i', $field->options['disable_today_after'], wp_timezone() );
                $currentTime = new DateTime();

                if( $currentTime > $givenTime ) {
                    $time = $givenTime->format( get_option('time_format') );
                    return ['error' => true, 'message' => sprintf( __( 'Today\'s date can\'t be selected in the field "%s" because it is after %s.', 'sw-wapf' ), $field->get_label(), $time ) ];
                }

            }

        }

	}

	return $error;

}

// Add date related formulas
wapf_add_formula_function('today', function() {
	$date = new DateTime('now', wp_timezone() );
	$date_format = get_option('wapf_date_format','mm-dd-yyyy');
	return $date->format(Helper::date_format_to_php_format($date_format));
});

wapf_add_formula_function('datediff',function($args, $data) {
	$date_format = get_option('wapf_date_format','mm-dd-yyyy');
	if( count($args) < 2 || empty( $args[0] ) || empty( $args[1] ) )
		return 0;

	$date = DateTime::createFromFormat(Helper::date_format_to_php_format($date_format),$args[0], wp_timezone() );
	$date->setTime(0,0,0);
	$date2 = DateTime::createFromFormat(Helper::date_format_to_php_format($date_format),$args[1], wp_timezone() );
	$date2->setTime(0,0,0);
	$diff = $date->diff($date2);

	return $diff->days;
});

wapf_add_formula_function('dow', function($args, $data) {

    $date_format = get_option('wapf_date_format','mm-dd-yyyy');
    if( empty($args) || empty( $args[0] ) )
        return 0;

    $date = DateTime::createFromFormat(Helper::date_format_to_php_format($date_format),$args[0], wp_timezone() );

    return intval( $date->format('w') );

} );

wapf_add_formula_function('month', function($args, $data) {

    $date_format = get_option('wapf_date_format','mm-dd-yyyy');
    if( empty($args) || empty( $args[0] ) )
        return 0;

    $date = DateTime::createFromFormat(Helper::date_format_to_php_format($date_format),$args[0], wp_timezone() );

    return intval( $date->format('n') );

} );
