<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'moyasar'),
        'type' => 'checkbox',
        'label' => __('Enable Moyasar Payment Gateway', 'moyasar'),
        'default' => 'no'
    ),
    'api_pb' => array(
        'title' => __('Publish Key', 'moyasar'),
        'type' => 'text',
        'description' => __('This key is used by the client to initiate payment.', 'moyasar')
    ),

    'api_sk' => array(
        'title' => __('Secret Key', 'moyasar'),
        'type' => 'text',
        'description' => __('This key is used by the server to verify payments upon clients return.', 'moyasar')
    ),

    'new_order_status' => array(
        'title' => __('New Order Status', 'moyasar'),
        'type' => 'select',
        'default' => 'processing',
        'options' => array(
            'processing' => __('Processing', 'moyasar'),
            'on-hold' => __('On Hold', 'moyasar'),
            'completed' => __('Completed', 'moyasar'),
        )
    ),

    'webhook_url' => array(
        'title' => __('Webhook URL', 'moyasar'),
        'type' => 'text',
        'default' => moyasar_remove_url_fragment(get_site_url(null, '/?rest_route=/moyasar/v2/webhook')),
        'custom_attributes' => ['readonly' => true],
        'description' => __('Copy URL and pasted into a webhook section in the Moyasar dashboard.', 'moyasar')
    ),
    'webhook_secret' => array(
        'title' => __('Webhook Secret', 'moyasar'),
        'type' => 'text',
        'custom_attributes' => ['readonly' => true],
        'description' => __('Make sure use the same secret token in the Moyasar dashboard.', 'moyasar')
    ),

    'title' => array(
        'title' => __('Title', 'moyasar'),
        'type' => 'text',
    ),
    'description' => array(
        'title' => __('Description', 'moyasar'),
        'type' => 'textarea',
    ),
);
