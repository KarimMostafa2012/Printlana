<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$class = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-notifications' ); ?>
<div id="wps-dashboard-notifications">
	<h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>"><span><?php _e( 'Notification center', 'wps-bidouille' ); ?></span><span class="wps-counter-notifications"></span></h2>
	<div class="main <?php echo esc_attr( $class['div'] ); ?>">
		<?php
		echo \WPS\WPS_Bidouille\Helpers::wps_check_temporary_url();

		echo \WPS\WPS_Bidouille\Helpers::wps_update( 'wordpress' );
		echo \WPS\WPS_Bidouille\Helpers::wps_update( 'plugins' );
		echo \WPS\WPS_Bidouille\Helpers::wps_update( 'themes' );
		if ( current_user_can( 'delete_plugins' ) ) {
			\WPS\WPS_Bidouille\Helpers::wps_numbers_plugins();
		}
		if ( current_user_can( 'delete_themes' ) ) {
			\WPS\WPS_Bidouille\Helpers::wps_number_themes();
		}

		include( WPS_BIDOUILLE_DIR . 'blocks/update_traduction.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/check_old_plugins.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/db_prefix.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/ssl.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/wps_hide_login.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/wps_limit_login.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/wps_cleaner.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/wpboutik.php' );

		include( WPS_BIDOUILLE_DIR . 'blocks/settings_autoupdate.php' ); ?>
	</div>
</div>
