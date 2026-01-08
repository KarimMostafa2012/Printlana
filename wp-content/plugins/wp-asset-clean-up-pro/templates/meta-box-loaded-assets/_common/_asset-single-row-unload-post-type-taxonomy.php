<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-(script|style)-single-row.php
*/

// [wpacu_pro]
use WpAssetCleanUpPro\MainPro;
// [/wpacu_pro]

if (! isset($data)) {
	exit; // no direct access
}

$assetType  = $data['row']['asset_type'];
$assetTypeS = substr($data['row']['asset_type'], 0, -1); // "styles" to "style" & "scripts" to "script"

// Unload it if the post has a certain "Category", "Tag" or other taxonomy associated with it.

// Only show it if "Unload site-wide" is NOT enabled
// Otherwise, there's no point to use this unload rule based on the chosen taxonomy's value if the asset is unloaded site-wide
if (! $data['row']['global_unloaded']) {
    // [wpacu_pro]
	$handleUnloadViaTax = ( isset( $data['handle_unload_via_tax'][$assetType][ $data['row']['obj']->handle ] ) && $data['handle_unload_via_tax'][$assetType][ $data['row']['obj']->handle ] )
		? $data['handle_unload_via_tax'][$assetType][ $data['row']['obj']->handle ]
		: array();

	$handleUnloadViaTax['enable'] = isset( $handleUnloadViaTax['enable'] ) && $handleUnloadViaTax['enable'];
	$handleUnloadViaTax['values'] = ( isset( $handleUnloadViaTax['values'] ) && $handleUnloadViaTax['values'] ) ? $handleUnloadViaTax['values'] : '';

	$isUnloadViaTaxEnabledWithValues = ($handleUnloadViaTax['enable'] && ! empty($handleUnloadViaTax['values']));

    if ($isUnloadViaTaxEnabledWithValues) { $data['row']['at_least_one_rule_set'] = true; }
	// [/wpacu_pro]
	?>
    <div class="wpacu_asset_options_wrap wpacu_manage_via_tax_area_wrap">
        <ul class="wpacu_asset_options">
            <?php
            if ($assetType === 'scripts') {
	            if (isset($data['row']['obj']->tag_output) && stripos($data['row']['obj']->tag_output, '<noscript') === 0) {
		            switch ( $data['post_type'] ) {
			            case 'product':
				            $unloadViaTaxText = __( 'Unload NOSCRIPT tag on all WooCommerce "Product" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up' );
				            break;
			            case 'download':
				            $unloadViaTaxText = __( 'Unload NOSCRIPT tag on all Easy Digital Downloads "Download" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up' );
				            break;
			            default:
				            $unloadViaTaxText = sprintf( __( 'Unload NOSCRIPT tag on all pages of "<strong>%s</strong>" post type if these taxonomies (category, tag, etc.) are set', 'wp-asset-clean-up' ), $data['post_type'] );
		            }
	            } else {
		            switch ( $data['post_type'] ) {
			            case 'product':
				            $unloadViaTaxText = __( 'Unload JS on all WooCommerce "Product" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up' );
				            break;
			            case 'download':
				            $unloadViaTaxText = __( 'Unload JS on all Easy Digital Downloads "Download" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up' );
				            break;
			            default:
				            $unloadViaTaxText = sprintf( __( 'Unload JS on all pages of "<strong>%s</strong>" post type if these taxonomies (category, tag, etc.) are set', 'wp-asset-clean-up' ), $data['post_type'] );
		            }
	            }
            } else {
	            switch ( $data['post_type'] ) {
		            case 'product':
			            $unloadViaTaxText = __( 'Unload CSS on all WooCommerce "Product" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up' );
			            break;
		            case 'download':
			            $unloadViaTaxText = __( 'Unload CSS on all Easy Digital Downloads "Download" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up' );
			            break;
		            default:
			            $unloadViaTaxText = sprintf( __( 'Unload CSS on all pages of "<strong>%s</strong>" post type if these taxonomies (category, tag, etc.) are set', 'wp-asset-clean-up' ), $data['post_type'] );
	            }
            }
            ?>
            <li>
                <label for="wpacu_unload_it_via_tax_option_<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
					<?php if ( $isUnloadViaTaxEnabledWithValues ) {
						echo ' class="wpacu_unload_checked"';
					} ?>>
                    <input data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                           data-handle-for="<?php echo $assetTypeS; ?>"
                           id="wpacu_unload_it_via_tax_option_<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                           class="wpacu_unload_it_via_tax_checkbox wpacu_unload_rule_input wpacu_bulk_unload"
                           type="checkbox"
                           name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[<?php echo $assetType; ?>][unload_post_type_via_tax][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][enable]"
						<?php if ( $isUnloadViaTaxEnabledWithValues ) { ?> checked="checked" <?php } ?>
                           value="1"/>&nbsp;<span><?php echo $unloadViaTaxText; ?>:</span></label>
                <a style="text-decoration: none; color: inherit; vertical-align: middle;" target="_blank"
                   href="https://www.assetcleanup.com/docs/?p=1415#unload"><span
                            class="dashicons dashicons-editor-help"></span></a>
                <div class="wpacu_handle_manage_via_tax_input_wrap wpacu_handle_unload_via_tax_input_wrap <?php if ( ! $isUnloadViaTaxEnabledWithValues ) { echo 'wpacu_hide'; } ?>">
                    <div class="wpacu_manage_via_tax_rule_area" style="min-width: 300px;">
                        <select name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[<?php echo $assetType; ?>][unload_post_type_via_tax][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][values][]"
                                class="wpacu_manage_via_tax_dd wpacu_unload_via_tax_dd <?php if ($isUnloadViaTaxEnabledWithValues && $data['plugin_settings']['input_style'] === 'enhanced') { echo ' wpacu_chosen_select '; } echo ($data['plugin_settings']['input_style'] === 'enhanced') ? ' wpacu_chosen_can_be_later_enabled ' : ''; ?>"
                                data-placeholder="<?php esc_attr_e('Select taxonomies added to the post type'); ?>..."
                                multiple="multiple"
                                data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                                data-handle-for="<?php echo $assetTypeS; ?>"><?php if ( $isUnloadViaTaxEnabledWithValues ) { echo MainPro::loadDDOptionsForAllSetTermsForPostType($data['post_type'], $assetType, $data['row']['obj']->handle, $handleUnloadViaTax['values']); } ?></select>
                    </div>
                </div>
	            <?php
	            if ( ! $isUnloadViaTaxEnabledWithValues ) {
                // The loader shows when the checkbox above is checked
                ?>
                <div data-wpacu-tax-terms-options-loader="1" style="display: none; margin: 10px 0 10px;">
                    <img src="<?php echo WPACU_PLUGIN_URL; ?>/assets/icons/loader-horizontal.svg?x=<?php echo time(); ?>"
                         align="top"
                         width="90"
                         alt="" />
                </div>
	            <?php } ?>
            </li>
        </ul>
    </div>
	<?php
}
