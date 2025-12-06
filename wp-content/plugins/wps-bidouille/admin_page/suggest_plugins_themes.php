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

    <div class="wps-text-suggest">
        A l'instar du Quick-Install de votre console, retrouvez ci-dessous, une sélection de thèmes et extensions recommandés par l'équipe WPServeur.
    </div>

    <div class="wps-nav-suggest">
        <a class="<?php echo ( ( isset( $_GET['act'] ) && ( 'plugins' === $_GET['act'] ) ) || ! isset( $_GET['act'] ) ) ? 'current' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=wps-bidouille-suggest-plugins&tab=wps_bidouille&act=plugins' ); ?>"><i class="fas fa-plug" data-fa-transform="rotate-45"></i> <?php _e( 'Plugins' ); ?></a>
        <a class="<?php echo ( ( isset( $_GET['act'] ) && ( 'themes' === $_GET['act'] ) ) ) ? 'current' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=wps-bidouille-suggest-plugins&tab=wps_bidouille&act=themes' ); ?>"><i class="fas fa-pencil-alt"></i> <?php _e( 'Themes' ); ?></a>
        <a class="premium <?php echo ( isset( $_GET['act'] ) && 'plugins_premium' === $_GET['act'] ) ? 'current' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=wps-bidouille-suggest-plugins&tab=wps_bidouille&act=plugins_premium' ); ?>"><i class="fas fa-star"></i> <?php _e( 'Plugins' ); ?> premium</a>
        <a class="premium <?php echo ( isset( $_GET['act'] ) && 'themes_premium' === $_GET['act'] ) ? 'current' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=wps-bidouille-suggest-plugins&tab=wps_bidouille&act=themes_premium' ); ?>"><i class="fas fa-star"></i> <?php _e( 'Themes' ); ?> premium</a>
    </div>

	<?php
	if ( isset( $_GET['act'] ) && 'plugins_premium' === $_GET['act'] ) {
		include( WPS_BIDOUILLE_DIR . 'blocks/suggest/plugin_premiums.php' );
	} elseif ( isset( $_GET['act'] ) && 'themes_premium' === $_GET['act'] ) {
		include( WPS_BIDOUILLE_DIR . 'blocks/suggest/theme_premiums.php' );
	} elseif ( isset( $_GET['act'] ) && 'themes' === $_GET['act'] ) {
		include( WPS_BIDOUILLE_DIR . 'blocks/suggest/themes.php' );
	} else {
		$wp_list_table = _get_list_table( 'WP_Plugin_Install_List_Table' );
		$wp_list_table->prepare_items();
		$wp_list_table->display();
	} ?>
</div>