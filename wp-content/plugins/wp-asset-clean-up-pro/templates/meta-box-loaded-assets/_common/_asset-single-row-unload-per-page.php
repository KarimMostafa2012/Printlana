<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-(script|style)-single-row.php
*/

if (! isset($data, $isGroupUnloaded)) {
	exit; // no direct access
}

$assetType  = $data['row']['asset_type'];
$assetTypeS = substr($data['row']['asset_type'], 0, -1); // "styles" to "style" & "scripts" to "script"

// Bulk Unloaded Notice (e.g. for all 'post' pages, but not site-wide)
$isBulkUnloadedExceptSiteWide = ! $data['row']['global_unloaded'] && $isGroupUnloaded;

if (! $isBulkUnloadedExceptSiteWide) {
?>
<div class="wpacu_asset_options_wrap"
     style="<?php
     // If site-wide unloaded
     if ($data['row']['global_unloaded']) {
	     echo 'display: none;';
     }
     ?> padding: 8px 10px 6px !important;">
	<ul class="wpacu_asset_options" <?php if ($isGroupUnloaded) { echo 'style="display: none;"'; } ?>>
		<li class="wpacu_unload_this_page">
			<label class="wpacu_switch">
				<input data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                       data-handle-for="<?php echo $assetTypeS; ?>"
				       class="input-unload-on-this-page <?php if (! $isGroupUnloaded) { echo 'wpacu-not-locked'; } ?> wpacu_unload_rule_input wpacu_unload_rule_for_<?php echo $assetTypeS; ?>"
				       id="<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
					   <?php
                       if ($isGroupUnloaded) { echo ' disabled="disabled" '; }
                       echo ' '.wp_kses($data['row']['checked'], array('checked' => array())).' ';
                       ?>
                       name="<?php echo WPACU_PLUGIN_ID; ?>[<?php echo $assetType; ?>][]"
					   type="checkbox"
					   value="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>" />
				<span class="wpacu_slider wpacu_round"></span>
			</label>
			<label class="wpacu_slider_text" for="<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>">
				<?php echo esc_html($data['page_unload_text']); ?>
			</label>
		</li>
	</ul>
</div>
<?php
} else {
?>
<div style="display: block; background: inherit; border: inherit; float: none; padding: 8px 10px 6px !important;" class="wpacu_asset_options_wrap">
    <p style="margin: 0 !important;">
        <em><?php esc_html_e('Some unload rules are not showing up due to other bulk unload rules that are taking effect', 'wp-asset-clean-up'); ?>.</em>
        <a style="text-decoration: none; color: inherit; vertical-align: middle;" target="_blank" href="https://www.assetcleanup.com/docs/?p=1421"><span class="dashicons dashicons-editor-help"></span></a>
    </p>
    <div class="wpacu_clearfix" style="margin-top: -5px; height: 0;"></div>
</div>
<?php
}