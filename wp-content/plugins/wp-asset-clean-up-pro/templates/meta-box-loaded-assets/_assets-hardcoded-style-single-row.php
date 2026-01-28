<?php
use WpAssetCleanUp\HardcodedAssets;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUpPro\HardcodedAssetsPro;

if ( ! isset($data, $targetKey, $tagOutput, $contentUniqueStr, $contentWithinConditionalComments, $indexNo, $hardcodedTags, $handlesInfo)) {
    exit; // no direct access
}

/*
* Hardcoded LINK (stylesheet) &amp; STYLE tags
*/
$templateRowOutput = '';

// For LINK ("stylesheet")
if ( stripos( $tagOutput, '<link ' ) === 0 ) {
    $generatedHandle  = HardcodedAssetsPro::$handleLinkPrefix . $contentUniqueStr;
    $linkHrefOriginal = '';

    // could be href="value_here" or href  = "value_here" (with extra spaces) / make sure it matches
    if ( preg_match('# href(\s+|)=(\s+|)#Umi', $tagOutput) ) {
        $linkHrefOriginal = Misc::getValueFromTag($tagOutput);

        // No room for any mistakes, do not print the cached files
        if (strpos($linkHrefOriginal, OptimizeCommon::getRelPathPluginCacheDir()) !== false) {
            return;
        }
    }

    $dataRowObj = (object) array(
        'handle'        => $generatedHandle,
        'src'           => $linkHrefOriginal,
        'tag_output'    => $tagOutput
    );

    $dataRowObj->inside_conditional_comment = HardcodedAssets::isWithinConditionalComment($tagOutput, $contentWithinConditionalComments);

    $dataRowObj->position = HardcodedAssets::getTagPositionHeadOrBody($indexNo, $hardcodedTags['positions'][$targetKey]);
    $dataRowObj = apply_filters('wpacu_pro_get_position_new', $dataRowObj, 'styles');

    // [wpacu_pro]
    // The $tagOutput will be minified ('output_min' key) only after submit (to save resources)
    $wpacuHardcodedInfoToStoreAfterSubmit = array(
        'handle'     => $generatedHandle,
        'output'     => $tagOutput
    );

    if ($dataRowObj->inside_conditional_comment) {
        $wpacuHardcodedInfoToStoreAfterSubmit['cond_comm'] = $dataRowObj->inside_conditional_comment;
    }

    $dataRowObj->hardcoded_data = base64_encode(wp_json_encode($wpacuHardcodedInfoToStoreAfterSubmit));
    // [/wpacu_pro]

    // Determine source href starting with '/' but not starting with '//'
    if (strpos($linkHrefOriginal, '/') === 0 && strpos($linkHrefOriginal, '//') !== 0) {
        $dataRowObj->srcHref = get_site_url() . $linkHrefOriginal;
    } else {
        $dataRowObj->srcHref = $linkHrefOriginal;
    }

    // [wpacu_pro]
    HardcodedAssetsPro::maybeUpdateOldGeneratedHandleNameWithTheNewOne($tagOutput, HardcodedAssetsPro::$handleLinkPrefix, $generatedHandle, $handlesInfo);

    $dataRowObj->handles_maybe = HardcodedAssetsPro::getPossibleOlderHandlesForHardcodedTag($tagOutput, HardcodedAssetsPro::$handleLinkPrefix);
    $dataHH = HardcodedAssetsPro::wpacuGenerateHardcodedAssetData( $dataRowObj, $data, 'styles' );
    // [/wpacu_pro]
}

// For STYLE (inline)
elseif ( stripos( $tagOutput, '<style' ) === 0 ) {
    $generatedHandle  = HardcodedAssetsPro::$handleStylePrefix . $contentUniqueStr;

    $dataRowObj = (object) array(
        'handle'        => $generatedHandle,
        'src'           => false,
        'tag_output'    => $tagOutput
    );

    $dataRowObj->inside_conditional_comment = HardcodedAssets::isWithinConditionalComment($tagOutput, $contentWithinConditionalComments);

    $dataRowObj->position = HardcodedAssets::getTagPositionHeadOrBody($indexNo, $hardcodedTags['positions'][$targetKey]);
    $dataRowObj = apply_filters('wpacu_pro_get_position_new', $dataRowObj, 'styles');

    // [wpacu_pro]
    // The $tagOutput will be minified ('output_min' key) only after submit (to save resources)
    $wpacuHardcodedInfoToStoreAfterSubmit = array(
        'handle'     => $generatedHandle,
        'output'     => $tagOutput
    );

    if ($dataRowObj->inside_conditional_comment) {
        $wpacuHardcodedInfoToStoreAfterSubmit['cond_comm'] = $dataRowObj->inside_conditional_comment;
    }

    $dataRowObj->hardcoded_data = base64_encode(wp_json_encode($wpacuHardcodedInfoToStoreAfterSubmit));

    HardcodedAssetsPro::maybeUpdateOldGeneratedHandleNameWithTheNewOne($tagOutput, HardcodedAssetsPro::$handleStylePrefix, $generatedHandle, $handlesInfo);

    $dataRowObj->handles_maybe = HardcodedAssetsPro::getPossibleOlderHandlesForHardcodedTag($tagOutput, HardcodedAssetsPro::$handleStylePrefix);
    $dataHH = HardcodedAssetsPro::wpacuGenerateHardcodedAssetData( $dataRowObj, $data, 'styles' );
    // [/wpacu_pro]
}

if ( ! empty($dataHH) ) {
    $parseTemplate = Main::instance()->parseTemplate(
        '/meta-box-loaded-assets/_common/_asset-single-row-hardcoded',
        $dataHH,
        false,
        true
    );

    $templateRowOutput = $parseTemplate['output'];
    $returnData        = $parseTemplate['data'];
}
