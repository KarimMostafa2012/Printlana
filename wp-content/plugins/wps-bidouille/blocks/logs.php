<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$pf = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
if ( empty( $pf ) ) {
    return false;
}

$class = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-logs' ); ?>

<div id="wps-dashboard-logs">
    <h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>"><span><?php _e( 'Download Logs', 'wps-bidouille' ); ?></span>
    </h2>
    <div class="main <?php echo esc_attr( $class['div'] ); ?>">
        <div class="desc-report">
            <i class="fas fa-info-square"></i> <?php _e( 'Description logs', 'wps-bidouille' ); ?>
        </div>
        <ul>
            <li>
                <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille&action=downloads_error_log' ); ?>" class="btn btn-wps-report">
                    <span class="icon-btn"><i class="fal fa-download" data-fa-transform="grow-6"></i></span>
                    <span class="txt-btn"><?php _e( 'Download error log', 'wps-bidouille' ); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille&action=downloads_access_log' ); ?>" class="btn btn-wps-report">
                    <span class="icon-btn"><i class="fal fa-download" data-fa-transform="grow-6"></i></span>
                    <span class="txt-btn"><?php _e( 'Download access log', 'wps-bidouille' ); ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>