<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Moyasar_Controller_Order_Details
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
            'order-details',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_cart_total'),
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function get_cart_total()
    {
        try {
            WC()->cart->calculate_totals();
            return new WP_REST_Response(array(
                'success' => true,
                'order' => [
                    'total_row' => WC()->cart->get_totals()['total']
                ],
                'message' => 'Cart total retrieved successfully'
            ), 200);

        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $this->error('Error retrieving cart total: ' . $e->getMessage())
            ), 500);
        }
    }

    private function error($message, $context = [])
    {
        moyasar_logger(sprintf("CartTotal: [%s] %s", $this->requestId, $message), 'error');
        return $message;
    }
}