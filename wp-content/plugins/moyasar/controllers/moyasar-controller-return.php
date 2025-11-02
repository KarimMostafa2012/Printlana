<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Moyasar_Controller_Return
{
    public static $instance;

    protected $gateway;

    public static function init()
    {
        $controller = new static();

        add_action('wp', array($controller, 'handle_user_return'));

        return static::$instance = $controller;
    }

    private function perform_redirect($url)
    {
        wp_safe_redirect($url);
        exit;
    }

    public function handle_user_return(WP $wordpress)
    {

        if ($this->get_query_param('moyasar_page') != 'return') {
            return;
        }


        if (! $this->get_query_param('id')) {
            $this->perform_redirect(wc_get_checkout_url());

            return;
        }

        try {
            $order = $this->get_current_order_or_fail();
            $payment_method = $order->get_payment_method();
            $gateway = moyasar_get_payment_method_class($payment_method);
            $payment_id = $order->get_transaction_id('edit');

            if (!$payment_id) {
                moyasar_logger("[Moyasar] [Return] [Payment]: Payment ID not found", 'error', $order->get_id());
                throw new RuntimeException(__('Cannot retrieve saved invoice ID for order.', 'moyasar'));
            }

            $payment = null;

            if ($payment_method === 'moyasar-stc-pay'){
                if (! $this->get_query_param('otp')) {
                    $this->perform_redirect(wc_get_checkout_url());
                    return;
                }
                $otp = $this->get_query_param('otp');
                $payment = $this->fetch_stc_payment($gateway, $order, $otp);
            }else{
                $payment  = $this->fetch_payment($gateway, $payment_id);
            }

            moyasar_logger("[Moyasar] [Return] [Payment]: " . wp_json_encode($payment), 'info', $order->get_id());


            if ($payment['status'] != 'paid') {
                # Taking last payment
                $message = isset($payment['source']['message']) ? $payment['source']['message'] : 'no message';
                $message = sprintf(
                    __('Payment %1$s for order was not complete. Message: %2$s. Payment Status: %3$s', 'moyasar'),
                    $payment_id,
                    $message,
                    $payment['status']
                );

                $order->set_status('failed');
                $order->add_order_note($message);
                $order->save();

                wc_add_notice($message, 'error');

                $this->perform_redirect(wc_get_checkout_url());
                return;
            }

            add_filter('woocommerce_payment_complete_order_status', array($gateway, 'determine_new_order_status'), PHP_INT_MAX, 3);

            WC()->cart->empty_cart();


            Moyasar_Helper_Coupons::tryApplyCoupon($order, $payment);

            $payment_id = $payment['id'];
            $paymentSource = $this->paymentSource($payment);
            $status = $gateway->get_option('new_order_status');
            $order->read_meta_data(true);
            if ( ! $order->meta_exists('_moyasar_payment_source') )
            {
                $order->add_order_note("Payment $payment_id for order is complete, new status: $status.");
                $order->set_status($status); // $gateway->get_option('new_order_status'
                $order->payment_complete();
                $order->update_meta_data('_moyasar_payment_source', $paymentSource);
                if ($paymentSource == 'Credit Card'){
                    $order->update_meta_data('_moyasar_token_id', $payment['source']['token'], true);
                }
                moyasar_logger("[Moyasar] [Return] [Success]: Payment ID: $payment_id is paid, Redirecting to " . $gateway->get_return_url($order), 'info', $order->get_id());
            } else {
                $order->add_order_note("Payment $payment_id for order is complete, Status Already Updated.");
                moyasar_logger("[Moyasar] [Return] [Success]: Payment ID: $payment_id is paid & Completed, Redirecting to " . $gateway->get_return_url($order), 'info', $order->get_id());
            }

            $order->save();
            $this->perform_redirect(
                $gateway->get_return_url($order)
            );
            return;
        } catch (Moyasar_Http_Exception $e) {
            $message = $e->getMessage();

            if ($e->response->isJson()) {
                $body = $e->response->json();
                $message = isset($body['message']) ? $body['message'] : $message;
            }

            moyasar_logger("[Moyasar] [Return] [Http_Exception]: $message", 'error');
            wc_add_notice($message, 'error');

            $this->perform_redirect(wc_get_checkout_url());
            return;
        } catch (Exception $e) {
            $message = $e->getMessage();
            moyasar_logger("[Moyasar] [Return] [Exception]: $message", 'error');
            wc_add_notice($message, 'error');

            $this->perform_redirect(wc_get_checkout_url());
            return;
        }
    }

    public function paymentSource($payment)
    {
        if (! isset($payment['source']['type'])) {
            return null;
        }

        switch (strtolower($payment['source']['type'])) {
            case 'creditcard':
                return 'Credit Card';
            case 'applepay':
                return 'Apple Pay';
            case 'stcpay':
                return 'Stc pay';
            default:
                return null;
        }
    }

    /**
     * @description Get Current Order
     * @return WC_Order $order
     */
    public function get_current_order_or_fail()
    {
        $order = $this->get_current_order();
        if (! $order instanceof WC_Order) {
            moyasar_logger("[Moyasar] [Return] [Order]: Order not found", 'error');
            throw new RuntimeException(esc_html(__('Cannot retrieve current order', 'moyasar')));
        }

        return $order;
    }

    /**
     * @description Get Current Order
     * @return WC_Order $order
     */
    public function get_current_order()
    {
        $session = WC()->session;

        if ($session) {
            moyasar_logger("[Moyasar] [Return] [Order]: Getting order from session", 'info');
            $order_id = $session->get('order_awaiting_payment');

            if (absint($order_id) > 0) {
                return wc_get_order($order_id);
            }
        }
        moyasar_logger("[Moyasar] [Return] [Order]: Getting order from URL", 'info');


        return $this->get_order_from_url();
    }


    /**
     * @description Get Order From URL
     * @return WC_Order $order
     */
    public function get_order_from_url()
    {
        $order_id = absint($this->get_query_param('order-pay'));
        if (!$order_id){
            moyasar_logger("[Moyasar] [Return] [Order]: Order ID not found (Null)", 'error');
            return null;
        }
        $order = wc_get_order($order_id);
        if (! $order || $order_id !== $order->get_id()) {
            moyasar_logger("[Moyasar] [Return] [Order]: Order ID $order_id not found", 'error');
            return null;
        }

        WC()->session->set('order_awaiting_payment', $order_id);
        return $order;
    }

    /**
     * @description Get Query Param
     * @param string $key
     */
    private function get_query_param($key, $default = null)
    {
        if ( ! isset( $_GET['moyasar-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_GET['moyasar-nonce-field'] )) , 'moyasar-form' ) ) {
            return null;
        }
        return isset($_GET[$key]) ? sanitize_text_field( wp_unslash ($_GET[$key] ) ): $default;
    }

    /**
     * @description Fetch Payment Details
     * @param
     */
    private function fetch_payment($gateway, $payment_id)
    {
        return Moyasar_Quick_Http::make()
            ->basic_auth($gateway->api_sk())
            ->get($gateway->moyasar_api_url("payments/$payment_id"))
            ->json();
    }

    /**
     * @description Fetch STC Payment Details
     * @param
     */
    private function fetch_stc_payment($gateway, $order, $otp)
    {
        return Moyasar_Quick_Http::make()
            ->basic_auth($gateway->api_sk())
            ->get($gateway->moyasar_api_url("/stc_pays/{$order->get_meta('stc_pay_otp_id')}/proceed"),
                [
                    'otp_token' => $order->get_meta('stc_pay_otp_token'),
                    'otp_value' => $otp
                ]
            )
            ->json();
    }

}
