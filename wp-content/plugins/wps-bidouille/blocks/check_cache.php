<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
} ?>

<div id="wps-dashboard-cache">
    <h2 class="hndle"><span><?php _e( 'Check Cache', 'wps-bidouille' ); ?> NGINX</span></h2>
    <div class="main">
        <div class="desc-report">
            <i class="fas fa-info-square"></i> <?php _e( 'This tool makes it possible to check if the urls tested below are taken into account by the NGINX cache server', 'wps-bidouille' ); ?>
        </div>
		<?php
		if ( isset( $_POST['scan_cache_url'] ) && $_POST['scan_cache_url'] != '' ) {
		    if ( filter_var( $_POST['scan_cache_url'], FILTER_VALIDATE_URL ) ) {

			    // Clear all caching files.
			    if ( function_exists( 'rocket_clean_domain' ) ) {
				    rocket_clean_domain();
			    }

			    \WPS\WPS_Bidouille\Helpers::_curl_headers( $_POST['scan_cache_url'], true );
			    $scan_cache = \WPS\WPS_Bidouille\Helpers::_curl_headers( $_POST['scan_cache_url'] );
		    }
		} else {
			\WPS\WPS_Bidouille\Helpers::_curl_headers( get_site_url(), true );
			$scan_cache = \WPS\WPS_Bidouille\Helpers::_curl_headers( get_site_url() );
		} ?>
        <div class="wps-cache-check">
            <form method="post" action="">
                <input class="wps-input-check-cache" type="url" name="scan_cache_url"
                       value="<?php echo( isset( $_POST['scan_cache_url'] ) ? esc_attr( $_POST['scan_cache_url'] ) : '' ); ?>"
                       placeholder="<?php _e( 'Scan this URL', 'wps-bidouille' ); ?>">
                <button class="btn-wps btn-wps-check-cache"><span class="icon-btn"><i class="fal fa-search"
                                                                                      data-fa-transform="grow-6"></i></span><span
                            class="txt-btn"><?php _e( 'Check this URL', 'wps-bidouille' ); ?></span></button>
            </form>
        </div>
        <div class="wps-cache-result">
            <p><?php echo __( 'Result of the page', 'wps-bidouille' ) . ' : ' . ( isset( $_POST['scan_cache_url'] ) && ! empty( $_POST['scan_cache_url'] ) ? esc_url( $_POST['scan_cache_url'] ) : get_site_url() ); ?></p>
			<?php echo $scan_cache; ?>
        </div>
    </div>
</div>