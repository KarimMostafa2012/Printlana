<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$response = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_api_result();

if ( empty( $response ) ) : ?>
    <div class="wps-notice-error-suggest">
	<p><i class="fas fa-frown"></i> <?php _e( 'Oops, we can not recover the premium extensions, force the refresh of the page', 'wps-bidouille' ); ?></p>
	<a href="#" class="wps-delete-transient-premiums button btn-wps wps-reinit" data-nonce="<?php echo wp_create_nonce( 'delete-transient-premiums' ); ?>"><?php _e( 'Refresh page', 'wps-bidouille' ); ?></a>
	</div>
    <?php
endif;

$plugins_premiums = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_json_array( $response );

if ( empty( $plugins_premiums ) ) {
	return false;
} ?>

<div class="wp-list-table widefat wps-bidouille_page_wps-bidouille-suggest-plugins-premium">
    <div id="the-list">
		<?php
		foreach ( $plugins_premiums as $plugin ) {

			$title        = $plugin['name'];
			$description  = strip_tags( $plugin['short_description'] );
			$version      = $plugin['version'];
			$last_updated = $plugin['last_updated'];
			$more_details = $plugin['more_details'];
			$name         = strip_tags( $title . ' ' . $version );
			$icon         = $plugin['icon'];
			if ( empty( $icon ) ) {
				$icon = WPS_BIDOUILLE_URL . 'assets/img/icone-encours.png';
			}

			$author = $plugin['author'];
			if ( ! empty( $author ) ) {
				$author = ' <cite>' . sprintf( __( 'By %s' ), $author ) . '</cite>';
			}

			$action_links = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_action_links_plugin_premium( $plugin ); ?>
            <div class="plugin-card plugin-card-<?php echo sanitize_html_class( $plugin['slug'] ); ?>">
                <div class="plugin-card-top">
                    <div class="name column-name">
                        <h3>
							<?php echo $title; ?>
                            <img src="<?php echo $icon; ?>" class="plugin-icon">
                        </h3>
                    </div>
                    <div class="action-links">
						<?php
						if ( $action_links ) {
							echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
						} ?>
                    </div>
                    <div class="desc column-description">
                        <p><?php echo $description; ?></p>
                        <p class="authors"><?php echo $author; ?></p>
                        <p><?php _e( 'Version:' ); ?><?php echo $version; ?>&nbsp;-&nbsp;<?php echo $last_updated; ?></p>
                    </div>
                </div>
            </div>
			<?php
		} ?>
    </div>
</div>