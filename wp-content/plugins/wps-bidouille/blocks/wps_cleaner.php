<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$plugin = 'wps-cleaner/wps-cleaner.php';

if ( is_plugin_active( $plugin ) ) {
	return false;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return false;
}

$is_plugin_installed = \WPS\WPS_Bidouille\Helpers::is_plugin_installed( $plugin );
if ( ! $is_plugin_installed ) {
	$text   = __( 'Clean up your WordPress site by installing the WPS Cleaner', 'wps-bidouille' );
	$classes = 'install-now';
	$url    = wp_nonce_url( add_query_arg(
		array(
			'action' => 'install-plugin',
			'plugin' => 'wps-cleaner',
		),
		admin_url( 'update.php' )
	), 'install-plugin_wps-cleaner' );
	$button = __( 'Install plugin', 'wps-bidouille' );
} else {
	$text = __( 'You have the WPS Cleaner plugin, activate it now', 'wps-bidouille' );
	$classes = 'activate-now';
	$url  = wp_nonce_url( add_query_arg(
		array(
			'action'        => 'activate',
			'plugin'        => $plugin,
			'plugin_status' => 'all',
			'paged'         => 1
		),
		admin_url( 'plugins.php' )
	), 'activate-plugin_' . $plugin );

	$button = __( 'Activate plugin', 'wps-bidouille' );
}

if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-wps-cleaner' ) ) {
	return false;
} ?>

<div class="wps-updates wpsnotice notice-info plugin-card plugin-card-wps-cleaner is-dismissible" data-dismissible="disable-notice-wps-cleaner-forever">
    <div class="wps-up-left"><i class="fas fa-shield-alt wps-icon-bleu" data-fa-transform="grow-8 flip-h"></i>
        <span class="wps-info-info"><?php echo esc_html( $text ); ?></span>
    </div>
    <div class="wps-up-right">
        <a data-slug="wps-cleaner" href="<?php echo esc_url( $url ); ?>" class="wps-btn-info <?php echo esc_attr( $classes ); ?>">
            <i class="fal fa-shield-alt" data-fa-transform="grow-4"></i>
			<?php echo esc_html( $button ); ?>
        </a>
    </div>
</div>