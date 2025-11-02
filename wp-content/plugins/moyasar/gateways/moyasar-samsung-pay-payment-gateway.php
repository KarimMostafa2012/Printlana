<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Moyasar_Samsung_Pay_Payment_Gateway extends WC_Payment_Gateway
{
    use Moyasar_Gateway_Trait;
    public static $samsung_hook_registerd = false;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (!self::$samsung_hook_registerd) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            self::$samsung_hook_registerd = true;
        }

        $this->id = 'moyasar-samsung-pay';
        $this->has_fields = false;
        $this->method_title = __('Moyasar Samsung Pay', 'moyasar');
        $this->method_description = __('Moyasar Gateway Settings For SamsungPay', 'moyasar');

        // Load settings from database
        $this->init_form_fields();
        $this->init_settings();
        $this->set_secrets();
        $this->set_order_options();
        $this->set_title_description_plugin('Samsung Pay', 'Pay with Samsung Pay.');
        $this->schemas = $this->get_option('schemas', []);
        $this->register_actions();

    }

    /**
     * Enqueue Samsung Pay SDK script.
     *
     * This method enqueues the Samsung Pay SDK script from Samsung's CDN.
     * The script is necessary for enabling Samsung Pay functionality in the plugin.
     *
     *
     * @return void
     *
     * @note This method loads a third-party script from Samsung's servers.
     *       Users should be aware that their browsers will connect to Samsung's CDN when using this feature.
     *       For more information, see the plugin's readme file and Samsung's terms of service.
     */
    public function enqueue_scripts() {
        $version = MOYASAR_PAYMENT_VERSION;
        wp_enqueue_script('samsung_pay_sdk', 'https://img.mpay.samsung.com/gsmpi/sdk/samsungpay_web_sdk.js', array(), $version, true);
    }

    /**
     * @description Js Classic Init
     */
    function enqueue_classic_scripts()
    {
        $version = MOYASAR_PAYMENT_VERSION;
        wp_register_script('moyasar-samsung-pay-js', MOYASAR_PAYMENT_URL . '/assets/classic/src/js/triggers/samsung-pay-form.js', ['jquery'], $version, true);

        $supported_networks =  $this->settings['schemas'] ?? [];
        $merchant_name = moyasar_get_store_name();
        $store_currency = get_woocommerce_currency();
        $store_country = WC()->countries->get_base_country() ?? 'SA';
        $total = WC()->cart->get_total( 'raw' );
        // Localize the script with new data
        $script_data = array(
            'mysrSPPaymentId' => $this->id,
            'mysrSPPublishableKey' => $this->api_pb,
            'mysrSPMoyasarBaseUrl' => $this->api_base_url,
            'mysrSPStoreCountry' => $store_country,
            'mysrSPStoreCurrency' => $store_currency,
            'mysrSPSupportedNetworks' => $supported_networks,
            'mysrSPSupportedCountries' => $this->settings['supportedCountries'] ?? ['SA'],
            'mysrSPMerchantName' => $merchant_name,
            'mysrSPTotal' => $total,
            'mysrSPServiceId' => $this->settings['service_id'] ?? '',
        );
        wp_localize_script('moyasar-samsung-pay-js', 'moyasar_samsung_pay', $script_data);
        wp_enqueue_script('moyasar-samsung-pay-js');
    }
    /**
     * @description Set Up Payment Fields (PHP)
     */
    public function payment_fields()
    {
        $this->enqueue_classic_scripts();
        require __DIR__ . '/../views/samsung-pay-form.php';
    }


    /**
     * @description Set Up Admin Settings
     * @return void
     */
    public function init_form_fields()
    {
        $shared_fields = require __DIR__ . '/../utils/admin-settings.php';
        $gateway_fields = require __DIR__ . '/../utils/methods/samsung-pay-admin-settings.php';
        $this->form_fields = array_merge($shared_fields, $gateway_fields);
    }


    /**
     * @description Process Payment
     * @return array
     */
    public function process_payment($order_id)
    {
        if ( ! isset( $_POST['moyasar-sp-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['moyasar-sp-nonce-field'] )) , 'moyasar-form' ) ) {
            return [
                'result' => 'failed',
                'message' => __('Nonce verification failed.', 'moyasar')
            ];
        }

        $mysr_token = stripslashes(sanitize_text_field( wp_unslash ( isset($_POST['mysr_token']) ? $_POST['mysr_token'] : '' )) );

        $source = [
            'type' => 'samsungpay',
            'token' => $mysr_token
        ];

        return $this->payment($order_id, $source);

    }

}
