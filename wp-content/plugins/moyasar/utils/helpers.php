<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function moyasar_page_url($page, $order_id)
{
    $nonce = wp_create_nonce('moyasar-form');
    return moyasar_remove_url_fragment(get_site_url(null, "/?moyasar_page=$page&moyasar-nonce-field=$nonce&order-pay=$order_id"));
}


function moyasar_url($page, $query, $order_id)
{
    return add_query_arg($query, moyasar_page_url($page, $order_id));
}

function moyasar_remove_url_fragment($url)
{
    // Remove any URL fragments
    return preg_replace('/#[^&]+/', '', urldecode($url));
}


/**
 * @description Get Gateway Icon
 * @param $name
 * @return string
 */
function moyasar_get_icon($name){
    return MOYASAR_PAYMENT_URL . '/assets/general/images/' . $name . '.png';
}

/**
 * @description Get Store Name In English
 * @return string
 */
function moyasar_get_store_name(){
    $store_name = get_bloginfo('name') ?? 'Store';

    if (!preg_match('/\A[\x00-\x7F]+\z/', $store_name)) {
        $store_name = 'Store';
    }

    return $store_name;
}


/**
 * @description Get method icon
 * @return array
 */
function moyasar_get_method_icon($gateway){
    $icons = [];
    if ($gateway->id === 'moyasar-credit-card') {
        $schemas = $gateway->get_schemas();
        foreach ($schemas as $schema) {
            $icons[] = moyasar_get_icon($schema);
        }
    }
    if ($gateway->id === 'moyasar-stc-pay') {
        $icons[] = moyasar_get_icon('stc_pay');
    }
    if ($gateway->id === 'moyasar-apple-pay') {
        $icons[] = moyasar_get_icon('apple_pay');
    }

    if ($gateway->id === 'moyasar-samsung-pay') {
        $icons[] = moyasar_get_icon('samsung_pay');
    }

    return $icons;
}

/**
 * @description Get Payment Method Class
 * @param
 */
function moyasar_get_payment_method_class($payment_method)
{
    // Moyasar_Apple_Pay_Payment_Gateway
    $payment_method = str_replace(' ', '_', ucwords(str_replace('-', ' ', $payment_method))) . '_Payment_Gateway';

    if (! class_exists($payment_method)) {
        throw new RuntimeException(esc_html(__('Payment method not found', 'moyasar')));
    }


    return new $payment_method();
}

/**
 * @description Logger
 * @param
 */
function moyasar_logger($message, $level = 'info', $order_id = null, $extra = [])
{
    $metadata = ['source' => 'moyasar', 'order' => $order_id];
    $metadata = array_merge($metadata, $extra);
    wc_get_logger()->log($level, $message, $metadata);
}