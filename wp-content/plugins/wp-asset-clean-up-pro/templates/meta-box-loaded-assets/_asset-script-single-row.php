<?php
/*
 * The file is included from _asset-script-rows.php
*/
if (! isset($data)) {
	exit; // no direct access
}

$assetType  = $data['row']['asset_type'];
$assetTypeS = substr($data['row']['asset_type'], 0, -1); // "styles" to "style" & "scripts" to "script"

$inlineCodeStatus = $data['plugin_settings']['assets_list_inline_code_status'];
$isCoreFile       = isset($data['row']['obj']->wp) && $data['row']['obj']->wp;
$hideCoreFiles    = $data['plugin_settings']['hide_core_files'];
$isGroupUnloaded  = $data['row']['is_group_unloaded'];

// Does it have "children"? - other asset file(s) depending on it
$childHandles     = isset($data['all_deps']['parent_to_child'][$assetType][$data['row']['obj']->handle]) ? $data['all_deps']['parent_to_child'][$assetType][$data['row']['obj']->handle] : array();
sort($childHandles);

$jqueryIconHtmlHandle  = '<img src="'.WPACU_PLUGIN_URL.'/assets/icons/handles/icon-jquery.png" style="max-width: 22px; max-height: 22px;" width="18" height="18" title="" alt="" />';
$jqueryIconHtmlDepends = '<img src="'.WPACU_PLUGIN_URL.'/assets/icons/handles/icon-jquery.png" style="max-width: 22px; max-height: 22px; vertical-align: text-top;" width="16" height="16" alt="" />';

// [wpacu_pro]
$assetPosition = $data['row']['obj']->position;

// Initial position (as it was set when the script was enqueued in the theme or the plugin)
// jQuery, jQuery Migrate are always loaded in the HEAD by default
if (in_array($data['row']['obj']->handle, array('jquery', 'jquery-core', 'jquery-migrate'))) {
	$assetPosition = 'head';
}

// If the position wasn't changed, keep the initial one
$assetPositionNew     = isset($data['row']['obj']->position_new) ? $data['row']['obj']->position_new : $assetPosition;
$assetLocationChanged = $assetPositionNew !== $assetPosition;

if ($assetLocationChanged) {
	$data['row']['class'] .= ' wpacu_location_changed';
}
// [/wpacu_pro]

// Unloaded site-wide
if ($data['row']['global_unloaded']) {
	$data['row']['class'] .= ' wpacu_is_global_unloaded';
}

// Unloaded site-wide OR on all posts, pages etc.
if ($isGroupUnloaded) {
	$data['row']['class'] .= ' wpacu_is_bulk_unloaded';
}

$rowIsContracted   = '';
$dashSign          = 'minus';
$dataRowStatusAttr = 'expanded';

if (isset($data['handle_rows_contracted'][$assetType][$data['row']['obj']->handle]) &&
    $data['handle_rows_contracted'][$assetType][$data['row']['obj']->handle]) {
	$rowIsContracted   = 1;
	$dashSign          = 'plus';
	$dataRowStatusAttr = 'contracted';
}
?>
<tr data-<?php echo $assetTypeS; ?>-handle-row="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
    id="wpacu_<?php echo $assetTypeS; ?>_row_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
    <?php
    // [wpacu_pro]
    if (count($childHandles) > 0) {
    ?>
        data-script-child-handles="<?php echo rtrim(implode(', ', $childHandles)); ?>"
    <?php
    }
    // [/wpacu_pro]
    ?>
    class="wpacu_asset_row <?php echo esc_attr($data['row']['class']); ?>"
    style="<?php if ($isCoreFile && $hideCoreFiles) { echo 'display: none;'; } ?>">
    <td valign="top" style="position: relative;" data-wpacu-row-status="<?php echo esc_attr($dataRowStatusAttr); ?>">
        <!-- [reference field] -->
        <input type="hidden" name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[<?php echo $assetType; ?>][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>]" value="" />
        <!-- [/reference field] -->
        <div class="wpacu_handle_row_expand_contract_area">
            <a data-wpacu-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
               data-wpacu-handle-for="<?php echo $assetTypeS; ?>"
               class="wpacu_handle_row_expand_contract"
               href="#"><span class="dashicons dashicons-<?php echo esc_attr($dashSign); ?>"></span></a>
        </div>
        <?php
        include '_asset-'.$assetTypeS.'-single-row/_handle.php';

        $ver = $data['wp_version']; // default
        if (isset($data['row']['obj']->ver) && $data['row']['obj']->ver) {
            $ver = is_array($data['row']['obj']->ver) ? implode(', ', $data['row']['obj']->ver) : $data['row']['obj']->ver;
        }

	    $data['row']['obj']->preload_status = 'not_preloaded'; // default

	    $assetHandleHasSrc = false;

        include '_asset-'.$assetTypeS.'-single-row/_source.php';

	    // Any tips?
	    if (isset($data['tips'][$assetType][$data['row']['obj']->handle]) && ($assetTip = $data['tips'][$assetType][$data['row']['obj']->handle])) {
            ?>
            <div class="tip"><strong>Tip:</strong> <?php echo esc_html($assetTip); ?></div>
		    <?php
	    }
	    ?>
        <div class="wpacu_handle_row_expanded_area <?php if ($rowIsContracted) { echo 'wpacu_hide'; } ?>">
            <?php
            $extraInfo = array();

		    include '_asset-'.$assetTypeS.'-single-row/_handle_deps.php';

		    $extraInfo[] = esc_html__('Version:', 'wp-asset-clean-up').' '.$ver;

		    include '_common/_asset-single-row-position.php';

		    if (isset($data['row']['obj']->src) && trim($data['row']['obj']->src)) {
			    $extraInfo[] = esc_html__('File Size:', 'wp-asset-clean-up') . ' <em>' . $data['row']['obj']->size . '</em>';
		    }

	        if (! empty($extraInfo)) {
	            $stylingDiv = 'margin: 10px 0';

	            if (isset($hasNoSrc) && $hasNoSrc) {
	                $stylingDiv = 'margin: 15px 0 10px;';
	            }

		        echo '<div style="'.$stylingDiv.'">'.implode(' &nbsp;/&nbsp; ', $extraInfo).'</div>';
	        }

            include '_common/_asset-unload-load-area.php';

	        // Extra inline associated with the asset's tag
	        include '_common/_asset-single-row-extra-inline.php';

	        // Async, Defer, Media Query Load (Pro)
	        include '_asset-'.$assetTypeS.'-single-row/_loaded-rules.php';

	        // Handle Note
	        include '_common/_asset-single-row-notes.php';
	        ?>
	    </div>
        <img style="display: none;" class="wpacu_ajax_loader" src="<?php echo esc_url(WPACU_PLUGIN_URL); ?>/assets/icons/icon-ajax-loading-spinner.svg" alt="<?php echo esc_html__('Loading'); ?>..." />
	</td>
</tr>