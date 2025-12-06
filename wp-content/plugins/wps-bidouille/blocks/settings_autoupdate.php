<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$pf = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
if ( ! current_user_can( 'manage_options' ) || empty( $pf ) ) {
    return false;
}

if ( class_exists('WP_Formation_Auto_Update_Config') ) {
	$show_warning = false;

	$all_official_plugins = WP_Formation_Auto_Update_Config::get_all_official_plugins();
	$all_themes           = WP_Formation_Auto_Update_Config::get_themes();
	$all_official_themes  = is_array( $all_themes ) && isset( $all_themes['officials'] ) ? $all_themes['officials'] : array();

	$autopdate_plugins = get_option( 'no_autoupdates_plugins' );
	$autopdate_plugins = is_array( $autopdate_plugins ) ? $autopdate_plugins : array();

	$autopdate_themes = get_option( 'autoupdates_themes' );
	$autopdate_themes = is_array( $autopdate_themes ) ? $autopdate_themes : array();

	// If we've more WP plugins/themes than in the configuration options, it means that we need an options update
	if ( ( count( $all_official_themes ) + count( $all_official_plugins ) ) > ( count( $autopdate_plugins ) + count( $autopdate_themes ) ) ) {
		$show_warning = true;
	}

	if ( false === $show_warning ) {
		return false;
	} else {
	    delete_site_transient('disable-notice-autoupdates' );
    }
}

if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-autoupdates' ) ) {
	return false;
} ?>

<div class="wps-updates wpsnotice notice-info is-dismissible" data-dismissible="disable-notice-autoupdates-forever">
    <div class="wps-up-left"><i class="fas fa-cogs wps-icon-bleu" data-fa-transform="grow-8 flip-h"></i>
		<?php _e( 'Remember to adjust your automatic update settings', 'wps-bidouille' ); ?>
    </div>
    <div class="wps-up-right">
        <a href="<?php echo admin_url( 'options-general.php?page=autoupdates' ); ?>" class="wps-btn-info">
            <i class="fal fa-cog" data-fa-transform="grow-4"></i> <?php _e( 'Update my settings', 'wps-bidouille' ); ?>
        </a>
    </div>
</div>