<?php
use WpAssetCleanUp\HardcodedAssets;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\ObjectCache;

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

$handlesInfo = Main::getHandlesInfo();

// Fetch all output rows under an array
$hardcodedTagsOutputList = array('with_rules' => array(), 'with_no_rules' => array());

$totalHardcodedTags = 0;
$totalHardcodedTagsViaRules = array('with_rules' => 0, 'with_no_rules' => 0);

foreach ( $hardcodedTags as $targetKey => $listAssets) {
    if ( ! in_array($targetKey, array('link_and_style_tags', 'script_src_or_inline_and_noscript_inline_tags')) ) {
        // Go through the tags only; other information should not be included in the loop
        continue;
    }

    foreach ( $listAssets as $indexNo => $tagOutput ) {
        $contentUniqueStr = HardcodedAssets::determineHardcodedAssetSha1($tagOutput);

        $assetType = '';

        // default (will be updated in the inclusions)
        $templateRowOutput = '';
        $returnData        = array();

        if ($targetKey === 'link_and_style_tags') {
            $assetType = 'styles';
            include __DIR__ . '/_assets-hardcoded-style-single-row.php';
        } elseif ($targetKey === 'script_src_or_inline_and_noscript_inline_tags') {
            $assetType = 'scripts';
            include __DIR__ . '/_assets-hardcoded-script-single-row.php';
        }

        if ($templateRowOutput !== '' && $assetType !== '') {
            $rulesStatus = isset($returnData['row']['at_least_one_rule_set']) && $returnData['row']['at_least_one_rule_set'] ? 'with_rules' : 'with_no_rules';

            $offset = $hardcodedTags['offset'][$targetKey][$indexNo];

            $hardcodedTagsOutputList[$rulesStatus][$offset] = $templateRowOutput;

            $totalHardcodedTagsViaRules[$rulesStatus]++;
            $totalHardcodedTags++;
        }
    }
}

if ( ! empty($hardcodedTagsOutputList['head']) ) {
    ksort($hardcodedTagsOutputList['head']);
}

if ( ! empty($hardcodedTagsOutputList['body']) ) {
    ksort($hardcodedTagsOutputList['body']);
}

$afterHardcodedTitle = ' &#10141; Total: '. $totalHardcodedTags;
$afterHardcodedTitle .= ' | With Rules: '.$totalHardcodedTagsViaRules['with_rules'].' | With No Rules: '.$totalHardcodedTagsViaRules['with_no_rules'];

if (isset($data['print_outer_html']) && $data['print_outer_html']) { ?>
<div class="wpacu-assets-collapsible-wrap wpacu-wrap-area wpacu-hardcoded">
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
			foreach ($hardcodedTagsOutputList as $rulesStatus => $outputRows) {
                $totalTagsForTarget = count( $outputRows );
                ?>
					<div>
						<div class="wpacu-content-title wpacu-has-toggle-all-assets">
							<h3 class="wpacu-title">
								<?php if ($rulesStatus === 'with_rules') { ?><span class="dashicons dashicons-star-filled"></span> With Rules (CSS &amp; JavaScript) &#10141; Total: <?php echo $totalTagsForTarget; ?><?php } ?>
								<?php if ($rulesStatus === 'with_no_rules') { ?><span class="dashicons dashicons-star-empty"></span> With No Rules (CSS &amp; JavaScript) &#10141; Total: <?php echo $totalTagsForTarget; ?><?php } ?>
							</h3>

                            <?php if ($totalTagsForTarget > 0) { ?>
                                <div class="wpacu-area-toggle-all-assets wpacu-absolute">
                                    <a class="wpacu-area-contract-all-assets wpacu_area_handles_row_expand_contract"
                                       data-wpacu-area="hardcoded_<?php echo $rulesStatus; ?>" href="#">Contract</a>
                                    |
                                    <a class="wpacu-area-expand-all-assets wpacu_area_handles_row_expand_contract"
                                       data-wpacu-area="hardcoded_<?php echo $rulesStatus; ?>" href="#">Expand</a>
                                    All Assets
                                </div>
                            <?php } ?>
						</div>
                        <?php if ($totalTagsForTarget > 0) { ?>
                            <table style="padding: 0 10px;"
                                   class="wpacu_list_table wpacu_striped"
                                   data-wpacu-area="hardcoded_<?php echo $rulesStatus; ?>">
                                <tbody>
                                <?php
                                foreach ( $outputRows as $outputRow ) {
                                    echo $outputRow."\n";
                                }
                                ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <div style="padding: 0 10px;">
                                <?php if ($rulesStatus === 'with_rules') { ?>
                                    <p style="margin-top: 0;">There is no hardcoded asset with any rules set.</p>
                                <?php
                                } else { ?>
                                    <p style="margin-top: 0;">All the hardcoded assets have rules assigned to them.</p>
                                <?php
                                }
                                ?>
                            </div>
                        <?php } ?>

                        <?php if ($rulesStatus === 'with_rules') { ?>
                            <hr style="margin: 12px 0 10px;" />
                        <?php } else { ?>
                            <div style="margin: 12px 0;"></div>
                        <?php } ?>
					</div>
                <?php
				}
			?>
        </div>
<?php if (isset($data['print_outer_html']) && $data['print_outer_html']) { ?>
    </div>
</div>
<?php }
