<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
} ?>

<div id="plugin-filter" class="wrap wps-page-exclude-cache">
	<?php
	include( WPS_BIDOUILLE_DIR . 'blocks/title.php' );
	include( WPS_BIDOUILLE_DIR . 'blocks/pub.php' );
	include( WPS_BIDOUILLE_DIR . 'blocks/pub_wpboutik.php' );
	include( WPS_BIDOUILLE_DIR . 'blocks/menu.php' );

	$cpts = \WPS\WPS_Bidouille\RemoveFromCache::_get_cpts(); ?>

    <h1><?php _e( 'Réglages de la purge du cache serveur', 'wps-bidouille' ); ?></h1>

    <form method="post" action="options.php">
		<?php settings_fields( 'wps-bidouille-deactivate-purge-auto' );
		$option_purge = get_option( 'wps_bidouille_deactivate_purge' ); ?>

        <div class="wps-bidouille-info">
            <p><?php _e( 'Si vous constatez des lenteurs dans votre administration lorsque vous sauvegardez des modifications, WPServeur vous suggère de désactiver la purge automatique du cache serveur NGINX.', 'wps-bidouille' ); ?></p>
            <p><?php _e( 'Vous devrez purger manuellement (via le bouton vert "Purge du cache") à chaque mise à jour de contenus.', 'wps-bidouille' ); ?></p>
        </div>
        <input type="checkbox" name="wps_bidouille_deactivate_purge"
               value="1"<?php checked( 1 == $option_purge ); ?> /> <?php _e( 'Désactiver la purge automatique du cache serveur NGINX', 'wps-bidouille' ); ?></td>
		<?php submit_button(); ?>
    </form>

    <h1><?php _e( 'Exclude items from the cache server', 'wps-bidouille' ); ?></h1>

    <div class="wps-bidouille-info">
        <p><?php _e( 'This tool allows you to exclude elements of the NGINX cache (server side). This tool will not replace the settings of the third-party cache plugin such as W3Total Cache or WP-Rocket.', 'wps-bidouille' ); ?></p>
    </div>
    <div class="wps_wrap">
        <div class="wps-container-1">
            <nav class="nav-tab-wrapper">
                <div id="nav-add-simple" class="current"><?php _e( 'Add simple', 'wps-bidouille' ); ?></div>
                <div id="nav-add-mass" class="not-current"><?php _e( 'Add mass', 'wps-bidouille' ); ?></div>
                <div id="nav-add-archive" class="not-current"><?php _e( 'Add archive', 'wps-bidouille' ); ?></div>
            </nav>
            <div id="add-simple" class="wps-view">
                <form action="options.php" method="post" id="exclude_posts_cache">
					<?php
					settings_fields( 'wps-bidouille-remove-cache-settings' );
					global $wpdb;
					$list_cpt = array_keys( $cpts );
					$numItems = count( $list_cpt );
					$i        = 0;
					$cpt_in   = '(';
					foreach ( $list_cpt as $cpt ) {
						$cpt_in .= "'" . $cpt . "'";
						if ( ++ $i !== $numItems ) {
							$cpt_in .= ",";
						}
					}
					$cpt_in .= ')';
					$posts  = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type IN {$cpt_in} AND post_status = 'publish' ORDER BY post_date DESC;" ); ?>
                    <p>
                    <p><?php _e( 'Start typing the title, select the items and click on the "Add to list" button', 'wps-bidouille' ); ?></p>
                    <select id="select2_wps_posts" name="select2_wps_posts[]" multiple="multiple"
                            data-nonce="<?php echo wp_create_nonce( 'select-posts' ) ?>"
                            style="width:99%;max-width:25em;">
						<?php
						if ( $posts ) {
							foreach ( $posts as $post ) {
								$title     = $post->post_title;
								$post_type = get_post_type( $post->ID );
								// if the post title is too long, truncate it and add "..." at the end
								$title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
								echo '<option value="' . esc_attr( $post->ID ) . '">' . $title . ' ( ' . $post_type . ' )</option>';
							}
						} ?>
                    </select>
                    </p>
					<?php \WPS\WPS_Bidouille\Helpers::wps_submit_button( __( 'Add to list', 'wps-bidouille' ), 'btn-wps btn-wps-add', 'submit', false ); ?>
                </form>
            </div>

            <div id="add-mass" class="wps-hide">
                <form action="options.php" method="post" id="exclude_posts_cache_all">
					<?php settings_fields( 'wps-bidouille-remove-cache-settings-all' ); ?>
                    <div class="wps-function-add-mass">
                        <p><?php _e( 'Check one or more item type, then click on the "Add to list" button', 'wps-bidouille' ); ?></p>
						<?php
						if ( $cpts ) :
							foreach ( $cpts as $k => $cpt ) : ?>
                                <span class="add">
                                    <input type="checkbox" id="wps_cpt_remove_from_cache<?php echo $k; ?>"
                                           name="wps_cpt_remove_from_cache[]"
                                           value="<?php echo esc_attr( $k ); ?>"/>
                                    <label for="wps_cpt_remove_from_cache<?php echo $k; ?>"><?php echo esc_html( $cpt->label ); ?></label>
                                 </span>
							<?php
							endforeach;
						endif; ?>
                    </div>
                    <div class="clearfix"></div>
					<?php \WPS\WPS_Bidouille\Helpers::wps_submit_button( __( 'Add to list', 'wps-bidouille' ), 'btn-wps btn-wps-add', 'submit', false ); ?>
                </form>
            </div>

            <div id="add-archive" class="wps-hide">
                <form action="options.php" method="post" id="exclude_posts_cache_archive">
			        <?php settings_fields( 'wps-bidouille-remove-cache-settings-archive' ); ?>
                    <div class="wps-function-add-mass">
                        <p><?php _e( 'Check one or more item type, then click on the "Add to list" button', 'wps-bidouille' ); ?></p>
				        <?php
				        if ( $cpts ) :
					        foreach ( $cpts as $k => $cpt ) :
						        $archive_link = get_post_type_archive_link( $k );
						        if ( ! $archive_link ) {
							        continue;
						        } ?>
                                <span class="add">
                                    <input type="checkbox" id="wps_archive_cpt_remove_from_cache<?php echo $k; ?>"
                                           name="wps_archive_cpt_remove_from_cache[]"
                                           value="<?php echo $archive_link; ?>"/>
                                    <label for="wps_archive_cpt_remove_from_cache<?php echo $k; ?>"><?php echo esc_html( $cpt->label . ' (' . $archive_link . ')' ); ?></label>
                                 </span>
					        <?php
					        endforeach;
				        endif; ?>
                    </div>
                    <div class="clearfix"></div>
			        <?php \WPS\WPS_Bidouille\Helpers::wps_submit_button( __( 'Add to list', 'wps-bidouille' ), 'btn-wps btn-wps-add', 'submit', false ); ?>
                </form>
            </div>
        </div>
        <div class="wps-container-2">
			<?php include( WPS_BIDOUILLE_DIR . 'blocks/check_cache.php' ); ?>
        </div>
        <div class="clearfix"></div>
		<?php
		$exampleListTable = new \WPS\WPS_Bidouille\WPS_Uncached_List_Table();
		$exampleListTable->prepare_items(); ?>
        <h3><?php _e( 'List of elements excluded from the cache server', 'wps-bidouille' ); ?></h3>
        <a href="<?php echo add_query_arg( 'action', 'delete-exclud-cache-all', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-remove-from-cache' ), 'exclude_cache', 'wps-nonce' ) ); ?>"
           class="btn-wps btn-wps-exclude-cache"><?php _e( 'Erase all', 'wps-bidouille' ); ?></a>
		<?php $exampleListTable->display(); ?>
        <a href="<?php echo add_query_arg( 'action', 'delete-exclud-cache-all', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-remove-from-cache' ), 'exclude_cache', 'wps-nonce' ) ); ?>"
           class="btn-wps btn-wps-exclude-cache"><?php _e( 'Erase all', 'wps-bidouille' ); ?></a>

    </div>
</div>