<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\Blocks\CartCheckoutBlockSupport;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

defined( 'ABSPATH' ) || exit();

/**
 * Extend checkout endpoint of StoreAPI
 *
 * @since 3.15.0
 */
class ExtendEndpoint {
    /**
     * Stores Rest Extending instance.
     *
     * @var ExtendSchema
     */
    private static $extend;

    /**
     * Plugin Identifier, unique to each plugin.
     *
     * @var string
     */
    const IDENTIFIER = 'dokan_delivery_time';

    /**
     * Bootstraps the class and hooks required data.
     *
     * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
     *
     * @since 3.15.0
     */
    public static function init( ExtendSchema $extend_rest_api ) {
        self::$extend = $extend_rest_api;
        self::extend_store();
    }

    /**
     * Registers the actual data into each endpoint.
     *
     * @since 3.15.0
     */
    public static function extend_store() {
        // Register into `checkout`
        self::$extend->register_endpoint_data(
            array(
                'endpoint'        => CheckoutSchema::IDENTIFIER,
                'namespace'       => self::IDENTIFIER,
                'schema_callback' => array( self::class, 'extend_checkout_schema' ),
                'schema_type'       => ARRAY_A,
            )
        );
    }

    /**
     * Register subscription product schema into checkout endpoint.
     *
     * @since 3.15.0
     *
     * @return array Registered schema.
     */
    public static function extend_checkout_schema() {
        return [
            'vendor_delivery_time' => [
                'description' => 'Customers delivery time data of vendors',
                'context'     => [ 'view', 'edit' ],
                'type'        => 'array',
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'vendor_id' => [
                            'type'  => 'string',
                            'required' => true,
                        ],
                        'store_name' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'delivery_date' => [
                            'type'   => 'string',
                            'format' => 'date',
                            'required' => true,
                        ],
                        'selected_delivery_type' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'delivery_time_slot' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'store_pickup_location' => [
                            'type' => 'string',
                            'required' => true,
                        ]
                    ],
                ],
            ],
        ];
    }
}
