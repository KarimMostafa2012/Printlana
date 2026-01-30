<?php

/**
 * Setting page link from plugin page, so that: user can get easily from Plugin Install page.
 * 
 * @param array $links Getting wordpress default array as name $links
 * @return array
 * @since 1.0.0
 */
function wcmmq_add_action_links($links) {

    $my_links[] = '<a href="' . admin_url('admin.php?page=wcmmq-min-max-control') . '" title="Setting">Settings</a>';
    $my_links[] = '<a href="https://codeastrology.com/my-support/?utm_source=MinMaxControl+Dashboard&utm_medium=Free+Version" title="' . esc_attr__( 'CodeAstrology Support', 'woo-min-max-quantity-step-control-single' ) . '" target="_blank">'.esc_html__( 'Get Support','woo-min-max-quantity-step-control-single' ).'</a>';


    
    return array_merge($my_links, $links);
}
add_filter('plugin_action_links_woo-min-max-quantity-step-control-single/wcmmq.php', 'wcmmq_add_action_links');

add_filter( 'plugins_api_result', 'wcmmq_browse_plugin_result', 1, 3 );
function wcmmq_browse_plugin_result( $res, $action, $args ){
    
    if ( $action !== 'query_plugins' ) {
            return $res;
    }
    
    //phpcs:ignore WordPress.Security.NonceVerification.Recommended - Not needed for this read-only request
    if( isset( $_GET['page'] ) && $_GET['page'] == 'wcmmq-browse-plugins' ){
        //Will Continue
    }else{
        return $res;
    }
    $browse_plugins = get_transient( 'codersaiful_browse_plugins' );

    if( $browse_plugins ){
        return $browse_plugins;//As $res
    }
    
    
    
    $wp_version = get_bloginfo( 'version', 'display' );
    $action = 'query_plugins';
    $args = array(
        'page' => 1,
        'wp_version' => $wp_version
    );
    $args['author']          = 'codersaiful';
    $url = 'http://api.wordpress.org/plugins/info/1.2/';
    $url = add_query_arg(
            array(
                    'action'  => $action,
                    'request' => $args,
            ),
            $url
    );

    $http_url = $url;
    $ssl      = wp_http_supports( array( 'ssl' ) );
    if ( $ssl ) {
            $url = set_url_scheme( $url, 'https' );
    }

    $http_args = array(
            'timeout'    => 15,
            'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
    );
    $request   = wp_remote_get( $url, $http_args );

    if ( $ssl && is_wp_error( $request ) ) {
            if ( ! wp_is_json_request() ) {
                    trigger_error(
                            sprintf(
                                    /* translators: %s: Support forums URL. */
                                    __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
                                    __( 'https://wordpress.org/support/forums/' )
                            ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)' ),
                            headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
                    );
            }

            $request = wp_remote_get( $http_url, $http_args );
    }


    $res = json_decode( wp_remote_retrieve_body( $request ), true );
    if ( is_array( $res ) ) {
            // Object casting is required in order to match the info/1.0 format.
            $res = (object) $res;
            set_transient( 'codersaiful_browse_plugins' , $res, 32000);
    }
    
    return $res;
}