<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$pf                     = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
$is_user_white_label    = \WPS\WPS_Bidouille\Helpers::is_user_white_label();
if ( $is_user_white_label || false === apply_filters( 'wps_display_account', true ) || empty( $pf ) ) {
	return false;
}

$class = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-user-infos' ); ?>

<div id="wps-dashboard-user-infos">
	<h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>">
		<span><?php _e( 'Account WPServeur', 'wps-bidouille' ); ?></span>
	</h2>
	<div class="main <?php echo esc_attr( $class['div'] ); ?>">
		<?php $user = \WPS\WPS_Bidouille\Helpers::_ip_check(); ?>
        <ul>
            <li>
                <span class="wps-login"><i class="fal fa-user-alt"></i>
                    <?php echo __( 'Login WPServeur', 'wps-bidouille' ) . ' : ' . $user['login']; ?></span>
            </li>
            <li>
                <span class="wps-serveur"><i class="fal fa-server"></i>
                    <?php echo __( 'Server', 'wps-bidouille' ) . ' : ' . strtoupper( $pf ); ?>
                </span>
            </li>
            <li>
                <span class="wps-wp"><i class="fab fa-wordpress-simple"></i> <?php echo __( 'Path WordPress', 'wps-bidouille' ) . ' : ' . $user['path']; ?></span>
            </li>
        </ul>
        <div class="wps-acces-btn">
            <?php
            foreach ( $user as $k => $item ) :
                if ( $k !== 'phpmyadmin' && $k !== 'monitoring' && $k !== 'console' ) {
                    continue;
                }

                if ( $k == 'phpmyadmin' ) {
                    $label = 'phpMyAdmin';
                    $icon  = 'database';
                } elseif ( $k == 'monitoring' ) {
                    $label = __( 'Monitoring', 'wps-bidouille' );
                    $icon  = 'signal';
                } elseif ( $k == 'console' ) {
                    $label = __( 'Console', 'wps-bidouille' );
                    $icon  = 'tachometer';
                } ?>
                <a href="<?php echo esc_url( $item ); ?>" class="btn btn-wps-<?php echo esc_attr( $k ); ?>"
                   target="_blank"><span
                        class="icon-btn"><i class="fal fa-<?php echo esc_attr( $icon ); ?>"  data-fa-transform="grow-6"></i></span>
                    <span
                        class="txt-btn"><?php echo esc_html( $label ); ?></span></a>
                <?php
            endforeach; ?>
            <a href="https://www.wpserveur.net/mon-compte" class="btn btn-wps-my-account" target="_blank">
                <span class="icon-btn"><i class="fal fa-user-alt" data-fa-transform="grow-6"></i></span>
                <span class="txt-btn"><?php _e( 'My Account', 'wps-bidouille' ); ?></span>
            </a>

            <a href="https://www.wpserveur.net/mode-emploi-wps/" class="btn btn-wps-mode-emploi" target="_blank">
                <span class="icon-btn"><i class="fal fa-book" data-fa-transform="grow-6"></i></span>
                <span class="txt-btn"><?php _e( 'WPServeur User Guide', 'wps-bidouille' ); ?></span>
            </a>
        </div>
	</div>
</div>