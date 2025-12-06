<?php
if ( ! defined( 'ABSPATH' ) ) exit;
return array(
    'schemas' => array(
      'title' => __('Schemas', 'moyasar'),
        'description' => __('Select the card types you want to enable Apple Pay for.', 'moyasar'),
        'type' => 'multiselect',
        'options' => array(
            'mada' => __('Mada', 'moyasar'),
            'visa' => __('Visa', 'moyasar'),
            'mastercard' => __('Mastercard', 'moyasar'),
            'amex' => __('Amex', 'moyasar'),
        ),
        'default' => array('mada', 'visa', 'mastercard', 'amex'),
    ),
    'service_id' => array(
        'title' => __('Service ID', 'moyasar'),
        'type' => 'text',
        'required' => true,
        'description' => __('Enter your Moyasar service ID.', 'moyasar'),
        'default' => '',
    ),
);
