<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$pf = \WPS\WPS_Bidouille\Helpers::wps_ip_check_return_pf();
if ( ! empty( $pf ) ) {
	return false;
}

$plugin                 = 'bv-migration-to-wpserveur/blogvault.php';
$is_plugin_installed    = \WPS\WPS_Bidouille\Helpers::is_plugin_installed( $plugin );
if ( ! $is_plugin_installed ) {
	$classes = 'install-now';
	$action_url    = wp_nonce_url( add_query_arg(
		array(
			'action' => 'install-plugin',
			'plugin' => 'bv-migration-to-wpserveur',
		),
		network_admin_url( 'update.php' )
	), 'install-plugin_bv-migration-to-wpserveur' );
	$button = __( 'Install WPServeur migration', 'wps-bidouille' );
} else {
	$action_url  = wp_nonce_url( add_query_arg(
		array(
			'action'        => 'activate',
			'plugin'        => $plugin,
			'plugin_status' => 'all',
			'paged'         => 1
		),
		network_admin_url( 'plugins.php' )
	), 'activate-plugin_' . $plugin );

	$button = __( 'Enable WPServeur migration', 'wps-bidouille' );
}

$details_url = add_query_arg(
	array(
		'tab'       => 'plugin-information',
		'plugin'    => 'bv-migration-to-wpserveur',
		'TB_iframe' => true,
		'width'     => 722,
		'height'    => 949,
	),
	network_admin_url( 'plugin-install.php' )
); ?>
<style>
   .pub-wp-serveur.plugin-card.plugin-card-bv-migration-to-wpserveur {
        background:url(<?php echo WPS_BIDOUILLE_URL .'assets/img/bg_pub.png'; ?>) no-repeat center bottom #33414E;
        position: relative;
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
        -webkit-align-items: center;
        -ms-flex-align: center;
        -ms-grid-row-align: center;
        align-items: center;
        float: none;
        width: auto;
        padding: 5px 5px 5px 0;
        border: 0 none;
        box-shadow: none;
        color: #FFF;
        margin: 0 0 5px 0;
    }
.pub-wp-serveur .logo {
            padding: 0px 20px;
        }

        .pub-wp-serveur > .message {
            width: 100%;
            padding: 0px 10px;
            font-size: 14px;
            text-align:center;
        }

        .pub-wp-serveur .cta {
            padding: 0px 0px;
        }

        .btn-install-plugin.activate-now,
        .btn-pubwps {
            display: block;
            background: #fff;
            width: 100%;
            padding: 8px 20px;
            white-space: nowrap;
            margin-bottom: 4px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            border-radius: 4px;
            line-height: 18px;
            height: unset;
            text-shadow: none;
            box-sizing: border-box;
        }
        .plugin-card-bv-migration-to-wpserveur .notice-dismiss:before {
            color: #ffffff;
            font: 400 26px/26px dashicons;
        }
        .btn-wps-details {
            color: #fff;
            box-sizing: border-box;
        }

        .btn-abonner {
            background: #95bf23;
            color: #fff;
        }

        .btn-abonner:focus,
        .btn-abonner:hover {
            color: #95bf23;
            background: #fff;
            box-shadow: none !important;
        }
        .btn-pubwps.btn-install-plugin.activate-now.button-primary,
        .btn-pubwps.btn-install-plugin.activate-now.button-primary:focus,
        .btn-install-plugin.activate-now,
        .btn-install-plugin {
            background: #26a0d2;
            color: #fff;
            position: relative;
            border:none;
            box-shadow:none;
        }
        .btn-pubwps.btn-install-plugin.install-now:focus,
        .btn-install-plugin.activate-now:hover,
        .btn-install-plugin:hover {
            color: #26a0d2;
            background: #fff;
            border:none;
            box-shadow:none;
        }

        .btn-wps-details {
            text-align: center;
            width: 100%;
            display: block;
            white-space: nowrap;
            padding: 0 20px;
        }

        a.btn-wps-details:focus {
            color: #fff !important;
            box-shadow: none !important;
        }
        a.btn-pubwps.btn-install-plugin.install-now.updated-message.installed.button-disabled:before {
            font: normal 20px/1 'dashicons';
            position: absolute;
            left: 10px;
            top:7px;
        }
        .btn-install-plugin.updating-message:before {
            font: normal 20px/1 'dashicons';
            position: absolute;
            left: 10px;
            top: 7px;
        }

        @media screen and (max-width: 860px) {
            .pub-wp-serveur,
            .pub-wp-serveur .logo,
            .pub-wp-serveur .message,
            .pub-wp-serveur .cta {
                display: block !important;
                padding: 5px 10px;
            }

            .btn-pubwps {
                width: unset;
            }

            .pub-wp-serveur .message,
            .pub-wp-serveur .logo {
                text-align: center;
            }

            .pub-wp-serveur .cta {
                margin-bottom: 20px;
            }
        }
</style>
<div class="pub-wp-serveur plugin-card plugin-card-bv-migration-to-wpserveur">
	<div class="message">
        <strong><?php _e( 'Discover the specialized WordPress hosting with WPServeur!', 'wps-bidouille' ); ?></strong>
        <strong><?php _e( 'For fast, secure and efficient hosting.', 'wps-bidouille' ); ?></strong><br>
        <?php _e( 'We only do WordPress but we do it well!', 'wps-bidouille' ); ?>
        <i style="font-size:11px;"><?php _e( '(-10% discount on your subscription with the promo code <strong>WPSC&T</strong>)', 'wps-bidouille' ); ?></i></div>
	<div class="cta">
		<a href="https://www.wpserveur.net/?refwps=14&campaign=wpsbidouille" target="_blank" class="btn-pubwps btn-abonner"><?php _e( 'Subscribe to WPServeur', 'wps-bidouille' ); ?></a>
		<a data-slug="bv-migration-to-wpserveur" href="<?php echo $action_url; ?>" class="btn-pubwps btn-install-plugin <?php echo $classes; ?>"><?php echo $button; ?></a>
		<a href="<?php echo $details_url; ?>" class="thickbox open-plugin-details-modal btn-wps-details"><?php _e( 'More about WPServeur migration', 'wps-bidouille' ); ?></a>
	</div>
</div>