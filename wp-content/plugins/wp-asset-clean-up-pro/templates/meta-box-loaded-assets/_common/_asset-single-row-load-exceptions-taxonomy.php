<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_common/_asset-single-row-load-exceptions.php
 */

// [wpacu_pro]
use WpAssetCleanUpPro\MainPro;
// [/wpacu_pro]

if (! isset($data)) {
	exit; // no direct access
}

$assetType  = $data['row']['asset_type'];
$assetTypeS = substr($data['row']['asset_type'], 0, -1); // "styles" to "style" & "scripts" to "script"

// Only show it on edit post/page/custom post type depending on the taxonomies set
$handleLoadViaTax = ( isset( $data['handle_load_via_tax'][$assetType][ $data['row']['obj']->handle ] ) && $data['handle_load_via_tax'][$assetType][ $data['row']['obj']->handle ] )
	? $data['handle_load_via_tax'][$assetType][ $data['row']['obj']->handle ]
	: array();

$handleLoadViaTax['enable'] = isset( $handleLoadViaTax['enable'] ) && $handleLoadViaTax['enable'];
$handleLoadViaTax['values'] = ( isset( $handleLoadViaTax['values'] ) && $handleLoadViaTax['values'] ) ? $handleLoadViaTax['values'] : '';

$isLoadViaTaxEnabledWithValues = ($handleLoadViaTax['enable'] && ! empty($handleLoadViaTax['values']));
if ($isLoadViaTaxEnabledWithValues) { $data['row']['at_least_one_rule_set'] = true; }

switch ($data['post_type']) {
	case 'product':
		$loadBulkTextViaTax = __('On all WooCommerce "Product" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up');
		break;
	case 'download':
		$loadBulkTextViaTax = __('On all Easy Digital Downloads "Download" pages if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up');
		break;
	default:
		$loadBulkTextViaTax = sprintf(__('On all pages of "<strong>%s</strong>" post type if these taxonomies (e.g. Category, Tag) are set', 'wp-asset-clean-up'), $data['post_type']);
}
?>
<li>
    <label for="wpacu_load_it_via_tax_option_<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>">
        <input data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
               data-handle-for="<?php echo $assetTypeS; ?>"
               id="wpacu_load_it_via_tax_option_<?php echo $assetTypeS; ?>_<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
               class="wpacu_load_it_via_tax_checkbox wpacu_load_exception wpacu_load_rule_input wpacu_bulk_load"
               type="checkbox"
               name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[<?php echo $assetType; ?>][load_it_post_type_via_tax][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][enable]"
			<?php if ( $isLoadViaTaxEnabledWithValues ) { ?> checked="checked" <?php } ?>
               value="1"/>&nbsp;<span><?php echo $loadBulkTextViaTax; ?>:</span></label>
    <a style="text-decoration: none; color: inherit; vertical-align: middle;" target="_blank"
       href="https://www.assetcleanup.com/docs/?p=1415#load_exception"><span
                class="dashicons dashicons-editor-help"></span></a>
    <div class="wpacu_handle_manage_via_tax_input_wrap wpacu_handle_load_via_tax_input_wrap <?php if ( ! $isLoadViaTaxEnabledWithValues ) { echo 'wpacu_hide'; } ?>">
        <div class="wpacu_manage_via_tax_rule_area" style="min-width: 300px;">
            <select name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[<?php echo $assetType; ?>][load_it_post_type_via_tax][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][values][]"
                    class="wpacu_manage_via_tax_dd wpacu_load_via_tax_dd <?php if ($isLoadViaTaxEnabledWithValues && $data['plugin_settings']['input_style'] === 'enhanced') { echo ' wpacu_chosen_select '; } echo ($data['plugin_settings']['input_style'] === 'enhanced') ? ' wpacu_chosen_can_be_later_enabled ' : ''; ?>"
                    data-placeholder="<?php esc_attr_e('Select taxonomies added to the post type'); ?>..."
                    multiple="multiple"
                    data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                    data-handle-for="<?php echo $assetTypeS; ?>"><?php if ( $isLoadViaTaxEnabledWithValues ) { echo MainPro::loadDDOptionsForAllSetTermsForPostType($data['post_type'], $assetType, $data['row']['obj']->handle, $handleLoadViaTax['values'], 'load_exception'); } ?></select>
        </div>
    </div>
	<?php
	if ( ! $isLoadViaTaxEnabledWithValues ) {
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
