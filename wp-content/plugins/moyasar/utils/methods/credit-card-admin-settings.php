<?php
if ( ! defined( 'ABSPATH' ) ) exit;
return array(
    'schemas' => array(
      'title' => __('Schemas', 'moyasar'),
        'type' => 'multiselect',
        'options' => array(
            'mada' => __('Mada', 'moyasar'),
            'visa' => __('Visa', 'moyasar'),
            'mastercard' => __('Mastercard', 'moyasar'),
            'amex' => __('Amex', 'moyasar'),
        ),
        'default' => array('mada', 'visa', 'mastercard', 'amex'),
    ),
);
