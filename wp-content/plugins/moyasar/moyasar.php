<?php

/**
 * Plugin Name: Moyasar
 * Plugin URI: https://www.moyasar.com/
 * Description: Moyasar Payment Gateway, Adds credit card, Apple Pay, and STC Pay payment capabilities to Woocommerce.
 * Version: 7.3.6
 * Requires at least: 4.6
 * Requires PHP: 5.6
 * WC tested up to: 6.8
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Moyasar Development Team
 * Author URI: https://docs.moyasar.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (defined('MOYASAR_PAYMENT_VERSION')) {
    return;
}


define('MOYASAR_PAYMENT_VERSION', '7.3.6');
define('MOYASAR_PLUGIN_MIN_PHP_VER', '5.6.0');
define('MOYASAR_PLUGIN_MIN_WC_VER',  '3.0');
define('MOYASAR_PAYMENT_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
define('MOYASAR_PAYMENT_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('MOYASAR_PAYMENT_PLUGIN_DIR_NAME', basename(MOYASAR_PAYMENT_DIR));

/**
 * Moyasar API base URL.
 *
 * @note External service. See readme for terms of service and privacy policy.
 * @link https://moyasar.com
 */
define('MOYASAR_API_BASE_URL', 'https://api.moyasar.com');

$secret_exists = get_option('moyasar_webhook_secret');
if (!$secret_exists){
    update_option('moyasar_webhook_secret', bin2hex(random_bytes(14)));
}
function moyasar_notice_missing()
{
    $message = sprintf( __( 'Moyasar Payment Gateway requires WooCommerce to be installed and active. You can download %s here.', 'moyasar' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' );
    echo esc_html('<div class="error"><p><strong>' . $message . '</strong></p></div>');
}

function moyasar_notice_unsupported()
{
    $message = sprintf( __( 'Moyasar Payment Gateway is disabled. WooCommerce %2$s is not supported.', 'moyasar' ), WC_VERSION );
    echo esc_html('<div class="error"><p><strong>' . $message . '</strong></p></div>');
}

add_action('plugins_loaded', 'moyasar_init_plugin');

function moyasar_init_plugin()
{
    // Load Utils
    require_once 'utils/helpers.php';
    require_once 'utils/currency.php';
    require_once 'quick-http/class-moyasar-quick-http.php';

    // Load texts
    load_plugin_textdomain( 'moyasar', false, MOYASAR_PAYMENT_PLUGIN_DIR_NAME . '/i18n/languages');

    // If Woocommerce cannot be detected, add a notice that Moyasar Payment Plugin requires it
    if (! class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'moyasar_notice_missing');
        return;
    }

    if (version_compare(WC_VERSION, MOYASAR_PLUGIN_MIN_WC_VER, '<')) {
        add_action('admin_notices', 'moyasar_notice_unsupported');
        return;
    }

    // Load Dependencies
    require_once 'gateways/shared/moyasar-gateway-trait.php';
    require_once 'gateways/moyasar-credit-card-payment-gateway.php';
    require_once 'gateways/moyasar-stc-pay-payment-gateway.php';
    require_once 'gateways/moyasar-apple-pay-payment-gateway.php';
    require_once 'gateways/moyasar-samsung-pay-payment-gateway.php';
    require_once 'helpers/moyasar-helper-coupons.php';
    require_once 'controllers/moyasar-controller-payment.php';
    require_once 'controllers/moyasar-controller-return.php';
    require_once 'controllers/moyasar-controller-order-helper.php';

    // Init REST Services
    Moyasar_Controller_Payment::init();
    Moyasar_Controller_Return::init();
    Moyasar_Controller_Order_Details::init();
}

add_filter('woocommerce_payment_gateways', 'moyasar_register_gateway');

function moyasar_register_gateway($methods)
{
    $methods[] = 'Moyasar_Credit_Card_Payment_Gateway';
    $methods[] = 'Moyasar_Stc_Pay_Payment_Gateway';
    $methods[] = 'Moyasar_Apple_Pay_Payment_Gateway';
    $methods[] = 'Moyasar_Samsung_Pay_Payment_Gateway';

    return $methods;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__ ), 'moyasar_action_links');

function moyasar_action_links($links)
{
    $links[] = '<a href="'. wc_admin_url('&page=wc-settings&tab=checkout&section=moyasar-form') .'">' . __('Gateway Settings', 'moyasar') . '</a>';

    return $links;
}

add_action('woocommerce_blocks_loaded', 'moyasar_woocommerce_blocks_support');

function moyasar_woocommerce_blocks_support() {
    if (! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once 'blocks/moyasar-payment-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new Moyasar_Payment_Block);
        }
    );

    add_action( 
        'before_woocommerce_init',
        function() {
            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }
        }
    );
}

