<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$response = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_api_result( WPS_BIDOUILLE_API_CAT_THEMES );

if ( empty( $response ) ) : ?>
    <div class="wps-notice-error-suggest">
        <p><i class="fas fa-frown"></i> <?php _e( 'Oops, we can not recover the premium themes, force the refresh of the page', 'wps-bidouille' ); ?></p>
        <a href="#" class="wps-delete-transient-premiums button btn-wps wps-reinit" data-nonce="<?php echo wp_create_nonce( 'delete-transient-premiums' ); ?>"><?php _e( 'Refresh page', 'wps-bidouille' ); ?></a>
    </div>
    <?php
endif;

$themes_premiums = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_json_array( $response );

if ( empty( $themes_premiums ) ) {
	return false;
} ?>

<div class="wp-list-table widefat wps-bidouille_page_wps-bidouille-suggest-themes-premium">
	<div id="the-list">
		<?php
		foreach ( $themes_premiums as $theme ) {

			$title        = $theme['name'];
			$description  = strip_tags( $theme['description'] );
			$version      = $theme['version'];
			$last_updated = $theme['last_updated'];
			$more_details = $theme['more_details'];
			$name         = strip_tags( $title . ' ' . $version );
			$icon         = $theme['icon'];
			if ( empty( $icon ) ) {
			    $icon = WPS_BIDOUILLE_URL . 'assets/img/icone-encours.png';
            }

			$author = $theme['author'];
			if ( ! empty( $author ) ) {
				$author = ' <cite>' . sprintf( __( 'By %s' ), $author ) . '</cite>';
			}

			$action_links = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_action_links_theme_premium( $theme ); ?>
			<div class="plugin-card plugin-card-<?php echo sanitize_html_class( $theme['slug'] ); ?>">
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
						<p><?php echo wp_trim_words( $description, 10 ); ?></p>
						<p class="authors"><?php echo $author; ?></p>
                        <p><?php _e( 'Version:' ); ?><?php echo $version; ?>&nbsp;-&nbsp;<?php echo $last_updated; ?></p>
					</div>
				</div>
			</div>
			<?php
		} ?>
	</div>
</div>