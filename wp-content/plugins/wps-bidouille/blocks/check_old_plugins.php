<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	return false;
}

if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-check-old-plugins' ) ) {
	return false;
}

if ( false === ( $old_plugin = get_transient( 'wps_old_plugin' ) ) ) :

    include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
    $plugins = get_plugins();
    if ( empty( $plugins ) ) {
        return false;
    }

    $old_plugin = array();
    foreach ( $plugins as $plugin_file => $data ) {

        $plugin = explode( '/', $plugin_file );
        $api    = plugins_api( 'plugin_information', array(
            'slug'   => reset( $plugin ),
            'fields' => array(
                'short_description' => false,
                'description'       => false,
                'sections'          => false,
                'tested'            => true,
                'requires'          => true,
                'rating'            => false,
                'ratings'           => false,
                'downloaded'        => false,
                'downloadlink'      => false,
                'last_updated'      => true,
                'added'             => true,
                'tags'              => false,
                'compatibility'     => true,
                'homepage'          => true,
                'versions'          => false,
                'donate_link'       => false,
                'reviews'           => false,
                'banners'           => false,
                'icons'             => false,
                'active_installs'   => false,
                'group'             => false,
                'contributors'      => false,
            ),
        ) );

        if ( is_wp_error( $api ) ) {
            continue;
        }

        if ( isset( $api->last_updated ) ) {
	        $last_updated_timestamp = strtotime( $api->last_updated );
	        if ( $last_updated_timestamp > 0 && ( $last_updated_timestamp < ( time() - ( 730 * 24 * 60 * 60 ) ) ) ) {
		        $old_plugin[] = $api->name;
	        }
        }
    }
	set_transient( 'wps_old_plugin', $old_plugin, MONTH_IN_SECONDS );
endif;

if ( empty( $old_plugin ) ) {
    return false;
} ?>

<div class="wps-updates wpsnotice wps-border-red is-dismissible"
     data-dismissible="disable-notice-check-old-plugins-forever">
    <div class="wps-up-left">
        <i class="fas fa-thumbs-down wps-icon-red" data-fa-transform="grow-8"></i>
        <?php _e( 'Warning, one or more of your extensions have not been updated for more than 2 years', 'wps-bidouille' ); ?>
        <?php echo ' ( ' . implode(', ', $old_plugin ) . ')'; ?>
    </div>
    <div class="wps-up-right">
        <a class="wps-btn-delete"
           href="<?php echo network_admin_url( 'plugins.php?plugin_status=all&wps_bidouille_old_plugin=true' ); ?>"><i
                    class="fal fa-info" data-fa-transform="grow-6"></i>
            <?php _e( 'See the extensions', 'wps-bidouille' ); ?></a>
    </div>
</div>