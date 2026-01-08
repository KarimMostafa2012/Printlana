<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	return false;
}

if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-update-traduction' ) ) {
	return false;
}

$update_wp = wp_get_update_data();

$datas = $update_wp['counts']['translations'];

if ( empty( $datas ) ) {
	$color      = 'green';
	$dashicon   = 'thumbs-up';
	$msg_1      = __( 'Congratulations, your traductions is up to date!', 'wps-bidouille' );
	$class_a    = 'wps-h-btn-disabled';
	$fa_icon    = 'shield-check';
	$is_dismiss = 'is-dismissible';
	$href = '';
	$msg_2      = __( 'No update available', 'wps-bidouille' );
} else {
	delete_site_transient( 'disable-notice-update-traduction' );
	$color      = 'red';
	$dashicon   = 'thumbs-down';
	$msg_1      = __( 'One or more of your translations requires an update', 'wps-bidouille' );
	$class_a    = 'wps-btn-delete';
	$href       = 'href="' . network_admin_url( 'update-core.php' ) . '"';
	$fa_icon    = 'info';
	$is_dismiss = '';
	$msg_2      = __( 'Update available', 'wps-bidouille' );
}

?>

<div class="wps-updates wpsnotice wps-border-<?php echo $color . ' ' . $is_dismiss; ?>"
     data-dismissible="disable-notice-update-traduction-forever">
    <div class="wps-up-left">
        <i class="fas fa-<?php echo $dashicon; ?> wps-icon-<?php echo $color; ?>" data-fa-transform="grow-8"></i>
        <?php echo $msg_1; ?>
    </div>
    <div class="wps-up-right">
        <a class="<?php echo $class_a; ?>" <?php echo $href; ?>><i class="fal fa-<?php echo $fa_icon; ?>" data-fa-transform="grow-6"></i>
	    <?php echo $msg_2; ?></a>
    </div>
</div>