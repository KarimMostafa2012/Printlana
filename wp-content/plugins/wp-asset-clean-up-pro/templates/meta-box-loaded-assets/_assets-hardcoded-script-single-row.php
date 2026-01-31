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
 * Hardcoded SCRIPT (with "src" attribute & inline) or Hardcoded NOSCRIPT inline tags
*/
$templateRowOutput = '';

$generatedHandle = $srcHrefOriginal = false;

if ( stripos( $tagOutput, '<script' ) === 0 ) {
    if ( preg_match( '# src(\s+|)=(\s+|)#Umi', $tagOutput ) ) {
        $srcHrefOriginal = Misc::getValueFromTag( $tagOutput, '', 'dom_with_fallback' );
    }

    if ( $srcHrefOriginal ) {
        // No room for any mistakes, do not print the cached files
        if ( strpos( $srcHrefOriginal,
                OptimizeCommon::getRelPathPluginCacheDir() ) !== false ) {
            return;
        }

        $handlePrefix    = HardcodedAssetsPro::$handleScriptSrcPrefix;
        $generatedHandle = $handlePrefix . $contentUniqueStr;
    }

    // Is it a SCRIPT without "src" attribute? Then it's an inline one
    if ( ! $generatedHandle ) {
        $handlePrefix    = HardcodedAssetsPro::$handleScriptInlinePrefix;
        $generatedHandle = $handlePrefix . $contentUniqueStr;
    }
} elseif ( stripos( $tagOutput, '<noscript' ) === 0 ) {
    $handlePrefix    = HardcodedAssetsPro::$handleNoScriptInlinePrefix;
    $generatedHandle = $handlePrefix . $contentUniqueStr;
}

if ( ! $generatedHandle ) {
    return;
}

$dataRowObj = (object)array(
    'handle'        => $generatedHandle,
    'tag_output'    => $tagOutput
);

$dataRowObj->inside_conditional_comment = HardcodedAssets::isWithinConditionalComment($tagOutput, $contentWithinConditionalComments);

$dataRowObj->position = HardcodedAssets::getTagPositionHeadOrBody($indexNo, $hardcodedTags['positions'][$targetKey]);
$dataRowObj = apply_filters('wpacu_pro_get_position_new', $dataRowObj, 'scripts');

if ($srcHrefOriginal) {
    $dataRowObj->src = $srcHrefOriginal;
}

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

// Determine source href (starting with '/' but not starting with '//')
if ($srcHrefOriginal) {
    if ( strpos( $srcHrefOriginal, '/' ) === 0 && strpos( $srcHrefOriginal, '//' ) !== 0 ) {
        $dataRowObj->srcHref = get_site_url() . $srcHrefOriginal;
    } else {
        $dataRowObj->srcHref = $srcHrefOriginal;
    }
}

// [wpacu_pro]
HardcodedAssetsPro::maybeUpdateOldGeneratedHandleNameWithTheNewOne($tagOutput, $handlePrefix, $generatedHandle, $handlesInfo);

$dataRowObj->handles_maybe = HardcodedAssetsPro::getPossibleOlderHandlesForHardcodedTag($tagOutput, $handlePrefix);
$dataHH = HardcodedAssetsPro::wpacuGenerateHardcodedAssetData( $dataRowObj, $data, 'scripts' );
// [/wpacu_pro]

$parseTemplate = Main::instance()->parseTemplate(
    '/meta-box-loaded-assets/_common/_asset-single-row-hardcoded',
    $dataHH,
    false,
    true
);

$templateRowOutput = $parseTemplate['output'];
$returnData = $parseTemplate['data'];
