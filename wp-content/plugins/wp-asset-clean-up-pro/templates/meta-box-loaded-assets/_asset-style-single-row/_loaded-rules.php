<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-style-single-row.php
*/

if (! isset($data)) {
	exit; // no direct access
}

$childHandles = isset($data['all_deps']['parent_to_child']['styles'][$data['row']['obj']->handle]) ? $data['all_deps']['parent_to_child']['styles'][$data['row']['obj']->handle] : array();

$handleAllStatuses = array();

if (! empty($childHandles)) {
	$handleAllStatuses[] = 'is_parent';
}

if (isset($data['row']['obj']->deps) && ! empty($data['row']['obj']->deps)) {
	$handleAllStatuses[] = 'is_child';
}

if (empty($handleAllStatuses)) {
    $handleAllStatuses[] = 'is_independent';
}

// The media attribute is different from "all"
$linkHasDistinctiveMediaAttr = isset($data['row']['obj']->args) && $data['row']['obj']->args && $data['row']['obj']->args !== 'all';

// [wpacu_pro]
// Is "parent" of other "children"? Show an alert to the admin!
$showMatchMediaAlertForParentCss = in_array('is_parent', $handleAllStatuses);
// [/wpacu_pro]
?>

<!-- [wpacu_pro] -->
<?php
if (isset($data['row']['obj']->src) && trim($data['row']['obj']->src) !== '') {
?>
    <div style="margin: 0 0 15px;">
        <?php
        $matchMediaLoadArray = (isset($data['media_queries_load']['styles'][$data['row']['obj']->handle]) && $data['media_queries_load']['styles'][$data['row']['obj']->handle])
            ? $data['media_queries_load']['styles'][$data['row']['obj']->handle]
            : array();

        $matchMediaLoadStatus      = isset($matchMediaLoadArray['enable']) ? (int)$matchMediaLoadArray['enable'] : false;
        $matchMediaLoadCustomValue = (isset($matchMediaLoadArray['value']) && $matchMediaLoadArray['value']) ? $matchMediaLoadArray['value'] : '';
        $matchMediaLoadCustomValueToPrint = '';

        // Custom one set by the user
        if ( $matchMediaLoadStatus === 1 ) {
            $matchMediaLoadCustomValueToPrint = $matchMediaLoadCustomValue;
        }

        // Existing one: "only if its current media query is matched"
        if ( $matchMediaLoadStatus === 2 ) {
            // The value in the database is "current" (reference for developers to scan the database content if required)

            if ( ! $linkHasDistinctiveMediaAttr ) {
                // The CSS file had a distinctive "media", but afterward it was set to "all"
                // In this case, make it not enabled, so it will load for any screen
                $matchMediaLoadStatus = false;
            }

            $matchMediaLoadCustomValueToPrint = '';
        }

        $wpacuDataForSelectId   = 'wpacu_handle_media_query_load_select_style_'.$data['row']['obj']->handle;
        $wpacuDataForTextAreaId = 'wpacu_handle_media_query_load_textarea_style_'.$data['row']['obj']->handle;

        if ( $matchMediaLoadCustomValue && in_array($matchMediaLoadStatus, array(1, 2)) ) { $data['row']['at_least_one_rule_set'] = true; }
        ?>
        Make the browser download the file&nbsp;
            <select id="<?php echo esc_attr($wpacuDataForSelectId); ?>"
                    data-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
                    data-wpacu-input="media-query-select"
                    <?php
                    // [wpacu_pro]
                    if ($showMatchMediaAlertForParentCss) {
                        echo ' data-wpacu-show-parent-alert ';
                    }
                    // [/wpacu_pro]
                    ?>
                    name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[styles][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][media_query_load][enable]"
                    class="wpacu-screen-size-load wpacu-for-style">
                <option <?php if ( ! $matchMediaLoadStatus ) { echo 'selected="selected"'; } ?> value="">on any screen size (default)</option>

                <?php if ( $linkHasDistinctiveMediaAttr ) { ?>
                    <option <?php if ( $matchMediaLoadStatus === 2 ) { echo 'selected="selected"'; } ?> value="2">only if its current media query is matched</option>
                <?php } ?>

                <option <?php if ( $matchMediaLoadStatus === 1 ) { echo ' selected="selected" '; } ?> value="1">only if this media query is matched:</option>
            </select>

        <div data-style-handle="<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>"
             class="wpacu-handle-media-queries-load-field <?php if ($matchMediaLoadStatus === 1) { echo 'wpacu-is-visible'; } ?> wpacu-fade-in">
                <textarea id="<?php echo esc_attr($wpacuDataForTextAreaId); ?>"
                  style="min-height: 40px;"
                  class="wpacu-handle-media-queries-load-field-input"
                  data-wpacu-adapt-height="1"
                  data-wpacu-is-empty-on-page-load="<?php echo ( ! $matchMediaLoadCustomValueToPrint ) ? 'true' : 'false'; ?>"
                  <?php if ( ! $matchMediaLoadCustomValueToPrint ) { echo 'disabled="disabled"'; } ?>
                  name="<?php echo WPACU_FORM_ASSETS_POST_KEY; ?>[styles][<?php echo htmlentities(esc_attr($data['row']['obj']->handle), ENT_QUOTES); ?>][media_query_load][value]"><?php echo esc_textarea($matchMediaLoadCustomValueToPrint); ?></textarea> &nbsp;<small style="vertical-align: top;">e.g. <em style="vertical-align: top;">screen and (max-width: 767px)</em></small>
            <div class="wpacu_clearfix"></div>
    </div>
    <div class="wpacu-helper-area"><a style="text-decoration: none; color: inherit;" target="_blank" href="https://assetcleanup.com/docs/?p=1023"><span class="dashicons dashicons-editor-help"></span></a></div>
    <div class="wpacu_clearfix"></div>
<?php
}
?>
<!-- [/wpacu_pro] -->
