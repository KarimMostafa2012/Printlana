<?php
use WpAssetCleanUp\HardcodedAssets;
use WpAssetCleanUp\Info;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\ObjectCache;
use WpAssetCleanUp\Sorting;

if (! isset($data)) {
	exit; // no direct access
}

$totalFoundHardcodedTags = 0;
$hardcodedTags = $data['all']['hardcoded'];

$contentWithinConditionalComments = ObjectCache::wpacu_cache_get('wpacu_hardcoded_content_within_conditional_comments');

$totalFoundHardcodedTags  = isset($hardcodedTags['link_and_style_tags']) ? count($hardcodedTags['link_and_style_tags']) : 0;
$totalFoundHardcodedTags += isset($hardcodedTags['script_src_or_inline_and_noscript_inline_tags'])
                            ? count($hardcodedTags['script_src_or_inline_and_noscript_inline_tags']) : 0;

if ($totalFoundHardcodedTags === 0) {
	return; // Don't print anything if there are no hardcoded tags available
}

$allPlugins            = get_plugins();
$allThemes             = wp_get_themes();
$allActivePluginsIcons = Misc::getAllActivePluginsIcons();
$handlesInfo           = Main::getHandlesInfo();

// Fetch all output rows under an array
$hardcodedTagsOutputList = array('plugins' => array(), 'themes' => array(), 'core' => array(), 'external' => array(), 'misc' => array(), 'other' => array());

$totalHardcodedTags = 0;
$totalHardcodedTagsViaLocation = array('plugins' => 0, 'themes' => 0, 'core' => 0, 'external' => 0, 'misc' => 0, 'other' => 0);

foreach ( $hardcodedTags as $targetKey => $listAssets) {
    if ( ! in_array($targetKey, array('link_and_style_tags', 'script_src_or_inline_and_noscript_inline_tags')) ) {
        // Go through the tags only; other information should not be included in the loop
        continue;
    }

    foreach ( $listAssets as $indexNo => $tagOutput ) {
        $contentUniqueStr = HardcodedAssets::determineHardcodedAssetSha1($tagOutput);

        $assetType = '';
        $templateRowOutput = ''; // default (will be updated in the inclusions)

        if ($targetKey === 'link_and_style_tags') {
            $assetType = 'styles';
            include __DIR__ . '/_assets-hardcoded-style-single-row.php';
        } elseif ($targetKey === 'script_src_or_inline_and_noscript_inline_tags') {
            $assetType = 'scripts';
            include __DIR__ . '/_assets-hardcoded-script-single-row.php';
        }

        if (isset($dataRowObj)) {
            $dataRowObj = Sorting::appendLocationToAsset($dataRowObj, $assetType, 'hardcoded');

            if ($templateRowOutput !== '' && $assetType !== '' && isset($dataRowObj->locationMain, $dataRowObj->locationChild)) {
                $locationMain  = $dataRowObj->locationMain;
                $locationChild = $dataRowObj->locationChild;

                $offset = $hardcodedTags['offset'][$targetKey][$indexNo];

                if ($locationChild !== 'none') {
                    $hardcodedTagsOutputList[$locationMain][$locationChild][$offset] = $templateRowOutput;
                } else {
                    $hardcodedTagsOutputList[$locationMain][$offset] = $templateRowOutput;
                }

                $totalHardcodedTags++;
            }
        }
    }
}

$hardcodedTagsOutputList = Misc::filterList($hardcodedTagsOutputList);

$hardcodedTagsOutputListKeys   = array_keys($hardcodedTagsOutputList);
$hardcodedTagsLastMainLocation = end($hardcodedTagsOutputListKeys);

$afterHardcodedTitle = ' &#10141; Total: '. $totalHardcodedTags;
$afterHardcodedTitle .= '';

if (isset($data['print_outer_html']) && $data['print_outer_html']) {
    ?>
<div class="wpacu-assets-collapsible-wrap wpacu-wrap-area wpacu-by-location wpacu-hardcoded">
    <a class="wpacu-assets-collapsible wpacu-assets-collapsible-active" href="#" style="padding: 15px 15px 15px 44px;">
        <span class="dashicons dashicons-code-standards"></span> Hardcoded (non-enqueued) Styles &amp; Scripts<?php echo $afterHardcodedTitle; ?>
    </a>
    <div class="wpacu-assets-collapsible-content" style="max-height: inherit;">
<?php } ?>
        <div style="padding: 0;">
            <div style="margin: 15px 0 0; padding: 0 10px;">
                <p><span style="color: #0073aa;" class="dashicons dashicons-info"></span> The following tags are NOT LOADED via the recommended <a target="_blank"
                                                                     href="https://developer.wordpress.org/reference/hooks/wp_enqueue_scripts/">wp_enqueue_scripts()</a>
                action hook (despite the name, it is used for enqueuing both scripts and styles) which is the proper one to use when enqueuing scripts and styles that are meant to appear on
                the front end. The standard functions that are used inside the hook to do an enqueuing are: <a target="_blank"
                                                                                                               href="https://developer.wordpress.org/reference/functions/wp_enqueue_style/">wp_enqueue_style()</a>,
	            <a target="_blank" href="https://codex.wordpress.org/Function_Reference/wp_add_inline_style">wp_add_inline_style()</a>,
	            <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_enqueue_script/">wp_enqueue_script()</a>
	            &amp; <a target="_blank"
	                     href="https://developer.wordpress.org/reference/functions/wp_add_inline_script/">wp_add_inline_script()</a>. The tags could have been added via editing the PHP code (not using the right standard functions), directly inside posts content, widgets or via plugins such as "Insert Headers and Footers", "Head, Footer and Post Injections", etc. Be careful when unloading any of these tags as they might be related to Google Analytics/Google Ads, StatCounter, Facebook Pixel, etc.
                </p>
            </div>
			<?php
			foreach ($hardcodedTagsOutputList as $locationMain => $locationMainValues) {
                if ( $locationMain === 'plugins' && ! empty($locationMainValues) ) {
                    $totalTagsForTarget = count($locationMainValues, COUNT_RECURSIVE) - count($locationMainValues);
                } else {
                    $totalTagsForTarget = count($locationMainValues);
                }

                if ($locationMain === 'plugins') {
                ?>
                    <h3 style="margin-top: 30px;" class="wpacu-title">&#8600; Total Plugins' Assets: <?php echo $totalTagsForTarget; ?></h3>
                    <?php
                    foreach ($locationMainValues as $pluginMainDir => $outputRows) {
                        $totalPluginAssets = count($outputRows);
                        $locationChildText = Info::getPluginInfo( $pluginMainDir, $allPlugins, $allActivePluginsIcons );
                    ?>
                    <div style="margin: 0 0 20px; padding: 0 16px;">
                        <div data-wpacu-plugin="<?php echo esc_attr($pluginMainDir); ?>"
                             data-wpacu-area="hardcoded_<?php echo md5($pluginMainDir); ?>"
                             class="wpacu-location-child-area wpacu-area-expanded">
                            <div class="wpacu-area-title wpacu-plugin-icon">
                                <?php echo wp_kses($locationChildText, array('div' => array('class' => array(), 'style' => array()), 'span' => array('class' => array()))); ?>
                                <span style="font-weight: 200;">/</span>
                                <span style="font-weight: 400;"><?php echo $totalPluginAssets; ?></span> asset<?php echo ($totalPluginAssets > 1) ? 's' : ''; ?>
                            </div>
                            <div class="wpacu-area-toggle-all-assets" style="top: 20px;">
                                <a class="wpacu-area-contract-all-assets wpacu_area_handles_row_expand_contract"
                                   data-wpacu-area="hardcoded_<?php echo md5($pluginMainDir); ?>" href="#">Contract</a>
                                |
                                <a class="wpacu-area-expand-all-assets wpacu_area_handles_row_expand_contract"
                                   data-wpacu-area="hardcoded_<?php echo md5($pluginMainDir); ?>" href="#">Expand</a>
                                All
                            </div>
                        </div>
                        <table class="wpacu_list_table wpacu_striped"
                               data-wpacu-area="hardcoded_<?php echo md5($pluginMainDir); ?>">
                            <tbody>
                            <?php
                            foreach ( $outputRows as $outputRow ) {
                                echo $outputRow."\n";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    }
                } elseif ($locationMain === 'themes') { ?>
                    <h3 style="margin-top: 30px;"
                        class="wpacu-title">&#8600; Total Themes' Assets: <?php echo $totalTagsForTarget; ?></h3>
                    <?php
                    foreach ($locationMainValues as $themeMainDir => $outputRows) {
                        $totalThemeAssets        = count($outputRows);
                        $locationChildThemeArray = Info::getThemeInfo($themeMainDir, $allThemes);
                        $locationChildText       = $locationChildThemeArray['output'];
                        ?>
                        <div style="padding: 0 16px;">
                            <div data-wpacu-area="hardcoded_<?php echo md5($themeMainDir); ?>"
                                 class="wpacu-location-child-area wpacu-area-expanded">
                                <div class="wpacu-area-title <?php if ($locationChildThemeArray['has_icon']) { echo 'wpacu-theme-has-icon'; } else { echo 'wpacu-theme-no-icon'; } ?>">
                                    <?php echo wp_kses($locationChildText, array('div' => array('class' => array(), 'style' => array()), 'span' => array('class' => array()))); ?>
                                    <span style="font-weight: 200;">/</span>
                                    <span style="font-weight: 400;"><?php echo $totalThemeAssets; ?></span> asset<?php echo ($totalThemeAssets > 1) ? 's' : ''; ?>
                                </div>
                                <div class="wpacu-area-toggle-all-assets" style="top: 20px;">
                                    <a class="wpacu-area-contract-all-assets wpacu_area_handles_row_expand_contract"
                                       data-wpacu-area="hardcoded_<?php echo md5($themeMainDir); ?>" href="#">Contract</a>
                                    |
                                    <a class="wpacu-area-expand-all-assets wpacu_area_handles_row_expand_contract"
                                       data-wpacu-area="hardcoded_<?php echo md5($themeMainDir); ?>" href="#">Expand</a>
                                    All
                                </div>
                            </div>
                            <table class="wpacu_list_table wpacu_striped"
                                   data-wpacu-area="hardcoded_<?php echo md5($themeMainDir); ?>">
                                <tbody>
                                <?php
                                foreach ( $outputRows as $outputRow ) {
                                    echo $outputRow."\n";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    }
                } elseif (in_array($locationMain, array('external', 'misc', 'other'))) {
                    if ($locationMain === 'external') {
                        $hardcodedAreaTitle = '&#8600; Total Externally Loaded Assets: '.$totalTagsForTarget;
                    } elseif ($locationMain === 'misc') {
                        $hardcodedAreaTitle = '&#8600; Miscellaneous Loaded Assets: '.$totalTagsForTarget;
                    } else {
                        $hardcodedAreaTitle = '&#8600; Other Loaded Assets: '.$totalTagsForTarget;
                    }
                ?>
                    <h3 style="margin-top: 30px;"
                        class="wpacu-title"><?php echo $hardcodedAreaTitle; ?></h3>
                    <div style="padding: 0 16px;">
                        <div data-wpacu-area="hardcoded_external"
                             class="wpacu-location-child-area wpacu-area-expanded">
                            <div class="wpacu-area-toggle-all-assets" style="top: -50px; right: 2px;">
                                <a class="wpacu-area-contract-all-assets wpacu_area_handles_row_expand_contract"
                                   data-wpacu-area="hardcoded_<?php echo $locationMain; ?>" href="#">Contract</a>
                                |
                                <a class="wpacu-area-expand-all-assets wpacu_area_handles_row_expand_contract"
                                   data-wpacu-area="hardcoded_<?php echo $locationMain; ?>" href="#">Expand</a>
                                All
                            </div>
                        </div>
                        <table class="wpacu_list_table wpacu_striped"
                               data-wpacu-area="hardcoded_<?php echo $locationMain; ?>">
                            <tbody>
                            <?php
                            foreach ( $locationMainValues as $outputRow ) {
                                echo $outputRow."\n";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }

                if ($hardcodedTagsLastMainLocation === $locationMain) {
                    ?>
                    <div style="margin: 18px 0 0;"></div>
                    <?php
                }
            }
			?>
        </div>
<?php if (isset($data['print_outer_html']) && $data['print_outer_html']) { ?>
    </div>
</div>
<?php }
