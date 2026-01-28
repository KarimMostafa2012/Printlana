<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( false === ( $response = get_transient( 'wps_query_themes' ) ) ) {
	$args     = array(
		'fields' => array(
			'last_updated'    => true,
			'icons'           => true,
			'active_installs' => true
		),
		'browse' => 'favorites',
		'user'   => 'WPServeur'
	);
	$response = themes_api( 'query_themes', $args );
	set_transient( 'wps_query_themes', $response, 24 * HOUR_IN_SECONDS );
}

if ( empty( $response ) ) {
	return false;
} ?>
<div class="wp-list-table widefat wps-bidouille_page_wps-bidouille-suggest-themes">
    <div id="the-list">
		<?php
		foreach ( $response->themes as $theme ) :

			$theme = (array) $theme;

			$title                 = $theme['name'];
			$description           = strip_tags( $theme['description'] );
			$version               = $theme['version'];
			$last_updated          = $theme['last_updated'];
			$theme['more_details'] = $theme['preview_url'];
			$name                  = strip_tags( $title . ' ' . $version );
			$icon                  = $theme['screenshot_url'];
			if ( empty( $icon ) ) {
				$icon = WPS_BIDOUILLE_URL . 'assets/img/icone-encours.png';
			}

			$author = $theme['author'];
			if ( ! empty( $author ) ) {
				$author = ' <cite>' . sprintf( __( 'By %s' ), $author['display_name'] ) . '</cite>';
			}

			$action_links = \WPS\WPS_Bidouille\Suggest_Plugins_Themes::get_action_links_theme( $theme ); ?>
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
                        <p><?php _e( 'Version:' ); ?><?php echo $version; ?>
                            &nbsp;-&nbsp;<?php echo $last_updated; ?></p>
                    </div>
                </div>
            </div>
		<?php
		endforeach; ?>
    </div>
</div>