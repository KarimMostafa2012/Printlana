<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$class = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-tools' ); ?>

<div id="wps-dashboard-tools">
    <h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>">
        <span><?php _e( 'Optimizations', 'wps-bidouille' ); ?></span>
    </h2>
    <div class="main <?php echo esc_attr( $class['div'] ); ?>">
        <form action="options.php" method="post" id="wps_settings_tools">
			<?php
            settings_fields( 'wps-settings-tools' );
            $options_tools = get_option( 'wps_options_tools' );

            $pf = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
			if ( empty( $pf ) ) :
				$checked_hide_connection = '';
				if ( is_array( $options_tools ) && isset( $options_tools['error_connexion'] ) ) {
					$checked_hide_connection = checked( esc_attr( $options_tools['error_connexion'] ), 1, false );
				} ?>
                <div class="wps-row">
                    <input type="checkbox" id="error_connexion" name="wps_options_tools[error_connexion]"
                           value="1" <?php echo $checked_hide_connection; ?>>
                    <label for="error_connexion"><?php _e( 'Hide connection error', 'wps-bidouille' ); ?></label>
					<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Hide connection error information in', 'wps-bidouille' ) . '<code>wp-login.php</code>' ); ?>
                </div>
			    <?php
            endif;

			if ( empty( $pf ) ) :
                $checked_tools_sanitize_file_name = '';
                if ( is_array( $options_tools ) && isset( $options_tools['tools_sanitize_file_name'] ) ) {
                    $checked_tools_sanitize_file_name = checked( esc_attr( $options_tools['tools_sanitize_file_name'] ), 1, false );
                } ?>
                <div class="wps-row">
                    <input type="checkbox" id="tools_sanitize_file_name" name="wps_options_tools[tools_sanitize_file_name]"
                           value="1" <?php echo $checked_tools_sanitize_file_name; ?>>
                    <label for="tools_sanitize_file_name"><?php _e( 'Remove special characters from uploaded media', 'wps-bidouille' ); ?></label>
                    <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Automatically remove special characters when uploading media', 'wps-bidouille' ) ); ?>
                </div>
                <?php
	        endif;

            if ( empty( $pf ) ) :
				$checked_wp_version = '';
				if ( is_array( $options_tools ) && isset( $options_tools['wp_version'] ) ) {
					$checked_wp_version = checked( esc_attr( $options_tools['wp_version'] ), 1, false );
				} ?>
                <div class="wps-row">
                    <input type="checkbox" id="wp_version" name="wps_options_tools[wp_version]"
                           value="1" <?php echo $checked_wp_version; ?>>
                    <label for="wp_version"><?php _e( 'Remove WordPress version', 'wps-bidouille' ); ?></label>
					<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Remove the generator tag that is generated', 'wps-bidouille' ) ); ?>
                </div>
			    <?php
            endif;

            if ( empty( $pf ) ) :
	            $checked_link_manifest = '';
	            if ( is_array( $options_tools ) && isset( $options_tools['link_manifest'] ) ) {
		            $checked_link_manifest = checked( esc_attr( $options_tools['link_manifest'] ), 1, false );
	            } ?>
                <div class="wps-row">
                    <input type="checkbox" id="link_manifest" name="wps_options_tools[link_manifest]"
                           value="1" <?php echo $checked_link_manifest; ?>>
                    <label for="link_manifest"><?php _e( 'Remove Windows Live Writer Manifest', 'wps-bidouille' ); ?></label>
					<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Remove Windows Live Writer Manifest Link.', 'wps-bidouille' ) ); ?>
                </div>
			    <?php
            endif;

			$checked_deactivate_revisions = '';
			if ( is_array( $options_tools ) && isset( $options_tools['deactivate_revisions'] ) ) {
				$checked_deactivate_revisions = checked( esc_attr( $options_tools['deactivate_revisions'] ), 1, false );
			} ?>
            <div class="wps-row">
                <input type="checkbox" id="deactivate_revisions" name="wps_options_tools[deactivate_revisions]"
                       value="1" <?php echo $checked_deactivate_revisions; ?>>
                <label for="deactivate_revisions"><?php _e( 'Restrict the revision number', 'wps-bidouille' ); ?></label>
		        <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Restrict the revision number to 5 to avoid cluttering the database', 'wps-bidouille' ) ); ?>
            </div>

            <?php
            $checked_delete_h1_editor = '';
            if ( is_array( $options_tools ) && isset( $options_tools['delete_h1_editor'] ) ) {
	            $checked_delete_h1_editor = checked( esc_attr( $options_tools['delete_h1_editor'] ), 1, false );
            } ?>
            <div class="wps-row">
                <input type="checkbox" id="delete_h1_editor" name="wps_options_tools[delete_h1_editor]"
                       value="1" <?php echo $checked_delete_h1_editor; ?>>
                <label for="delete_h1_editor"><?php _e( 'Remove H1 in Tiny MCE', 'wps-bidouille' ); ?></label>
				<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( '<em>H1</em> ' . __( 'should never be used in the editor', 'wps-bidouille' ) ); ?>
            </div>

			<?php
            if ( empty( $pf ) ) :
				$checked_disable_emoji = '';
				if ( is_array( $options_tools ) && isset( $options_tools['disable_emoji'] ) ) {
					$checked_disable_emoji = checked( esc_attr( $options_tools['disable_emoji'] ), 1, false );
				} ?>
                <div class="wps-row">
                    <input type="checkbox" id="disable_emoji" name="wps_options_tools[disable_emoji]"
                           value="1" <?php echo $checked_disable_emoji; ?>>
                    <label for="disable_emoji"><?php _e( 'Disabled emoji WP', 'wps-bidouille' ); ?></label>
					<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Reduces resources number loaded on front page. Utilise les emojis du navigateur de vos visiteurs au lieu de charger celles de WordPress.org', 'wps-bidouille' ) ); ?>
                </div>
			    <?php
            endif;

            if ( ! is_plugin_active('contact-form-7/wp-contact-form-7.php' ) ) :
                $checked_deactivate_rest_api = '';
                if ( is_array( $options_tools ) && isset( $options_tools['deactivate_rest_api'] ) ) {
                    $checked_deactivate_rest_api = checked( esc_attr( $options_tools['deactivate_rest_api'] ), 1, false );
                } ?>
                <div class="wps-row">
                    <input type="checkbox" id="deactivate_rest_api" name="wps_options_tools[deactivate_rest_api]"
                           value="1" <?php echo $checked_deactivate_rest_api; ?>>
                    <label for="deactivate_rest_api"><?php _e( 'Deactivate REST-API', 'wps-bidouille' ); ?></label>
                    <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'This link become not accessible: ', 'wps-bidouille' ) . '<code>' . home_url( 'wp-json' ) . '</code>' ); ?>
                </div>
                <?php
            endif;

	        $checked_add_medium_large = '';
	        if ( is_array( $options_tools ) && isset( $options_tools['add_medium_large'] ) ) {
		        $checked_add_medium_large = checked( esc_attr( $options_tools['add_medium_large'] ), 1, false );
	        } ?>
            <div class="wps-row">
                <input type="checkbox" id="add_medium_large" name="wps_options_tools[add_medium_large]"
                       value="1" <?php echo $checked_add_medium_large; ?>>
                <label for="add_medium_large"><?php _e( 'Add format <em>Medium Large</em>', 'wps-bidouille' ); ?></label>
				<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Displays the default <em>Medium Wide</em> format (768 pixels) when you choose a media in an article.', 'wps-bidouille' ) ); ?>
            </div>

	        <?php
	        $checked_deactivate_author_page_and_link = '';
	        if ( is_array( $options_tools ) && isset( $options_tools['deactivate_author_page_and_link'] ) ) {
		        $checked_deactivate_author_page_and_link = checked( esc_attr( $options_tools['deactivate_author_page_and_link'] ), 1, false );
	        } ?>
            <div class="wps-row">
                <input type="checkbox" id="deactivate_author_page_and_link"
                       name="wps_options_tools[deactivate_author_page_and_link]"
                       value="1" <?php echo $checked_deactivate_author_page_and_link; ?>>
                <label for="deactivate_author_page_and_link"><?php _e( 'Deactivate author page and author link', 'wps-bidouille' ); ?></label>
				<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'The links and pages listing the different author login.', 'wps-bidouille' ) ); ?>
            </div>

	        <?php
	        $checked_feed_link = '';
	        if ( is_array( $options_tools ) && isset( $options_tools['feed_link'] ) ) {
		        $checked_feed_link = checked( esc_attr( $options_tools['feed_link'] ), 1, false );
	        } ?>
            <div class="wps-row">
                <input type="checkbox" id="feed_link" name="wps_options_tools[feed_link]"
                       value="1" <?php echo $checked_feed_link; ?>>
                <label for="feed_link"><?php _e( 'Remove RSS feed', 'wps-bidouille' ); ?></label>
				<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Remove RSS feed', 'wps-bidouille' ) ); ?>
            </div>

	        <?php
	        $checked_comments_feed = '';
	        if ( is_array( $options_tools ) && isset( $options_tools['comments_feed'] ) ) {
		        $checked_comments_feed = checked( esc_attr( $options_tools['comments_feed'] ), 1, false );
	        } ?>
            <div class="wps-row">
                <input type="checkbox" id="comments_feed" name="wps_options_tools[comments_feed]"
                       value="1" <?php echo $checked_comments_feed; ?>>
                <label for="comments_feed"><?php _e( 'Remove Comment RSS feed', 'wps-bidouille' ); ?></label>
				<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Remove Comment RSS feed', 'wps-bidouille' ) ); ?>
            </div>

	        <?php
	        global $wp_version;
	        if ( version_compare( $wp_version, '5.5', '<' ) ) :
                $checked_last_jquery = '';
                if ( is_array( $options_tools ) && isset( $options_tools['last_jquery'] ) ) {
                    $checked_last_jquery = checked( esc_attr( $options_tools['last_jquery'] ), 1, false );
                } ?>
                <div class="wps-row">
                    <input type="checkbox" id="last_jquery" name="wps_options_tools[last_jquery]"
                           value="1" <?php echo $checked_last_jquery; ?>>
                    <label for="last_jquery"><?php _e( 'Load the latest version of jQuery', 'wps-bidouille' ); ?></label>
                    <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Replaces the version of jQuery proposed by WordPress (1.12.4) which contains a security vulnerability, by version 3.3.1 which is patched on this flaw. <span class="wps-warning">Be careful, however, this feature may cause malfunction with some extensions</span>', 'wps-bidouille' ) ); ?>
                </div>
	        <?php
            endif;
	        $is_enabledsitemap = (bool) get_option( 'blog_public' );
	        if ( version_compare( $wp_version, '5.5', '>' ) && ! is_plugin_active('wordpress-seo/wp-seo.php' ) && ! is_plugin_active('seo-by-rank-math/rank-math.php' ) && $is_enabledsitemap ) :
		        $checked_users_remove_sitemap = '';
		        if ( is_array( $options_tools ) && isset( $options_tools['checked_users_remove_sitemap'] ) ) {
			        $checked_users_remove_sitemap = checked( esc_attr( $options_tools['checked_users_remove_sitemap'] ), 1, false );
		        } ?>
                <div class="wps-row">
                    <input type="checkbox" id="checked_users_remove_sitemap" name="wps_options_tools[checked_users_remove_sitemap]"
                           value="1" <?php echo $checked_users_remove_sitemap; ?>>
                    <label for="checked_users_remove_sitemap"><?php _e( 'Remove users on WordPress sitemap', 'wps-bidouille' ); ?></label>
			        <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Removed users on WordPress sitemap', 'wps-bidouille' ) ); ?>
                </div>
                <?php
            endif;

	        if ( is_plugin_active('woocommerce/woocommerce.php' ) ) :
                $checked_fast_woocommerce = '';
                if ( is_array( $options_tools ) && isset( $options_tools['fast_woocommerce'] ) ) {
                    $checked_fast_woocommerce = checked( esc_attr( $options_tools['fast_woocommerce'] ), 1, false );
                } ?>
                <div class="wps-row">
                    <input type="checkbox" id="fast_woocommerce" name="wps_options_tools[fast_woocommerce]"
                           value="1" <?php echo $checked_fast_woocommerce; ?>>
                    <label for="fast_woocommerce"><?php _e( 'WooCommerce Speed Drain Repair', 'wps-bidouille' ); ?></label>
                    <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Stops loading the extra items you do not need inside WooCommerce and speeds up WordPress core admin-ajax.php file.', 'wps-bidouille' ) ); ?>
                </div>
	            <?php
            endif;

	        if ( is_plugin_active('contact-form-7/wp-contact-form-7.php' ) ) :
                $checked_fast_contactform7 = '';
                if ( is_array( $options_tools ) && isset( $options_tools['fast_contactform7'] ) ) {
                    $checked_fast_contactform7 = checked( esc_attr( $options_tools['fast_contactform7'] ), 1, false );
                } ?>
                <div class="wps-row">
                    <input type="checkbox" id="fast_contactform7" name="wps_options_tools[fast_contactform7]"
                           value="1" <?php echo $checked_fast_contactform7; ?>>
                    <label for="fast_contactform7"><?php _e( 'Contact Form 7 Speed', 'wps-bidouille' ); ?></label>
                    <?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Stops loading the extra items you do not need inside Contact Form 7', 'wps-bidouille' ) ); ?>
                </div>
                <?php
            endif;

	        $checked_disable_rest_api_users = '';
	        if ( is_array( $options_tools ) && isset( $options_tools['disable_rest_api_users'] ) ) {
		        $checked_disable_rest_api_users = checked( esc_attr( $options_tools['disable_rest_api_users'] ), 1, false );
	        } ?>
            <div class="wps-row last">
                <input type="checkbox" id="disable_rest_api_users" name="wps_options_tools[disable_rest_api_users]"
                       value="1" <?php echo $checked_disable_rest_api_users; ?>>
                <label for="disable_rest_api_users"><?php _e( 'Disable REST API user endpoints', 'wps-bidouille' ); ?></label>
				<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Disable the user endpoint for non connected user (not display the list of wordpress users)', 'wps-bidouille' ) . ' <code>' . home_url( '/wp/v2/users' ) . '</code>' ); ?>
            </div>

            <div class="wps-action-btn">
                <button type="submit" name="submit" id="submit" class="button btn-wps btn-wps-register"><span class="icon-btn"><i class="fal fa-save" data-fa-transform="grow-6"></i></span><span class="txt-btn"><?php _e('Save'); ?></span></button>
            </div>
        </form>
    </div>
</div>