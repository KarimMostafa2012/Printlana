<?php

namespace SW_WAPF_PRO\Includes\Models {

        class ConditionRuleGroup
    {
        public $rules = [];

        public function get_variation_rules(): array {

                    	if( empty( $this->rules ) ) {
                return [];
            }

            return array_filter( $this->rules, function( $rule ) {
                return ! empty( $rule->subject ) && ( $rule->subject === 'product_variation' || $rule->subject === 'var_att' );
            });

                    }
    }
}