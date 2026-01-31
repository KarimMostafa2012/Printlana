<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-(script|style)-single-row.php
*/

if (! isset($data)) {
	exit; // no direct access
}

$assetType  = $data['row']['asset_type'];
$assetTypeS = substr($data['row']['asset_type'], 0, -1); // "styles" to "style" & "scripts" to "script"

// Only show it if "Unload site-wide" is NOT enabled
// Otherwise, there's no point to use an unload regex if the asset is unloaded site-wide
if (! $data['row']['global_unloaded']) {
	$handleUnloadRegex = ( isset( $data['handle_unload_regex'][$assetType][ $data['row']['obj']->handle ] ) && $data['handle_unload_regex'][$assetType][ $data['row']['obj']->handle ] )
		? $data['handle_unload_regex'][$assetType][ $data['row']['obj']->handle ]
		: array();

	$handleUnloadRegex['enable'] = isset( $handleUnloadRegex['enable'] ) && $handleUnloadRegex['enable'];
	$handleUnloadRegex['value']  = ( isset( $handleUnloadRegex['value'] ) && $handleUnloadRegex['value'] ) ? $handleUnloadRegex['value'] : '';

	$isUnloadRegExEnabledWithValue = $handleUnloadRegex['enable'] && $handleUnloadRegex['value'];
	if ($isUnloadRegExEnabledWithValue) { $data['row']['at_least_one_rule_set'] = true; }
	?>
	<div data-<?php echo $assetTypeS; ?>-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>" class="wpacu_asset_options_wrap wpacu_unload_regex_area_wrap">
		<ul class="wpacu_asset_options">
			<li>
				<label for="wpacu_unload_it_regex_option_<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
					<?php if ( $isUnloadRegExEnabledWithValue ) {
						echo ' class="wpacu_unload_checked"';
					} ?>>
					<input data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
					       data-handle-for="<?php echo $assetTypeS; ?>"
					       id="wpacu_unload_it_regex_option_<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
					       class="wpacu_unload_it_regex_checkbox wpacu_unload_rule_input wpacu_bulk_unload"
					       type="checkbox"
					       name="wpacu_handle_unload_regex[<?php echo $assetType; ?>][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][enable]"
						<?php if ( $handleUnloadRegex['enable'] ) { ?> checked="checked" <?php } ?>
						   value="1"/>&nbsp;<span><?php
                        if ($assetType === 'styles') {
	                        $assetTypeText = 'CSS';
                        } else {
	                        $assetTypeText = 'JS';

	                        if (isset($data['row']['obj']->tag_output) && stripos($data['row']['obj']->tag_output, '<noscript') === 0) {
                                $assetTypeText = 'NOSCRIPT tag';
                            }
                        }
						echo sprintf(__('Unload %s for URLs with request URI matching the following RegEx(es)', 'wp-asset-clean-up'), $assetTypeText);
						?>:</span></label>
				<a style="text-decoration: none; color: inherit; vertical-align: middle;" target="_blank"
				   href="https://assetcleanup.com/docs/?p=313#wpacu-unload-by-regex"><span
						class="dashicons dashicons-editor-help"></span></a>
				<div class="wpacu_handle_unload_regex_input_wrap <?php if (! $isUnloadRegExEnabledWithValue) { echo 'wpacu_hide'; } ?>">
                    <div class="wpacu_regex_rule_area">
                        <textarea <?php if (! $isUnloadRegExEnabledWithValue) { echo 'disabled="disabled"'; } ?>
                            class="wpacu_regex_rule_textarea"
                            data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                            data-handle-for="<?php echo $assetTypeS; ?>"
                            data-wpacu-adapt-height="1"
                            name="wpacu_handle_unload_regex[<?php echo $assetType; ?>][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][value]"><?php echo esc_attr($handleUnloadRegex['value']); ?></textarea>
                        <p style="margin-top: 0;"><small><span style="font-weight: 500;">Note:</span> Multiple RegEx rules can be added as long as they are one per line.</small></p>
                    </div>
				</div>
			</li>
		</ul>
	</div>
	<?php
}
