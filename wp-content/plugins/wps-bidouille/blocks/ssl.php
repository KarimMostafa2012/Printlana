<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-ssl' ) ) {
	return false;
}

if ( is_ssl() ) {
	$color     = 'green';
	$icon      = 'up';
	$info      = 'ok';
	$message   = __( 'SSL certificate is active', 'wps-bidouille' );
	$class_a   = 'wps-h-btn-disabled';
	$fa_icon   = 'fal fa-shield-check';
	$message_2 = __( 'Your site is in HTTPS', 'wps-bidouille' );
} else {
	$color     = 'red';
	$icon      = 'down';
	$info      = 'error';
	$message   = __( 'You don\'t have an SSL certificate', 'wps-bidouille' );
	$class_a   = 'wps-btn-delete';
	$fa_icon   = 'fal fa-shield';
	$message_2 = __( 'Your site isn\'t in HTTPS', 'wps-bidouille' );
}

$certinfo = \WPS\WPS_Bidouille\Helpers::get_informations_ssl();

if ( ! empty( $certinfo ) ) {
	$message .= ' => ' . __( 'Valid until:', 'wps-bidouille' ) . ' ' . date( 'd/m/Y', $certinfo['validTo_time_t'] );
	$pf      = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
	if ( ! empty( $pf ) && 'Let\'s Encrypt' === $certinfo['issuer']['O'] ) {
		$message .= ' (' . __( 'Automatic renewal', 'wps-bidouille' ) . ')';
	}
} ?>

<div class="wps-updates wpsnotice wps-border-<?php echo esc_attr( $color ); ?> is-dismissible"
     data-dismissible="disable-notice-ssl-forever">
    <div class="wps-up-left"><i
                class="fas fa-thumbs-<?php echo esc_attr( $icon ); ?> wps-icon-<?php echo esc_attr( $color ); ?>"
                data-fa-transform="grow-8"></i>
		<?php echo '<span class="wps-info-' . esc_attr( $info ) . '">' . $message . '</span>'; ?>
    </div>
    <div class="wps-up-right">
        <a class="<?php echo esc_attr( $class_a ); ?>">

           <i class="<?php echo esc_attr( $fa_icon ); ?>"
               data-fa-transform="grow-6"></i> <?php echo esc_html( $message_2 ); ?>
        </a>
    </div>
</div>