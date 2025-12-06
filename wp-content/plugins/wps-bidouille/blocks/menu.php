<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
} ?>

<div class="wps-wrap-menu">
    <nav class="wps-menu">
        <div class="wps-nav-menu <?php echo ( $_GET[ 'page' ] === 'wps-bidouille' ) ? 'current' : ''; ?>">
            <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille' ); ?>">
				<?php _e( 'WPS Bidouille', 'wps-bidouille' ); ?>
            </a>
        </div>
        <?php
        $pf = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
        $wps_check_temporary_url = \WPS\WPS_Bidouille\Helpers::wps_check_temporary_url();
        if ( ! empty( $pf ) && empty( $wps_check_temporary_url ) ) : ?>
            <div class="wps-nav-menu <?php echo ( $_GET[ 'page' ] === 'wps-bidouille-remove-from-cache' ) ? 'current' : ''; ?>">
                <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille-remove-from-cache' ); ?>">
                    <?php _e( 'Exclude from cache', 'wps-bidouille' ); ?> NGINX
                </a>
            </div>
            <?php
        endif;
        $is_user_white_label = \WPS\WPS_Bidouille\Helpers::is_user_white_label();
        if ( ! $is_user_white_label && ! empty( $pf ) ) : ?>
            <div class="wps-nav-menu <?php echo ( $_GET[ 'page' ] === 'wps-bidouille-white-label' ) ? 'current' : ''; ?>">
                <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille-white-label' ); ?>">
                    <?php _e( 'White Label', 'wps-bidouille' ); ?>
                </a>
            </div>
        <?php endif; ?>
        <div class="wps-nav-menu <?php echo ( $_GET[ 'page' ] === 'wps-bidouille-tools' ) ? 'current' : ''; ?>">
            <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille-tools' ); ?>">
				<?php _e( 'Complementary tools', 'wps-bidouille' ); ?>
            </a>
        </div>
	    <?php if ( ! empty( $pf ) && current_user_can( 'manage_options' ) ) : ?>
            <div class="wps-nav-menu <?php echo ( $_GET[ 'page' ] === 'wps-bidouille-suggest-plugins' ) ? 'current' : ''; ?>">
                <a href="<?php echo admin_url( 'admin.php?page=wps-bidouille-suggest-plugins&tab=wps_bidouille' ); ?>">
				    <?php _e( 'Recommended themes and extensions', 'wps-bidouille' ); ?>
                </a>
            </div>
	    <?php endif; ?>
    </nav>
</div>