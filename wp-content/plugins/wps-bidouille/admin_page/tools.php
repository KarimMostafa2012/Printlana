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
    <h1 class="wp-heading-inline"><?php _e( 'Complementary tools', 'wps-bidouille' ); ?></h1>
    <div class="wps-list-tools">
        <table>
            <tbody>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Log out all sessions', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php _e( 'This tool will disconnect all users except the active user (you).', 'wps-bidouille' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <a href="<?php echo add_query_arg( 'action', 'clear_all_sessions', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-tools' ), 'clear_sessions', 'wps-nonce' ) ); ?>"
                           class="button btn-wps wps-disconnect"><?php _e( 'Disconnect everyone', 'wps-bidouille' ); ?></a>
                    </td>
                </tr>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Expired temporary data', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php echo sprintf( __( 'This tool will clean up ALL expired <a href="%s" target="_blank">transients</a> from WordPress.', 'wps-bidouille'), 'https://wpformation.com/transients-wordpress/' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <a href="<?php echo add_query_arg( 'action', 'clear_expired_transient', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-tools' ), 'clear_expired_transient', 'wps-nonce' ) ); ?>"
                           class="button btn-wps clear_sessions"><?php _e( 'Clear temporary data', 'wps-bidouille' ); ?> (<?php echo \WPS\WPS_Bidouille\Helpers::count_cleanup_items('expired_transients'); ?>)</a>
                    </td>
                </tr>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Cleaning up spam comments', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php _e( 'This tool will remove spam comments.', 'wps-bidouille' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <a href="<?php echo add_query_arg( 'action', 'spam_comments', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-tools' ), 'spam_comments', 'wps-nonce' ) ); ?>"
                           class="button btn-wps clear_sessions"><?php _e( 'Delete spam comments', 'wps-bidouille' ); ?> (<?php echo \WPS\WPS_Bidouille\Helpers::count_cleanup_items('spam_comments'); ?>)</a>
                    </td>
                </tr>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Cleaning comments to trash', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php _e( 'This tool will delete the comments to the trash.', 'wps-bidouille' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <a href="<?php echo add_query_arg( 'action', 'trashed_comments', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-tools' ), 'trashed_comments', 'wps-nonce' ) ); ?>"
                           class="button btn-wps clear_sessions"><?php _e( 'Clear comments from trash', 'wps-bidouille' ); ?> (<?php echo \WPS\WPS_Bidouille\Helpers::count_cleanup_items('trashed_comments'); ?>)</a>
                    </td>
                </tr>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Cleaning revisions', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php _e( 'This tool will remove revisions.', 'wps-bidouille' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <a href="<?php echo add_query_arg( 'action', 'revisions', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-tools' ), 'revisions', 'wps-nonce' ) ); ?>"
                           class="button btn-wps clear_sessions"><?php _e( 'Delete revisions', 'wps-bidouille' ); ?> (<?php echo \WPS\WPS_Bidouille\Helpers::count_cleanup_items('revisions'); ?>)</a>
                    </td>
                </tr>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Resetting the plugin', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php _e( 'This resets the settings of the WPS Bidouille extension.', 'wps-bidouille' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <a href="<?php echo add_query_arg( 'action', 'reinitialize', wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-tools' ), 'reinitialize', 'wps-nonce' ) ); ?>"
                           class="button btn-wps wps-reinit"><?php _e( 'Reset', 'wps-bidouille' ); ?></a>
                    </td>
                </tr>
                <tr class="clear_sessions">
                    <th class="wps-desc-tool">
                        <strong class="name"><?php _e( 'Keep extension settings after deactivation / uninstallation', 'wps-bidouille' ); ?></strong>
                        <p class="description">
                            <strong>Note : </strong> <?php _e( 'This tool allows you to retain extension data even if you uninstall or disable WPS Bidouille.', 'wps-bidouille' ); ?>
                        </p>
                    </th>
                    <td class="run-tool">
                        <?php $wps_save_settings = get_option( 'wps_save_settings' ); ?>
                        <input type="checkbox" id="wps_save_settings" name="wps_save_settings" data-nonce="<?php echo wp_create_nonce( 'save-settings' ); ?>" value="1" <?php checked( $wps_save_settings, 'true' ); ?>>
                        <label for="wps_save_settings">&nbsp;</label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>