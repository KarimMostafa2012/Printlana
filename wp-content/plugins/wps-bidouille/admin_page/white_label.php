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
	$users = get_users( array( 'exclude' => array( get_current_user_id() ) ) ); ?>

    <h1 class="wp-heading-inline"><?php _e( 'White Label', 'wps-bidouille' ); ?></h1>
    <div id="tab-content1" class="content">
        <div><?php _e( 'The white mark tool:', 'wps-bidouille' ); ?> <span class="wps-note"><?php _e('(only for users who are not in the exclusion list)', 'wps-bidouille' ); ?></span><br>
        <ul class="wps-list-white-label">
            <li><?php _e( 'Removes the "WPServeur" widget from the Dashboard', 'wps-bidouille' ); ?></li>
            <li><?php _e( 'Removes the mention "This site is hosted by WPServeur :) PF5" on the administration login page', 'wps-bidouille' ); ?></li>
            <li><?php _e( 'Removes the notion of WPServeur account etc. on the WPS plugin Bidouille', 'wps-bidouille' ); ?></li>
        </ul>
        </div>
        
		<?php _e( 'Skip the site in white label?', 'wps-bidouille' ); ?> <span class="wps-note">(<?php _e( 'By default, the user who activates the white mark is in the exclusion list.', 'wps-bidouille' ); ?>)</span>
        <form action="options.php" method="post" id="white_label">
			<?php settings_fields( 'wps-bidouille-settings' ); ?>
            <div id="bounds">
				<?php
				$wps_white_list = get_option( 'wps_white_list' );
				if ( empty( $wps_white_list ) ) {
					$wps_white_list = 'false';
				} ?>
                <input type="radio" id="wps_white_list_on" name="wps_white_list"
                       value="true" <?php checked( esc_attr( $wps_white_list ), 'true' ); ?>>
                <label for="wps_white_list_on"><?php _e( 'Yes', 'wps-bidouille' ); ?></label>

                <input type="radio" id="wps_white_list_off" name="wps_white_list"
                       value="false" <?php checked( esc_attr( $wps_white_list ), 'false' ); ?>>
                <label for="wps_white_list_off"><?php _e( 'No', 'wps-bidouille' ); ?> </label>
            </div>
            <?php
            if ( 'true' == $wps_white_list ) : ?>
                <div class="clearfix"></div>

                <div class="wps-add-user-whitelabel">
                    <label for="select2_wps_users"><?php _e( 'List of users to exclude from the white label:', 'wps-bidouille' ); ?></label><br/>
                    <select id="select2_wps_users" name="select2_wps_users[]" multiple="multiple">
                        <?php
                        if ( $users ) {
                            foreach ( $users as $user ) {
                                $title             = $user->display_name;
                                $title             = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
                                $title             = $title . ' (' . $user->user_email . ')';
                                $select2_wps_users = get_option( 'select2_wps_users' );
                                $selected          = '';
                                if ( ! empty( $select2_wps_users ) && $user->ID !== get_current_user_id() ) {
                                    $selected = in_array( $user->ID, $select2_wps_users ) ? ' selected="selected" ' : '';
                                }
                                echo '<option value="' . esc_attr( $user->ID ) . '" ' . $selected . '>' . $title . '</option>';
                            }
                        } ?>
                    </select>
                </div>
            <?php endif; ?>
            <p><button type="submit" name="submit" id="submit" class="button btn-wps btn-wps-register"><span class="icon-btn"><i class="fal fa-save" data-fa-transform="grow-6"></i></span><span class="txt-btn"><?php _e( 'Save Changes' ); ?></span></button></p>
        </form>
    </div>
</div>