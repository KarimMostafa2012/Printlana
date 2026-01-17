<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-script-single-row.php
*/

use WpAssetCleanUp\Misc;

if ( ! isset($data, $ver) ) {
	exit; // no direct access
}

if (isset($data['row']['obj']->src, $data['row']['obj']->srcHref) && $data['row']['obj']->srcHref && trim($data['row']['obj']->src) !== '') {
	$assetHandleHasSrc = $isExternalSrc = true;

	if (Misc::getLocalSrcIfExist($data['row']['obj']->src)
        || strpos($data['row']['obj']->src, '/?') !== false // Dynamic Local URL
        || strpos(str_replace(site_url(), '', $data['row']['obj']->src), '?') === 0 // Starts with ? right after the site url (it's a local URL)
	) {
		$isExternalSrc = false;
	}

	$srcHref = $data['row']['obj']->srcHref;

	// If the source starts with '../' mark it as external to be checked via the AJAX call (special case)
	if (strpos($srcHref, '../') === 0) {
		$currentPageUrl = Misc::getCurrentPageUrl();
		$srcHref = trim($currentPageUrl, '/') . '/'. $data['row']['obj']->srcHref;
		$isExternalSrc = true; // simulation
	}

	$relSrc = str_replace(site_url(), '', $data['row']['obj']->src);

	if (isset($data['row']['obj']->baseUrl)) {
		$relSrc = str_replace($data['row']['obj']->baseUrl, '/', $relSrc);
	}

	if ($isExternalSrc) {
		$verToAppend = ''; // no need for any "ver"
	} else {
		$appendAfterSrcHref = ( strpos( $srcHref, '?' ) === false ) ? '?' : '&';

		if ( isset( $data['row']['obj']->ver ) && $data['row']['obj']->ver ) {
			$verToAppend = $appendAfterSrcHref .
			               (is_array( $data['row']['obj']->ver )
				               ? http_build_query( array( 'ver' => $data['row']['obj']->ver ) )
				               : 'ver=' . $ver);
		} else {
			global $wp_version;
			$verToAppend = $appendAfterSrcHref . 'ver=' . $wp_version;
		}
	}

	$isJsPreload = (isset($data['preloads']['scripts'][$data['row']['obj']->handle]) && $data['preloads']['scripts'][$data['row']['obj']->handle])
		? $data['preloads']['scripts'][$data['row']['obj']->handle]
		: false;

	if ($isJsPreload) {
		$data['row']['obj']->preload_status = 'preloaded';
		$data['row']['at_least_one_rule_set'] = true;
	}
	?>
	<div class="wpacu-source-row">
		<?php
		if (isset($data['row']['obj']->src_origin, $data['row']['obj']->ver_origin) && $data['row']['obj']->src_origin) {
			$sourceText = esc_html__('Source (updated):', 'wp-asset-clean-up');
			$messageToAlert = sprintf(
				esc_html__('On this page, the `%s` JavaScript handle had its source updated via `%s` filter tag.' ."\n\n". 'Original Source: %s (version: %s)'),
				$data['row']['obj']->handle,
				'wpacu_'.$data['row']['obj']->handle.'_js_handle_data',
				$data['row']['obj']->src_origin,
				($data['row']['obj']->ver_origin ?: esc_html__('null', 'wp-asset-clean-up'))
			);
			?>
            <a style="text-decoration: none; display: inline-block;"
               href="#"
               class="wpacu-filter-handle"
               data-wpacu-filter-handle-message="<?php echo esc_attr($messageToAlert); ?>"
            ><span class="dashicons dashicons-filter"></span></a>
		<?php } else {
			$sourceText = esc_html__('Source:', 'wp-asset-clean-up'); // as it is, no replacement
		}
		echo esc_html($sourceText); ?>
        <a target="_blank"  style="color: green;" <?php if ($isExternalSrc) { ?> data-wpacu-external-source="<?php echo esc_attr($srcHref . $verToAppend); ?>" <?php } ?> href="<?php echo esc_attr($srcHref . $verToAppend); ?>"><?php echo wp_kses($relSrc, array('u' => array('style' => array()))); ?></a> <?php if ($isExternalSrc) { ?><span data-wpacu-external-source-status></span><?php } ?>
		<?php
        include __DIR__.'/_preload.php';
        ?>
	</div>
	<?php
} else {
    $hasNoSrc = true;
}
