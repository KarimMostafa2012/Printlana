<?php

if ( !function_exists( 'omfw_fs' ) ) {
    // Create a helper function for easy SDK access.
    function omfw_fs() {
        global $omfw_fs;
        if ( !isset( $omfw_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
            $omfw_fs = fs_dynamic_init( array(
                'id'             => '13130',
                'slug'           => 'order-chats-for-woocommerce',
                'type'           => 'plugin',
                'public_key'     => 'pk_161857a0d390dcb26ebb4add0546f',
                'is_premium'     => false,
                'premium_suffix' => 'Premium',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'is_live'        => true,
            ) );
        }
        return $omfw_fs;
    }

    // Init Freemius.
    omfw_fs();
    // Signal that SDK was initiated.
    do_action( 'omfw_fs_loaded' );
    function omfw_fs_activation_url() {
        return ( omfw_fs()->is_activation_mode() ? omfw_fs()->get_activation_url() : omfw_fs()->get_upgrade_url() );
    }

}