<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Moyasar_Apple_Pay_Payment_Gateway extends WC_Payment_Gateway
{
    use Moyasar_Gateway_Trait;
    public static $apple_hook_registerd = false;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (!self::$apple_hook_registerd) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            self::$apple_hook_registerd = true;
        }

        $this->id = 'moyasar-apple-pay';
        $this->has_fields = false;
        $this->method_title = __('Moyasar Apple Pay', 'moyasar');
        $this->method_description = __('Moyasar Gateway Settings For ApplePay', 'moyasar');

        // Load settings from database
        $this->init_form_fields();
        $this->init_settings();
        $this->set_secrets();
        $this->set_order_options();
        $this->set_title_description_plugin('Apple Pay', 'Pay with Apple Pay.');
        $this->schemas = $this->get_option('schemas', []);
        $this->register_actions();

    }

    /**
     * Enqueue Apple Pay SDK script.
     *
     * This method enqueues the Apple Pay SDK script from Apple's CDN.
     * The script is necessary for enabling Apple Pay functionality in the plugin.
     *
     * @link https://developer.apple.com/documentation/apple_pay_on_the_web Apple Pay on the Web documentation
     *
     * @return void
     *
     * @note This method loads a third-party script from Apple's servers.
     *       Users should be aware that their browsers will connect to Apple's CDN when using this feature.
     *       For more information, see the plugin's readme file and Apple's terms of service.
     */
    public function enqueue_scripts() {
        $version = MOYASAR_PAYMENT_VERSION;
        wp_enqueue_script('apple_pay_sdk', 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js', array(), $version, true);
    }

    /**
     * @description Js Classic Init
     */
    function enqueue_classic_scripts()
    {
        $version = MOYASAR_PAYMENT_VERSION;
        wp_register_script('moyasar-apple-pay-js', MOYASAR_PAYMENT_URL . '/assets/classic/src/js/triggers/apple-pay-form.js', ['jquery'], $version, true);

        $supported_networks =  $this->settings['schemas'] ?? [];
        $merchant_name = moyasar_get_store_name();
        $store_currency = get_woocommerce_currency();
        $store_country = WC()->countries->get_base_country() ?? 'SA';
        $total = WC()->cart->get_total( 'raw' );
        // Localize the script with new data
        $script_data = array(
            'mysrAPPaymentId' => $this->id,
            'mysrAPPublishableKey' => $this->api_pb,
            'mysrAPMoyasarBaseUrl' => $this->api_base_url,
            'mysrAPStoreCountry' => $store_country,
            'mysrAPStoreCurrency' => $store_currency,
            'mysrAPSupportedNetworks' => $supported_networks,
            'mysrAPSupportedCountries' => $this->settings['supportedCountries'] ?? ['SA'],
            'mysrAPMerchantName' => $merchant_name,
            'mysrAPTotal' => $total,
        );
        wp_localize_script('moyasar-apple-pay-js', 'moyasar_apple_pay', $script_data);
        wp_enqueue_script('moyasar-apple-pay-js');
    }
    /**
     * @description Set Up Payment Fields (PHP)
     */
    public function payment_fields()
    {
        $this->enqueue_classic_scripts();
        require __DIR__ . '/../views/apple-pay-form.php';
    }


    /**
     * @description Set Up Admin Settings
     * @return void
     */
    public function init_form_fields()
    {
        $shared_fields = require __DIR__ . '/../utils/admin-settings.php';
        $gateway_fields = require __DIR__ . '/../utils/methods/apple-pay-admin-settings.php';
        $this->form_fields = array_merge($shared_fields, $gateway_fields);
    }


    /**
     * @description Process Payment
     * @return array
     */
    public function process_payment($order_id)
    {
        if ( ! isset( $_POST['moyasar-ap-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['moyasar-ap-nonce-field'] )) , 'moyasar-form' ) ) {
            return [
                'result' => 'failed',
                'message' => __('Nonce verification failed.', 'moyasar')
            ];
        }

        $mysr_token = stripslashes(sanitize_text_field( wp_unslash ( isset($_POST['mysr_token']) ? $_POST['mysr_token'] : '' )) );

        $source = [
            'type' => 'applepay',
            'token' => $mysr_token
        ];

        return $this->payment($order_id, $source);

    }

}
