<?php
// no direct access
use WpAssetCleanUp\Misc;

if (! isset($data)) {
	exit;
}

$listAreaStatus = $data['plugin_settings']['assets_list_layout_areas_status'];

/*
* ------------------------------
* [START] STYLES & SCRIPTS LIST
* ------------------------------
*/
require_once __DIR__.'/_assets-top-area.php';
?>
<div class="wpacu-assets-collapsible-wrap wpacu-wrap-all">
    <a style="padding: 15px;" class="wpacu-assets-collapsible <?php if ($listAreaStatus !== 'contracted') { ?>wpacu-assets-collapsible-active<?php } ?>" href="#wpacu-assets-collapsible-content">
        <?php esc_html_e('Styles (.css files) &amp; Scripts (.js files)', 'wp-asset-clean-up'); ?> &#10141; <?php esc_html_e('Total enqueued (+ core files)', 'wp-asset-clean-up'); ?>: <?php echo (int)$data['total_styles'] + (int)$data['total_scripts']; ?> (Styles: <?php echo (int)$data['total_styles']; ?>, Scripts: <?php echo (int)$data['total_scripts']; ?>)
    </a>

    <div id="wpacu-assets-collapsible-content"
         class="wpacu-assets-collapsible-content <?php if ($listAreaStatus !== 'contracted') { ?>wpacu-open<?php } ?>">
	    <?php if (! empty($data['all']['styles']) || ! empty($data['all']['scripts'])) { ?>
            <div class="wpacu-area-toggle-all-assets wpacu-right">
                <a class="wpacu-area-contract-all-assets wpacu_area_handles_row_expand_contract"
                   data-wpacu-area="all_assets" href="#">Contract</a>
                |
                <a class="wpacu-area-expand-all-assets wpacu_area_handles_row_expand_contract"
                   data-wpacu-area="all_assets" href="#">Expand</a>
                All Assets
            </div>
	    <?php } ?>
        <div>
            <?php
            if (! empty($data['all']['styles']) || ! empty($data['all']['scripts'])) {
                ?>
                <table class="wpacu_list_table wpacu_widefat wpacu_striped" data-wpacu-area="all_assets">
                    <tbody>
                    <?php
                    $data['view_all'] = true;
                    $data['rows_build_array'] = true;
                    $data['rows_assets'] = array();

                    require_once __DIR__.'/_asset-style-rows.php';
                    require_once __DIR__.'/_asset-script-rows.php';

                    if (! empty($data['rows_assets'])) {
                        ksort($data['rows_assets']);

                        foreach ($data['rows_assets'] as $assetRow) {
                            echo Misc::stripIrrelevantHtmlTags($assetRow)."\n";
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<?php
/*
 * -----------------------------
 * [END] STYLES & SCRIPTS LIST
 * -----------------------------
 */

include_once __DIR__ . '/_view-common-footer.php';
