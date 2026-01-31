<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait Moyasar_Gateway_Trait
{

    /**
     * Supported card schemas
     */
    private $schemas;
    private $api_pb;
    private $api_sk;
    private $api_base_url;
    private $webhook_secret;
    private $plugin_title;
    private $plugin_description;

    public static $hooks_registerd = false;

    private $new_order_status;

    public function moyasar_api_url($path = '')
    {
        $url = rtrim($this->api_base_url, '/');

        if (!empty(trim($path))) {
            $url .= '/v1/' . ltrim($path, '/');
        }

        return rtrim($url, '/');
    }

    /** Getters **/
    public function api_sk()
    {
        return $this->api_sk;
    }

    public function get_schemas()
    {
        return $this->schemas ?? [];
    }

    public function webhook_secret()
    {
        return $this->webhook_secret;
    }

    /**
     * @description Set Up Admin Settings
     * @return void
     */
    public function set_order_options()
    {
        $this->supports[] = 'refunds';
        $this->new_order_status = $this->get_option('new_order_status', 'processing');
    }


    /**
     * @description Set Title, Icon & Description for plugin
     * @param $default_title
     * @param $default_description
     * @return void
     */
    public function set_title_description_plugin($default_title, $default_description)
    {
        $this->icon = MOYASAR_PAYMENT_URL . '/assets/general/images/moyasar-icon.png';
        $this->plugin_title = $this->get_option('title');
        $this->plugin_description = $this->get_option('description');
        empty($this->plugin_title) ? $this->title = $default_title : $this->title = $this->plugin_title;
        empty($this->plugin_description) ? $this->description = $default_description : $this->description = $this->plugin_description;
    }

    /**
     * @description Set Secrets
     * @return void
     * @throws Exception
     */
    public function set_secrets()
    {
        $this->api_pb = $this->get_option('api_pb');
        $this->api_sk = $this->get_option('api_sk');
        $this->api_base_url = MOYASAR_API_BASE_URL;
        $this->update_option('webhook_url', moyasar_remove_url_fragment(get_site_url(null, '/?rest_route=/moyasar/v2/webhook')));
        $this->update_option('webhook_secret', get_option('moyasar_webhook_secret'));
        $this->webhook_secret = $this->get_option('webhook_secret');
    }


    /**
     * @param WC_Order $order
     * @return void
     */
    public function add_payment_method_to_order_data($order)
    {
        echo wp_kses_post('Payment Source: ' . $order->get_meta('_moyasar_payment_source'));
    }


    /**
     * @description Set Up Notice (PHP)
     */
    public function add_admin_notices()
    {
        $isEnabled = $this->enabled === 'yes';
        if (!$isEnabled) {
            // Prevent error messages from showing when method is disabled
            return;
        }

        if (empty($this->api_sk) || empty($this->api_pb)) {
            echo '<div class="woocommerce-error">' . esc_html("({$this->method_title}): " . __('Moyasar API keys are missing.', 'moyasar')) . '</div>';
        }
    }

    /**
     * @description Register Actions
     * @return void
     */
    public function register_actions()
    {
        if (!self::$hooks_registerd) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_before_checkout_form', array($this, 'add_admin_notices'));
            add_action('woocommerce_admin_order_data_after_payment_info', array($this, 'add_payment_method_to_order_data'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_filter('script_loader_tag', array($this, 'moyasar_update_src_js'), 10, 2);
            self::$hooks_registerd = true;
        }
    }

    /**
     * @description Adding data-cfasync attribute for the Rocket Loader
     * @param
     */
    function moyasar_update_src_js($tag, $handle)
    {
        if ( $handle === 'apple_pay_sdk' ) {
            return str_replace(' src', ' data-cfasync="false" src', $tag);
        }
        return $tag;
    }

    /**
     * @description Determine New Order Status
     * @return string
     */
    public function determine_new_order_status($status, $id, $instance)
    {
        return $this->new_order_status;
    }

    /**
     * @description Set Icon
     * @return string
     */
    public function get_icon()
    {
        // Detect is RTL or LTR
        $dir = is_rtl() ? 'left' : 'right';

        $images = moyasar_get_method_icon($this);
        $span = '<span style="float: ' . $dir . ';">';
        foreach ($images as $image) {
            $span .= '<img src="' . $image . '" style="margin-right: 10px; max-width: 35px; display: inline;" />';
        }
        $span .= '</span>';
        return apply_filters('woocommerce_gateway_icon', $span, $this->id);
    }

    /**
     * @description Payment API
     * @return array
     */
    function payment($order_id, $source, $custom_return = false, $success_cb = null)
    {

        $order = wc_get_order($order_id);
        WC()->session->set('order_awaiting_payment', $order_id);
        WC()->session->save_data();

        // Set status to pending to indicate that we are still processing payment
        $order->set_status('pending', __('Awaiting payment to complete', 'moyasar'));
        $order->add_meta_data('moyasar_payment_method', $this->id, true);
        $order->save();

        $metadata = [];
        $metadata["order_id"] = "$order_id";

        if ($order->has_shipping_address()) {
            foreach ($order->get_address('shipping') as $key => $value) {
                $metadata["shipping_$key"] = $value;
            }
        }

        if ($order->has_billing_address()) {
            foreach ($order->get_address('billing') as $key => $value) {
                $metadata["billing_$key"] = $value;
            }
        }

        try {
            $payment = Moyasar_Quick_Http::make()
                ->basic_auth($this->api_sk())
                ->post($this->moyasar_api_url("payments"), [
                    'amount' => Moyasar_Currency_Helper::amount_to_minor(
                        $order->get_total(),
                        $order->get_currency()
                    ),
                    'description' => 'Payment for order ' . $order_id,
                    'currency' => $order->get_currency(),
                    'callback_url' => $custom_return ? moyasar_page_url('mysr_checkout', $order_id) : moyasar_page_url('return', $order_id),
                    'metadata' => $metadata,
                    'source' => $source
                ])
                ->json();

            if (is_callable($success_cb)) {
                $success_cb($order, $payment);
            }

            $order->set_transaction_id($payment['id']);
            $order->add_order_note("A Payment initiated, ID: {$payment['id']}");
            $order->save();

        } catch (Exception $e) {
            $reference_code = bin2hex(random_bytes(10));
            $message = $e->getMessage();
            $clientMessage = '';
            moyasar_logger('Moyasar: Could not create payment. Error: ' . $message, 'error', $order_id, ['reference_code' => $reference_code]);

            if ($e instanceof Moyasar_Http_Client_Exception) {
                $response = $e->response;

                if ($response->isValidationError()) {
                    $message = $response->getValidationMessage();
                    $clientMessage = sprintf(
                        __("Please check the payment details and try again. %s", 'moyasar'),
                        $message
                    );
                }
                if ($response->isAuthenticationError()) {
                    $clientMessage = __("Authentication Error, please check your API keys.", 'moyasar');
                }
                if ($response->isCardNotSupportedError()) {
                    $clientMessage = __("Could not create payment, the card is not supported.", 'moyasar');
                }
                if ($clientMessage === '') {
                    $clientMessage = sprintf(
                        __("Could not create payment, please contact the store administrator. (reference number: %s)", 'moyasar'),
                        $reference_code
                    );
                }

            } else {
                $clientMessage = sprintf(
                    __("Could not create payment, please contact the store administrator. (reference number: %s)", 'moyasar'),
                    $reference_code
                );
            }

            return [
                'result' => 'failed',
                'message' => $clientMessage
            ];


        }
        $transaction_url = $payment['source']['transaction_url'];
        $redirect = moyasar_url('return', ['id' => $payment['id'], 'moyasar-nonce-field' => wp_create_nonce('moyasar-form')], $order_id);
        moyasar_logger('Moyasar: Redirecting to ' . $transaction_url, 'info', $order_id);
        moyasar_logger('Moyasar: Callback to ' . $redirect, 'info', $order_id);

        return [
            'result' => 'success',
            'transactionUrl' => $transaction_url,
            'redirect' => $redirect
        ];
    }


    public function process_refund($order_id, $amount = null, $reason = '')
    {
        try {
            if ($order_id <= 0) {
                moyasar_logger('[Moyasar] [Refund] Invalid order ID', 'error');
                throw new RuntimeException('Invalid order ID');
            }

            $order = wc_get_order($order_id);
            if (!$order) {
                moyasar_logger("[Moyasar] [Refund] Could not find order with ID $order_id", 'error');
                throw new RuntimeException("Could not find order with ID $order_id");
            }

            $payment_id = $order->get_transaction_id('edit');
            if (!$payment_id) {
                moyasar_logger("[Moyasar] [Refund] Could not find payment ID for order $order_id", 'error');
                throw new RuntimeException('No Payment is associated with this order.');
            }

            if ($amount > 0) {
                $amount = Moyasar_Currency_Helper::amount_to_minor($amount, $order->get_currency());
            }

            $payment = Moyasar_Quick_Http::make()
                ->basic_auth($this->api_sk())
                ->get($this->moyasar_api_url("payments/$payment_id"))
                ->json();

            $createdAt = new DateTime($payment['created_at']);
            $nowMinus2Hours = (new DateTime())->sub(new DateInterval('PT2H'));
            $tryVoid = $nowMinus2Hours < $createdAt && ($amount == 0 || $amount == $payment['amount']) && $this->id !== 'moyasar-stc-pay';
            $voidWorks = false;

            if ($tryVoid) {
                try {
                    Moyasar_Quick_Http::make()
                        ->basic_auth($this->api_sk())
                        ->post($this->moyasar_api_url("payments/$payment_id/void"));
                    moyasar_logger("[Moyasar] [Refund] Voided payment for order $order_id", 'info', $order_id);
                    $voidWorks = true;
                } catch (Exception $e) {
                    moyasar_logger("[Moyasar] [Refund] [Void] [Exception] {$e->getMessage()}, Fallback to Refund API.", 'error', $order_id);
                }
            }

            if (!$tryVoid || !$voidWorks) {
                Moyasar_Quick_Http::make()
                    ->basic_auth($this->api_sk())
                    ->post(
                        $this->moyasar_api_url("payments/$payment_id/refund"),
                        array_filter(['amount' => $amount])
                    );
                moyasar_logger("[Moyasar] [Refund] Refunded $amount for order $order_id", 'info', $order_id);
            }

            return true;
        } catch (Moyasar_Http_Exception $e) {
            $message = $e->getMessage();

            if ($e->response->isJson()) {
                $body = $e->response->json();
                $message = isset($body['message']) ? $body['message'] : $message;
            }
            moyasar_logger("[Moyasar] [Refund] [Moyasar_Http_Exception] $message", 'error', $order_id);

            return new WP_Error('refund_error', $message);
        } catch (Exception $e) {
            moyasar_logger("[Moyasar] [Refund] [Exception] {$e->getMessage()}", 'error', $order_id);
            return new WP_Error('refund_error', $e->getMessage());
        }
    }

    public function payment_scripts()
    {
        // we need JavaScript to process a token only on cart/checkout pages.
        if (!is_cart() && !is_checkout()) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled) {
            return;
        }

        // and this is our custom JS in your plugin directory that works with token.js
        $version = MOYASAR_PAYMENT_VERSION; // Dev: rand(1, 1000000);

        /* Push translations */
        wp_set_script_translations('wc-payment-method-moyasar', 'moyasar', MOYASAR_PAYMENT_DIR . '/i18n/languages');

        /* Push CSS */
        wp_register_style('wc-payment-method-moyasar', MOYASAR_PAYMENT_URL . '/assets/general/css/mysr.css', [], $version);
        wp_enqueue_style('wc-payment-method-moyasar');

        /* Gateway Request Class */
        /* Pass Vars */
        wp_localize_script('moyasar_initiate_js', 'moyasar', [
            'version' => $version
        ]);
        wp_register_script('moyasar_initiate_js', MOYASAR_PAYMENT_URL . '/assets/general/js/requests/initiate.js', array('jquery', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_initiate_js');

        /* Order Request Class */
        wp_register_script('moyasar_order_js', MOYASAR_PAYMENT_URL . '/assets/general/js/requests/order.js', array('jquery', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_order_js');


        /* Apple Pay Helpers */
        wp_register_script('moyasar_apple_helper_js', MOYASAR_PAYMENT_URL . '/assets/general/js/helpers/appleHelper.js', array('jquery', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_apple_helper_js');

        /* Samsung Pay Helpers */
        wp_register_script('moyasar_samsung_helper_js', MOYASAR_PAYMENT_URL . '/assets/general/js/helpers/samsungHelper.js', array('jquery', 'wc-checkout', 'wp-plugins', 'wp-i18n'), $version, true);
        wp_enqueue_script('moyasar_samsung_helper_js');


        /* Trigger Helpers */
        wp_register_script('moyasar_triggers_js', MOYASAR_PAYMENT_URL . '/assets/general/js/helpers/triggers.js', array('jquery', 'wc-checkout', 'wp-plugins', 'wp-i18n'), $version, true);
        wp_enqueue_script('moyasar_triggers_js');

        /* Validation JS */
        wp_register_script('moyasar_validation_js', MOYASAR_PAYMENT_URL . '/assets/general/js/helpers/validation.js', array('jquery', 'wp-plugins', 'wc-payment-method-moyasar', 'wc-checkout', 'wp-i18n'), $version, true);
        wp_enqueue_script('moyasar_validation_js');


        /* Classic Imports */
        $classic_folder = MOYASAR_PAYMENT_URL . '/assets/classic/src/js/forms';

        /* Credit Card Classic */
        wp_register_script('moyasar_credit_card_classic_js', $classic_folder . '/creditcard.js', array('jquery', 'moyasar_initiate_js', 'moyasar_order_js', 'moyasar_triggers_js', 'moyasar_validation_js', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_credit_card_classic_js');

        /* Apple Pay Classic */
        wp_register_script('moyasar_apple_pay_classic_js', $classic_folder . '/applepay.js', array('jquery', 'moyasar_initiate_js', 'moyasar_order_js', 'moyasar_triggers_js', 'moyasar_validation_js', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_apple_pay_classic_js');

        /* STC Pay Classic */
        wp_register_script('moyasar_stc_pay_classic_js', $classic_folder . '/stcpay.js', array('jquery', 'moyasar_initiate_js', 'moyasar_order_js', 'moyasar_triggers_js', 'moyasar_validation_js', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_stc_pay_classic_js');

        /* Samsung Pay Classic */
        wp_register_script('moyasar_samsung_pay_classic_js', $classic_folder . '/samsungpay.js', array('jquery', 'moyasar_initiate_js', 'moyasar_order_js', 'moyasar_triggers_js', 'moyasar_validation_js', 'wc-checkout'), $version, true);
        wp_enqueue_script('moyasar_samsung_pay_classic_js');

    }


}
