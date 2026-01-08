<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( false === apply_filters( 'wps_display_report_system', true ) ) {
	return false;
}

$class               = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-report-system' );
$pf                  = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
$is_user_white_label = \WPS\WPS_Bidouille\Helpers::is_user_white_label(); ?>

<div id="wps-dashboard-report-system">
    <h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>">
        <span><?php _e( 'Report System', 'wps-bidouille' ); ?></span></h2>
    <div class="main <?php echo esc_attr( $class['div'] ); ?>">
        <div class="desc-report">
            <i class="fas fa-info-square"></i> <?php _e( 'The system report allows you to generate a usable report on the general status of your WordPress site.', 'wps-bidouille' ); ?>
			<?php if ( ! empty( $pf ) && ! $is_user_white_label ) : ?><?php _e( 'For example, this speeds up requests for WPServeur support because it provides essential information about your site.', 'wps-bidouille' ); ?>
                ( <?php _e( 'No private information is collected in this report.', 'wps-bidouille' ); ?> )
			<?php endif; ?>
        </div>
        <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille&action=downloads_system_report&nonce=' . wp_create_nonce( 'download-system-report' ) ); ?>"
           class="btn btn-wps-report">
            <span class="icon-btn"><i class="fal fa-download" data-fa-transform="grow-6"></i></span>
            <span class="txt-btn"><?php _e( 'Download the system report', 'wps-bidouille' ); ?></span>
        </a>
		<?php
		if ( ! empty( $pf ) && ! $is_user_white_label ) : ?>
            <a href="https://www.wpserveur.net/support-wp-serveur/" class="btn btn-wps-ticket" target="_blank">
                <span class="icon-btn"><i class="fal fa-life-ring" data-fa-transform="grow-6"></i></span>
                <span class="txt-btn"><?php _e( 'Open a support ticket', 'wps-bidouille' ); ?></span>
            </a>
		<?php
		endif; ?>
    </div>
</div>