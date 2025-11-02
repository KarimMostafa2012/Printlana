<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Moyasar_Stc_Pay_Payment_Gateway extends WC_Payment_Gateway
{
    use Moyasar_Gateway_Trait;


    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->id = 'moyasar-stc-pay';

        $this->has_fields = true;
        $this->method_title = __('Moyasar STC Pay', 'moyasar');
        $this->method_description = __('Moyasar Gateway Settings For STC Pay', 'moyasar');

        $this->init_form_fields();
        $this->init_settings();
        $this->set_secrets();
        $this->set_order_options();
        $this->set_title_description_plugin('STC Pay', 'Pay with STC Pay.');

        $this->new_order_status = $this->get_option('new_order_status', 'processing');
        $this->schemas = $this->get_option('schemas', []);
        $this->register_actions();

    }

    /**
     * @description Set Up Admin Settings
     * @return void
     */
    public function init_form_fields()
    {
        $shared_fields = require __DIR__ . '/../utils/admin-settings.php';
        $this->form_fields = $shared_fields;
    }


    /**
     * @description Js Classic Init
     */
    function enqueue_classic_scripts()
    {
        $version = MOYASAR_PAYMENT_VERSION;
        wp_register_script('moyasar-stc-pay', MOYASAR_PAYMENT_URL . '/assets/classic/src/js/triggers/stc-pay-form.js', ['jquery'], $version, true);

        // Localize the script with new data
        $script_data = array(
            'mysrSPPaymentId' => $this->id,
            'mysrSPPublishableKey' => $this->api_pb,
            'mysrSPMoyasarBaseUrl' => $this->api_base_url,
        );
        wp_localize_script('moyasar-stc-pay', 'moyasar_stc_pay', $script_data);
        wp_enqueue_script('moyasar-stc-pay');
    }

    /**
     * @description Set Up Payment Fields (PHP)
     */
    public function payment_fields()
    {
        $this->enqueue_classic_scripts();
        require __DIR__ . '/../views/stc-pay-form.php';
    }


    /**
     * @description Validate Payment Fields (PHP)
     * We validate token, other fields will be validated by JS.
     */
    public function validate_fields()
    {
        if ( ! isset( $_POST['moyasar-stc-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['moyasar-stc-nonce-field'] )) , 'moyasar-form' ) ) {
            wc_add_notice(__('Nonce verification failed.', 'moyasar'), 'error');
            return;
        }

        if (empty(sanitize_text_field( wp_unslash ( $_POST['mysr_token'] ?? '' ) ))) {
            wc_add_notice(__('Something went wrong in the payment process.', 'moyasar'), 'error');
            return false;
        }
        return true;
    }


    /**
     * @description Process Payment
     * @return array
     */
    public function process_payment($order_id)
    {
        if ( ! isset( $_POST['moyasar-stc-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['moyasar-stc-nonce-field'] )) , 'moyasar-form' ) ) {
            return [
                'result' => 'failed',
                'message' => __('Nonce verification failed.', 'moyasar')
            ];
        }

        $source = [
            'type' => 'stcpay',
            'mobile' => sanitize_text_field( wp_unslash (isset($_POST['mysr_token']) ? $_POST['mysr_token'] : '' ) )
        ];

        $payment = $this->payment($order_id, $source, false, function ($order, $payment) {
            // Save STC Pay Transaction Metadata
            $transaction_url = $payment['source']['transaction_url'];
            $otpId = explode('/', parse_url($transaction_url, PHP_URL_PATH))[3];
            parse_str(parse_url($transaction_url, PHP_URL_QUERY), $queryParams);
            $otpToken = $queryParams['otp_token'] ?? null;
            $order->add_meta_data('stc_pay_otp_id', $otpId, true);
            $order->add_meta_data('stc_pay_otp_token', $otpToken, true);
        });

        if ($payment['result'] === 'success') {
            $payment['transactionUrl'] = '';
        }

        return $payment;
    }

}
