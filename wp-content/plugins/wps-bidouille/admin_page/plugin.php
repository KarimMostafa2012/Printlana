<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
} ?>

<div id="plugin-filter" class="wrap">
    <?php
    include( WPS_BIDOUILLE_DIR . 'blocks/title.php' );
    include( WPS_BIDOUILLE_DIR . 'blocks/pub.php' );
    include( WPS_BIDOUILLE_DIR . 'blocks/pub_wpboutik.php' );
    include( WPS_BIDOUILLE_DIR . 'blocks/menu.php' );
	include( WPS_BIDOUILLE_DIR . 'blocks/notifications.php' ); ?>

	<div id="wps-container-1" class="wps-container">
		<?php
		include( WPS_BIDOUILLE_DIR . 'blocks/server_information.php' );
		include( WPS_BIDOUILLE_DIR . 'blocks/report_system.php' );
		/*if ( ! \WPS\WPS_Bidouille\Helpers::is_user_white_label() ) {
			include( WPS_BIDOUILLE_DIR . 'blocks/logs.php' );
		}*/ ?>
	</div>
	<div id="wps-container-2" class="wps-container">
		<?php
		include( WPS_BIDOUILLE_DIR . 'blocks/user_info.php' );
		include( WPS_BIDOUILLE_DIR . 'blocks/optimisations.php' );

		/*if ( \WPS\WPS_Bidouille\Helpers::is_user_white_label() ) {
			include( WPS_BIDOUILLE_DIR . 'blocks/logs.php' );
		}*/ ?>
	</div>
	<div id="wps-container-3" class="wps-container">
		<?php include( WPS_BIDOUILLE_DIR . 'blocks/mysql.php' ); ?>
	</div>
</div>