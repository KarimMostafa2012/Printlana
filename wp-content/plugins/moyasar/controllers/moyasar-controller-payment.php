<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Moyasar_Controller_Payment
{
    public static $instance;

    protected $requestId;

    public static function init()
    {
        $controller = new static();

        add_action('rest_api_init', array($controller, 'register_routes'));

        return static::$instance = $controller;
    }

    public function __construct()
    {
        $this->requestId = bin2hex(random_bytes(4));
    }

    public function register_routes()
    {
        register_rest_route(
            'moyasar/v2',
            'webhook',
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'handle_webhook'),
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function handle_webhook(WP_REST_Request $request)
    {
        $payload = $request->get_body();

        $webhook_data = json_decode($payload, true);

        if (! $this->secureCompare(get_option('moyasar_webhook_secret'), sanitize_text_field( wp_unslash($webhook_data['secret_token'])))) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $this->error('Webhook received with invalid secret')
            ), 401);
        }

        $payload = $webhook_data['data'];
        $payment_id = sanitize_text_field( wp_unslash($payload['id']) );
        $order_id = sanitize_text_field( wp_unslash($payload['metadata']['order_id']) );

        $order = wc_get_order($order_id);

        if (! $order) {
            return [
                'success' => false,
                'message' => $this->error('Could not find the order: ' . $order_id)
            ];
        }

        if ($order->get_status() != 'pending') {
            return [
                'success' => true,
                'message' => $this->info('[Moyasar] [Webhook] Order is not pending, skipping.')
            ];
        }

        if (! in_array(sanitize_text_field( wp_unslash($payload['status']) ), ['paid', 'authorized', 'captured'])) {
            return [
                'success' => true,
                'message' => $this->info('[Moyasar] [Webhook] Payment is not a success.')
            ];
        }

        // Wait in-case redirection is happening at the moment to avoid a race condition
        // Moyasar webhooks will wait 10 seconds before timing out, we will sleep 7
        sleep(7);

        // Check order status again
        $order = wc_get_order($order_id);
        $payment_method = $order->get_payment_method();
        if ($order->get_status() != 'pending') {
            return [
                'success' => true,
                'message' => $this->info('[Moyasar] [Webhook] Order was processed by redirection, skipping.')
            ];
        }

        try {
            $returnHelpers = new Moyasar_Controller_Return();
            $gateway = moyasar_get_payment_method_class($payment_method);
            $payment = Moyasar_Quick_Http::make()
                ->basic_auth($gateway->api_sk())
                ->get($gateway->moyasar_api_url("payments/$payment_id"))
                ->json();


            add_filter('woocommerce_payment_complete_order_status', array($gateway, 'determine_new_order_status'), PHP_INT_MAX, 3);

            Moyasar_Helper_Coupons::tryApplyCoupon($order, $payment);

            $paymentId = $payment['id'];
            $paymentSource = $returnHelpers->paymentSource($payment);
            $status = $gateway->get_option('new_order_status');
            $order = wc_get_order($order_id);
            $order->read_meta_data(true);
            // Check if the order updated or not
            if ( ! $order->meta_exists('_moyasar_payment_source') )
            {
                $order->add_order_note($this->info("[Moyasar] [Webhook] Payment $paymentId for order is complete, new status: $status ."));
                $order->update_meta_data('_moyasar_payment_source', $paymentSource);
                $order->set_status($status);
                $order->payment_complete($paymentId);
                if ($paymentSource == 'Credit Card'){
                    $order->update_meta_data('_moyasar_token_id', $payment['source']['token'], true);
                }

            }else{
                $order->add_order_note($this->info("[Moyasar] [Webhook] Payment $paymentId for order is complete, Status Already Updated."));
            }

            $order->save();

            return [
                'success' => true,
                'message' => 'Payment is successful.'
            ];

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function info($message, $context = [])
    {
        moyasar_logger(sprintf("Moyasar: [%s] %s", $this->requestId, $message), 'info');
        return $message;
    }

    private function error($message, $context = [])
    {
        moyasar_logger(sprintf("Moyasar: [%s] %s", $this->requestId, $message), 'error');
        return $message;
    }

    // Compare two strings in constant time to prevent timing attacks
    private function secureCompare($a, $b) {
        if (strlen($a) != strlen($b)) {
            return false;
        }

        $equal = true;

        for ($i = 0; $i < strlen($a); ++$i) {
            $equal = $equal && ($a[$i] === $b[$i]);
        }

        return $equal;
    }
}
