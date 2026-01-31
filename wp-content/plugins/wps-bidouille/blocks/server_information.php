<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$class = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-server-info' ); ?>

<div id="wps-dashboard-server-info">
    <h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>">
        <span><?php _e( 'Server information', 'wps-bidouille' ); ?></span>
    </h2>
    <div class="main <?php echo esc_attr( $class['div'] ); ?>">
		<?php
		$memory                 = \WPS\WPS_Bidouille\Helpers::_get_memory();
		$server_info            = $memory['server_info'];
		$memory_limit           = $memory['memory_limit'];
		$memory_usage           = $memory['memory_usage'];
		$memory_miv             = $memory['max_input_vars'];
		$post_max_size          = $memory['post_max_size'];
		$upload_max_filesize    = $memory['upload_max_filesize'];
		$allow_url_fopen_icon   = $memory['allow_url_fopen'] == 0 ? '' : '-open';
		$allow_url_include_icon = $memory['allow_url_include'] == 0 ? '' : '-open';

		$degre                  = (int) ( (int) $memory_usage / ( intval( $memory_limit) * 10000 ) ) ;

		$ssl_message = ( is_ssl() ? '<span style="width: 23px;text-align: center;font-size: 15px;display: inline-block;"><i class="fal fa-check" data-fa-transform="grow-4"></i></span>' : __( 'No' ) );
		$certinfo    = \WPS\WPS_Bidouille\Helpers::get_informations_ssl();

		if ( ! empty( $certinfo ) ) {
			$ssl_message .= ' ' . __( 'Validity:', 'wps-bidouille' ) . ' ' . date( 'd/m/Y', $certinfo['validTo_time_t'] );
		}

		if ( $degre <= 49.99 ) {
			$battery = 'quarter';
		} elseif ( $degre >= 49.99 && $degre <= 74.99 ) {
			$battery = 'half';
		} elseif ( $degre >= 75 ) {
			$battery = 'full';
		} ?>

        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-server" data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'Environment', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo esc_html( $server_info ); ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Server information', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fab fa-php" data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'PHP Version', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo phpversion(); ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'The version of PHP installed on your hosting server', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fas fa-microchip" data-fa-transform="grow-4 rotate-90"></i></span>
            <span class="wps-name"><?php _e( 'Total Memory', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo esc_html( $memory_limit ); ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'The maximum amount of memory (RAM) that your site can use at one time', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-battery-full" data-fa-transform="grow-2"></i></span>
            <span class="wps-name"><?php _e( 'Used Memory', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo size_format( $memory_usage, 2 ); ?> <span
                        style="margin-left:8px;"></span><i class="fal fa-battery-<?php echo $battery; ?>"
                                                           data-fa-transform="grow-10"></i></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Memory usage', 'wps-bidouille' ) . ' PHP' ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-lock" data-fa-transform="grow-2"></i></span>
            <span class="wps-name"><?php _e( 'SSL Certificat', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo ' ' . $ssl_message; ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'An SSL certificate certifies the identity of a company and is used to encrypt data exchanged over a network', 'wps-bidouille' ) . ' HTTPS' ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-upload" data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'Post Max size', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo esc_html( $post_max_size ); ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'Sets the maximum size of data received by POST method', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-upload" data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'Upload Max filesize', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo esc_html( $upload_max_filesize ); ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'The maximum weight of files sent to the server by a form', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-code" data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'Max input vars', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo esc_html( $memory_miv ); ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'The maximum number of variables that your server can use for a single function to avoid overloads', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-folder<?php echo $allow_url_fopen_icon; ?>"
                                      data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'Allow URL fopen', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo ( $memory['allow_url_fopen'] == 0 ) ? 'Off' : 'On'; ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'This option enables the file access functions', 'wps-bidouille' ) ); ?>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-folder<?php echo $allow_url_include_icon; ?>"
                                      data-fa-transform="grow-4"></i></span>
            <span class="wps-name"><?php _e( 'Allow URL include', 'wps-bidouille' ); ?></span>
            <span class="wps-result"><?php echo ( $memory['allow_url_include'] == 0 ) ? 'Off' : 'On'; ?></span>
			<?php echo \WPS\WPS_Bidouille\Helpers::wps_help_tip( __( 'This option allows the use of URL handlers', 'wps-bidouille' ) ); ?>
        </div>
    </div>
</div>