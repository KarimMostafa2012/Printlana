<?php

use SW_WAPF_PRO\Includes\Classes\Helper;

if(!function_exists('wapfe_validate_expression')) {
	function wapfe_validate_expression($left,$right,$operand, $cart_fields, $extra) {
		$left = wapfe_prepare_expression_part($left,$cart_fields, $extra);
		$right = wapfe_prepare_expression_part($right,$cart_fields, $extra);

		switch ($operand) {
			case '<': return $left < $right;
			case '>': return $left > $right;
			case '>=': return $left >= $right;
			case '<=': return $left <= $right;
			case '!=': return $left !== $right;
			default: return $left === $right;
		}
	}
}

if(!function_exists('wapfe_prepare_expression_part')) {
	function wapfe_prepare_expression_part($str, $cart_fields, $extra) {
		$parsed = Helper::parse_math_string($str,$cart_fields,false, $extra);
		if($parsed === 'true') return true;
		if($parsed === 'false') return false;
		if($parsed === '') return ''; // field values could be empty so return it here because evalFx() will throw errors anyway
		$evaluated = Helper::evaluate_math_string(''.$parsed,false, true);
		return $evaluated === false ? $parsed : $evaluated;
	}
}

if(!function_exists('wapfe_get_expression_parts')) {
	function wapfe_get_expression_parts($str) {
		$open = 0;
		$checks = ['=','!=','>','<','<=','>='];

		for($i=0; $i < strlen($str); $i++) {
			if(in_array($str[$i],$checks) && $open === 0) {
				return [
					'operand'   => $str[$i],
					'left'      => trim(substr($str,0, $i)),
					'right'     => trim(substr($str,$i < strlen($str)-1 ? $i+1 : $i))
				];
			}
			if ($str[$i] === '(')
				$open++;
			if ($str[$i] === ')')
				$open--;
		}
		return [
			'operand'   => '=',
            'left'      => $str,
            'right'     => 'true'
        ];
	};

}

wapf_add_formula_function('round',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
    $num = Helper::parse_math_string($args[0],$data['fields'], true, $extra);
    $precision = count( $args ) === 2 && ! empty( $args[1] ) ? Helper::parse_math_string($args[1], $data['fields'], true, $extra) : 0;
	return round($num, $precision);
});

wapf_add_formula_function('abs',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return abs(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('floor',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return floor(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('ceil',function($args, $data){
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return ceil(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('sqrt',function($args, $data){
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return sqrt(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('cos',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return cos(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('sin',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return sin(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('tan',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return tan(Helper::parse_math_string($args[0],$data['fields'], true, $extra));
});

wapf_add_formula_function('pow',function($args, $data) {
	$extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return pow(Helper::parse_math_string($args[0],$data['fields'], true, $extra), Helper::parse_math_string($args[1],$data['fields'], true, $extra));
});

wapf_add_formula_function('sumQty', function($args, $data) {

    $extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
    $field_id = Helper::parse_math_string($args[0], $data['fields'], false, $extra);

    foreach ($data['fields'] as $cf) {

        if($cf['id'] !== $field_id)
            continue;

        if( ! empty( $cf['values'] ) ) {
            return array_sum( array_map( 'intval', array_column( (array) $cf['values'], 'label') ) );
        }

        return 0;
    }

    return 0;

});

wapf_add_formula_function('checked', function($args, $data) {
	foreach ($data['fields'] as $cf) {
		if($cf['id'] !== $args[0])
			continue;
		return is_array($cf['values']) ? count($cf['values']) : 0;
	}
	return 0;
});

wapf_add_formula_function('files', function($args, $data) {
	foreach ($data['fields'] as $cf) {
		if($cf['id'] !== $args[0])
			continue;

		if( empty( $cf['values'] ) || ! is_array( $cf['values'] ) || empty( $cf['values'][0]['label'] ) )
			return 0;
		return count( explode(',', $cf['values'][0]['label']) );
	}
	return 0;
});

wapf_add_formula_function('if', function($args, $data) {
	$parts = wapfe_get_expression_parts($args[0]);
    $extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
	return wapfe_validate_expression($parts['left'],$parts['right'],$parts['operand'],$data['fields'], $extra ) ? $args[1] : $args[2];
});

wapf_add_formula_function('or', function($args, $data) {
	for($i = 0; $i < count($args); $i++) {
		$parts = wapfe_get_expression_parts($args[$i]);
        $extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
		if( wapfe_validate_expression($parts['left'],$parts['right'],$parts['operand'], $data['fields'], $extra) )
			return 'true';
	}
	return 'false';
});

wapf_add_formula_function('and', function($args, $data) {
	for($i = 0; $i < count($args); $i++) {
		$parts = wapfe_get_expression_parts($args[$i]);
        $extra = ['product_id' => isset($data['product_id']) ? $data['product_id'] : null];
		if(!wapfe_validate_expression($parts['left'],$parts['right'],$parts['operand'], $data['fields'], $extra ) )
			return 'false';
	}
	return 'true';
});

add_filter( 'wapf/function_definitions' , function($defs) {

    $funcs = [
        [
            'name'                  => 'if',
            'category'              => __('Logical', 'sw-wapf'),
            'description'           => __( 'This function runs a logical test and returns one value for a TRUE result, or another for a FALSE result.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'if(10 < 5; 1; 2)',
                    'solution'          => '2',
                    'description'       => __('10 < 5 is false because 10 is not smaller than 5')
                ],
                [
                    'example'           => 'if(2 < 5; 1; 2)',
                    'solution'          => '1',
                    'description'       => __('because 2 is smaller than 5')
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'test',
                    'description'   => __( 'An expression you want to test.', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'if_true',
                    'description'   => __( 'The expression to run if <i>test</i> is true.', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'if_false',
                    'description'   => __( 'The expression to run if <i>test</i> is false.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'or',
            'category'              => __('Logical', 'sw-wapf'),
            'description'           => __( 'Returns true if at least 1 argument evaluates to true, false otherwise. Used within the IF-function', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'or(1 < 2; 5 < 1)',
                    'solution'          => 'true',
                    'description'       => __('the first argument is true', 'sw-wapf')
                ],
            ],
            'parameters'            => [
                [
                    'name'          => 'logical1',
                    'description'   => __( 'An expression you want to test.', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'logical2',
                    'description'   => __( 'An expression you want to test.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'and',
            'category'              => __('Logical', 'sw-wapf'),
            'description'           => __( 'Returns true if all argument evaluate to true, false otherwise. Used within the IF-function', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'and(1 < 2; 5 < 1)',
                    'solution'          => 'false',
                    'description'       => __('the second argument is false')
                ],
            ],
            'parameters'            => [
                [
                    'name'          => 'logical1',
                    'description'   => __( 'An expression you want to test.', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'logical2',
                    'description'   => __( 'An expression you want to test.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'abs',
            'category'              => __('Math', 'sw-wapf'),
            'description'           => __( 'Returns the absolute (positive) value of a number.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'abs(-5)',
                    'solution'          => '5',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number_1',
                    'description'   => __( 'The number to return the positive value for.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'ceil',
            'category'              => __('Number operations', 'sw-wapf'),
            'description'           => __( 'Round up to the nearest number.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'ceil(4.8)',
                    'solution'          => '5',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number to round up.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'checked',
            'category'              => __('Other', 'sw-wapf'),
            'description'           => __( 'Returns how many items are selected in a multi-select field (such as checkboxes, image swatches, …).', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'checked(600d34143bd65)',
                    'solution'          => '5',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'field_id',
                    'description'   => __( 'The ID of the field you want to count the selected items of.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'sumQty',
            'category'              => __('Other', 'sw-wapf'),
            'description'           => __( 'Sums all given quantities of a field with multiple quantity number boxes.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'sumQty(600d34143bd65)',
                    'solution'          => '6',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'field_id',
                    'description'   => __( 'The ID of the field you want to count the selected items of.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'cos',
            'category'              => __('Math', 'sw-wapf'),
            'description'           => __( 'Returns the cosine of the specified angle.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'cos(5)',
                    'solution'          => '0.283',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number (in radians) to find the cosine for.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'datediff',
            'category'              => __('Dates', 'sw-wapf'),
            'description'           => __( 'Returns the difference (in days) between two dates.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => "datediff('01-10-2023'; '01-12-2023')",
                    'solution'          => '2',
                    'description'       => __('there are 2 days between January 10 and January 12.','sw-wapf'),
                ],
                [
                    'example'           => 'datediff([field.600d34143bd65]; [field.799e14133be99])',
                    'solution'          => '5',
                    'description'       => __('the system counts the days between the 2 dates the customer enterered.', 'sw-wapf'),
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'date_1',
                    'description'   => __( 'A date string or pointer to a date field.', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'date_2',
                    'description'   => __( 'A date string or pointer to a date field.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        /*[
            'name'                  => 'month',
            'category'              => __('Dates', 'sw-wapf'),
            'description'           => __( 'Returns the month as a number. 1 for January, 2 for February,...', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => "month('01-03-2023')",
                    'solution'          => '3',
                    'description'       => __('3 for March.','sw-wapf'),
                ],
            ],
            'parameters'            => [
                [
                    'name'          => 'date_1',
                    'description'   => __( 'A date string, pointer to a date field, or today().', 'sw-wapf' ),
                    'required'      => true,
                ]
            ]
        ],
        [
            'name'                  => 'dow',
            'category'              => __('Dates', 'sw-wapf'),
            'description'           => __( 'Returns the day of the week as a number. 0 for Sunday, 1 for Monday,..., 6 for Saturday.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => "dow('01-10-2023')",
                    'solution'          => '2',
                    'description'       => __('2 for Tuesday.','sw-wapf'),
                ],
            ],
            'parameters'            => [
                [
                    'name'          => 'date_1',
                    'description'   => __( 'A date string, pointer to a date field, or today().', 'sw-wapf' ),
                    'required'      => true,
                ]
            ]
        ],*/
        [
            'name'                  => 'files',
            'category'              => __('Files', 'sw-wapf'),
            'description'           => __( 'Returns the count of uploaded files for a File Upload field.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'files(600d34143bd65)',
                    'solution'          => '3',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'field_id',
                    'description'   => __( 'The ID of the field you want to count the uploaded files of.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'floor',
            'category'              => __('Number operations', 'sw-wapf'),
            'description'           => __( 'Round down to the nearest number.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'floor(4.8)',
                    'solution'          => '4',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number to round down.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'pow',
            'category'              => __('Math', 'sw-wapf'),
            'description'           => __( 'To do exponentiation.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'pow(4; 2)',
                    'solution'          => '16',
                    'description'       => '4² = 16',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'base',
                    'description'   => __( 'The base number to do the exponentiation for. ', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'exponent',
                    'description'   => __( 'The exponent.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'round',
            'category'              => __('Number operations', 'sw-wapf'),
            'description'           => __( 'Round up or down to the neirest number. You can optionally specify decimals.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'round(4.8)',
                    'solution'          => '5',
                ],
                [
                    'example'           => 'round(4.2)',
                    'solution'          => '4',
                ],
                [
                    'example'           => 'round(0.33337;4)',
                    'solution'          => '0.3334',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number to round up or down.', 'sw-wapf' ),
                    'required'      => true,
                ],
                [
                    'name'          => 'decimals',
                    'description'   => __( 'The optional number of decimal digits to round to. If ommitted, it rounds to a whole number (no decimals).', 'sw-wapf' ),
                    'required'      => false,
                ],
            ]
        ],
        [
            'name'                  => 'sin',
            'category'              => __('Math', 'sw-wapf'),
            'description'           => __( 'Returns the sine of a number.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'sin(5)',
                    'solution'          => '-0.958',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number to find the sine for.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'sqrt',
            'category'              => __('Math', 'sw-wapf'),
            'description'           => __( 'Returns the square root of a number.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'sqrt(144)',
                    'solution'          => '12',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number to find the square root for.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'tan',
            'category'              => __('Math', 'sw-wapf'),
            'description'           => __( 'Returns the tangent of a number.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'tan(10)',
                    'solution'          => '0.648',
                ]
            ],
            'parameters'            => [
                [
                    'name'          => 'number',
                    'description'   => __( 'The number to find the tangent for.', 'sw-wapf' ),
                    'required'      => true,
                ],
            ]
        ],
        [
            'name'                  => 'today',
            'category'              => __('Dates', 'sw-wapf'),
            'description'           => __( 'Returns today’s date. Can be used in other date function for further calculation.', 'sw-wapf' ),
            'examples'              => [
                [
                    'example'           => 'today()',
                    'solution'          => __( "today's date", 'sw-wapf' ),
                ]
            ]
        ],
    ];

    return array_merge($defs, $funcs);

});
