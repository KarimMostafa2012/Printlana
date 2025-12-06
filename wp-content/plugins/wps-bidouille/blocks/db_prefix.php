<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	return false;
}

if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-db-prefix' ) ) {
	return false;
}

$pf = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
if ( ! empty( $pf ) ) {
	$wps_site_details = \WPS\WPS_Bidouille\Helpers::_get_wps_user();

	if ( $wps_site_details['env'] !== 'prod' ) {
		return false;
	}
}
$color   = 'green';
$icon    = 'up';
$info    = 'ok';
$message = __( 'The prefix of your database is not the default one', 'wps-bidouille' );

if ( 'wp_' == \WPS\WPS_Bidouille\Helpers::wps_db_prefix() ) {
	$color   = 'red';
	$icon    = 'down';
	$info    = 'error';
	$message = __( 'Please change the prefix of your database!', 'wps-bidouille' );
}

if ( isset( $_GET['wps_notice_change_db_prefix'] ) && ! empty( $_GET['wps_notice_change_db_prefix'] ) ) {
	switch ( $_GET['wps_notice_change_db_prefix'] ) {
		case 1:
			$color   = 'green';
			$icon    = 'up';
			$info    = 'ok';
			$message = __( 'The wp-config file has been successfully updated with prefix', 'wps-bidouille' ) . ' <b>' . \WPS\WPS_Bidouille\Helpers::wps_db_prefix() . '</b>!';
			break;
		case 2:
			$color   = 'red';
			$icon    = 'down';
			$info    = 'error';
			$message = __( 'The wp-config file could not be updated! You have to manually update the table_prefix variable to the one you have specified:', 'wps-bidouille' ) . ' ' . \WPS\WPS_Bidouille\Helpers::wps_db_prefix();
			break;
		case 3:
			$color   = 'red';
			$icon    = 'down';
			$info    = 'error';
			$message = __( 'An error has occurred and the tables could not be updated!', 'wps-bidouille' );
			break;
		case 4:
			$color   = 'red';
			$icon    = 'down';
			$info    = 'error';
			$message = __( 'There are no tables to rename!', 'wps-bidouille' );
			break;
	}
} ?>

<div class="wps-updates wpsnotice wps-border-<?php echo esc_attr( $color ); ?> is-dismissible"
     data-dismissible="disable-notice-db-prefix-forever">
    <div class="wps-up-left"><i
                class="fas fa-thumbs-<?php echo esc_attr( $icon ); ?> wps-icon-<?php echo esc_attr( $color ); ?>"
                data-fa-transform="grow-8"></i>
		<?php echo '<span class="wps-info-' . esc_attr( $info ) . '">' . $message . '</span>'; ?>
    </div>
    <div class="wps-up-right">
		<?php if ( 'wp_' == \WPS\WPS_Bidouille\Helpers::wps_db_prefix() ) : ?>
            <a href="<?php echo add_query_arg( 'action', 'change_db_prefix', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille' ), 'change_db_prefix', 'wps-nonce' ) ); ?>"
               class="wps-btn-info"><i
                        class="fal fa-cog"
                        data-fa-transform="grow-4"></i> <?php _e( 'Update database prefix', 'wps-bidouille' ); ?>
            </a>
		<?php else : ?>
            <a class="wps-h-btn-disabled"><i class="fal fa-shield-check"
                                             data-fa-transform="grow-6"></i> <?php _e( 'The prefix is ​​secure', 'wps-bidouille' ); ?>
            </a>
		<?php endif; ?>
    </div>
</div>
