<?php
/**
 * Plugin Name: test_dokan.php
 * Description: This mu plugin tests Dokan withdraw REST API logging.
 *  Version: 1.0.0
 */
add_action( 'rest_api_init', function() {
    add_filter( 'rest_pre_echo_response', function( $result, $server, $request ) {
        $route = $request->get_route();
        if ( strpos( $route, '/dokan/v1/withdraws' ) !== false ) {
            error_log('[DOKAN WITHDRAW REST] Route: ' . $route . ' Response: ' . print_r( $result, true ));
        }
        return $result;
    }, 10, 3 );
});
