<?php

namespace SW_WAPF_PRO\Includes\Classes {


    use SW_WAPF_PRO\Includes\Models\ConditionRuleGroup;
    use SW_WAPF_PRO\Includes\Models\FieldGroup;

    class Conditions
    {

        public static function is_field_group_valid( FieldGroup $field_group ): bool
        {

            if( empty( $field_group->rules_groups ) ) {
                return true;
            }

            foreach( $field_group->rules_groups as $rule_group ) {
                if( self::is_rule_group_valid( $rule_group ) ) {
                    return true;
                }
            }

            return false;

        }

        public static function is_field_group_valid_for_product( FieldGroup $field_group, $product ): bool
        {

            if( empty( $field_group->rules_groups ) ) {
                return true;
            }

            foreach ( $field_group->rules_groups as $rule_group ) {
                if( self::is_rule_group_valid( $rule_group, $product ) ) {
                    return true;
                }
            }

                        return false;

                    }

        public static function is_rule_group_valid( ConditionRuleGroup $group, $product = null ): bool {

            if( empty( $group->rules ) ) {
                return true;
            }

            foreach ( $group->rules as $rule ) {

                $value = $rule->value;

                if( is_array( $value ) && count( $value ) > 0 && isset( $value[0]['text'] ) ) {
                    $value = Enumerable::from($value)->select(function($x) {
                        return $x['id'];
                    })->toArray();
                }

                                if( ! Conditions::check( $rule->condition, $value, $product ) ) {
                    return false;
                }

            }

            return true;

        }

        private static function check($condition, $value, $product = null): bool
        {

            switch ($condition) {
                case 'auth':
                    return is_user_logged_in() === true;
                case '!auth':
                    return is_user_logged_in() === false;
                case 'role':
                    return self::user_has_role($value) === true;
                case '!role':
                    return self::user_has_role($value) === false;
            }

            $product = empty($product) ? $GLOBALS['product'] : $product;

            switch ($condition) {
                case 'product':
                case 'products':
                    return self::is_current_product($product, (array)$value) === true;
                case '!product':
                case '!products':
                    return self::is_current_product($product,(array)$value) === false;
                case 'product_var':
                case '!product_var': 
                    return self::is_product_variation($product, $value) === true; 
                case 'product_cat':
                case 'product_cats':
                    return self::is_current_product_category($product,(array)$value) === true;
                case '!product_cat':
                case '!product_cats':
                    return self::is_current_product_category($product,(array)$value) === false;
                case 'product_type':
                    return self::product_is_type($product, $value) === true;
                case '!product_type':
                    return self::product_is_type($product, $value) === false;
	            case 'p_tags':
	            	return self::product_has_tags($product,$value);
	            case '!p_tags':
	            	return self::product_has_tags($product,$value) === false;
	            case 'patts':
					return self::product_has_attribute_values($product,(array)$value);
	            case '!patts':
		            return self::product_has_attribute_values($product,(array)$value) === false;
            }

            switch($condition) {
	            case 'lang': return self::current_language_is($value);
	            case '!lang': return self::current_language_is($value) === false;
            }

            return apply_filters( 'wapf/field_group/is_condition_valid', false, $condition, $value, [ 'product' => $product ] );

        }

        public static function product_has_attribute_values($product,$attribute_values, $strict = false): bool {

	        $product_attributes = Woocommerce_Service::get_product_attributes($product,$strict);
	        if(empty($product_attributes)) return false;

	        foreach($attribute_values as $v) {
	        	$split = explode('|',$v);
	        	$attr_name = 'pa_' . $split[0];
	        	$value = $split[1];

	        	if(isset($product_attributes[$attr_name]) && ($value === '*' || in_array($value, $product_attributes[$attr_name])))
	        		return true;
	        }

	        return false;

        }

        private static function current_language_is($lang): bool {
        	return Helper::get_current_language() === $lang;
        }

        private static function user_has_role($role): bool {

            if( ! is_user_logged_in() ) {
                return false;
            }

            $user = wp_get_current_user();

            if( $user->ID == 0 ) {
                return false;
            }

            return in_array( $role, $user->roles );

        }

        private static function product_has_tags( $product, $value = [] ): bool {

	        $product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();
			$tags = get_the_terms( $product_id, 'product_tag' );

			if( $tags === false || empty( $value ) ) {
                return false;
            }

			foreach ( $tags as $tag ) {
				if( in_array( $tag->term_id, $value ) ) {
                    return true;
                }
			}

			return false;

        }

        private static function product_is_type( $product, $types = [] ): bool {

            if( empty( $types ) ) {
                return false;
            }

            return in_array( $product->get_type(), $types );

        }

        private static function is_product_variation( $product, $variations = [] ): bool {

            if( empty( $variations ) ) {
                return false;
            }

	        if( $product->is_type( 'variation' ) ) {
        		return in_array( $product->get_id(), $variations ); 
	        }

	        if( $product->is_type( 'variable' ) ) {
		        $children = $product->get_children();
		        foreach ( $children as $child ) {

			        if ( in_array( $child, $variations ) ) { 
				        return true;
			        }
		        }
	        }

            return false;

        }

        private static function is_current_product( $product, $product_ids = [] ): bool {

            if( empty( $product_ids ) ) {
                return false;
            }

            $product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();

			return in_array( $product_id, $product_ids ); 

        }

        private static function is_current_product_category($product, $term_ids = [] ): bool {

            if( empty( $term_ids ) ) {
                return false;
            }

        	$product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();
            $terms = get_the_terms( $product_id, 'product_cat' );

            if( empty( $terms ) && !is_array( $terms) ) {
                return false;
            }

                        foreach ( $terms as $term ) {
                if( in_array( $term->term_id, $term_ids ) ) {
                    return true;
                }
            }

                        return false;

        }

    }
}