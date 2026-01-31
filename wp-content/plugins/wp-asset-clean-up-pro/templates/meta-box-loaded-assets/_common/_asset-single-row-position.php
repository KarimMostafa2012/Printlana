<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-(script|style)-single-row.php
*/
if ( ! isset($data, $assetHandleHasSrc, $assetPosition, $assetPositionNew) ) {
	exit; // no direct access
}

$assetType  = $data['row']['asset_type'];
$assetTypeS = substr($data['row']['asset_type'], 0, -1); // "styles" to "style" & "scripts" to "script"

if ( ! function_exists('wpacuPrintManageAssetPositionArea') ) {
	/**
	 * @param $assetType
	 * @param $assetPosition
	 * @param $assetPositionNew
	 * @param $data
	 */
	function wpacuPrintManageAssetPositionArea($assetType, $assetPosition, $assetPositionNew, $data)
    {
        ?>
        <div class="wpacu-wrap-choose-position">
		    <?php esc_html_e('Location:', 'wp-asset-clean-up'); ?>
            <select data-wpacu-input="position-select"
                    name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[<?php echo $assetType; ?>][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][position]"
                    style="<?php if ($assetPosition !== $assetPositionNew) {
                        echo 'background: #f2faf2 url(\'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23555%22%2F%3E%3C%2Fsvg%3E\') no-repeat right 5px top 55%; padding-right: 30px; color: black;';
                    } ?>">
                <option <?php if ($assetPositionNew === 'head') { echo 'selected="selected"'; } ?>
                        value="<?php if ($assetPosition === 'head') { echo 'initial'; } else { echo 'head'; } ?>">
                    &lt;HEAD&gt; <?php if ($assetPosition === 'head') { ?>* initial<?php } ?>
                </option>
                <option <?php if ($assetPositionNew === 'body') { echo 'selected="selected"'; } ?>
                        value="<?php if ($assetPosition === 'body') { echo 'initial'; } else { echo 'body'; } ?>">
                    &lt;BODY&gt; <?php if ($assetPosition === 'body') { ?>* initial<?php } ?>
                </option>
            </select>
            <small>* applies site-wide</small>
        </div>
        <?php
    }
}

ob_start();

if ($assetType === 'scripts') {
    /*
     * [SCRIPTS]
     */
    wpacuPrintManageAssetPositionArea($assetType, $assetPosition, $assetPositionNew, $data);
    /*
     * [/SCRIPTS]
	 */
} else {
    /*
     * [STYLES]
     */
    if ($assetHandleHasSrc) {
        wpacuPrintManageAssetPositionArea($assetType, $assetPosition, $assetPositionNew, $data);
    } else {
	    if (isset($data['row']['obj']->extra->after) && ! empty($data['row']['obj']->extra->after)) {
            $noSrcLoadedIn = __('This inline CSS can be viewed using the "Show/Hide" button below and it is loaded in:', 'wp-asset-clean-up');
        } else {
            $noSrcLoadedIn = __( 'This handle is not for external stylesheet (most likely inline CSS) and it is loaded in:', 'wp-asset-clean-up' );
        }

        echo esc_html($noSrcLoadedIn) . ' '. (($assetPosition === 'head') ? 'HEAD' : 'BODY');
    }
    /*
     * [/STYLES]
     */
}

$htmlChoosePosition = ob_get_clean();

if ( isset($data['row']['obj']->position) && $data['row']['obj']->position !== '') {
    $extraInfo[] = $htmlChoosePosition;
}
