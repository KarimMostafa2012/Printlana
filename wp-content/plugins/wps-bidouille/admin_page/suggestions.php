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
    include( WPS_BIDOUILLE_DIR . 'blocks/menu.php' ); ?>
    <h1 class="wp-heading-inline"><?php _e( 'Our suggestions', 'wps-bidouille' ); ?></h1>
    <div>
        <table>
            <tbody>

            </tbody>
        </table>
    </div>
</div>