<?php
namespace WpAssetCleanUpPro\OptimiseAssets;

use WpAssetCleanUp\Misc;
use WpAssetCleanUp\ObjectCache;

/**
 * Class MatchMediaLoadPro
 * @package WpAssetCleanUp\OptimiseAssets
 */
class MatchMediaLoadPro
{
	/**
	 * @param $htmlSource string
     * @param $matchesSourcesFromTags array
     * @param string
	 *
	 * @return string|string[]
	 */
	public static function alterHtmlSourceForMediaQueriesLoad($htmlSource, $matchesSourcesFromTags, $assetType)
	{
        if ($assetType === 'styles') {
            $linkTagsToFallback = array();
        }

		if (! empty($matchesSourcesFromTags)) {
			foreach ($matchesSourcesFromTags as $matchedValues) {
                $matchedTag = $matchedValues[0];
				$newTag = self::maybeAlterToMatchMediaTag($matchedValues[0], $assetType);

				if ($matchedTag !== $newTag) {
					$htmlSource = str_replace( $matchedTag, $newTag, $htmlSource );

                    if ($assetType === 'styles') {
                        // Use the <NOSCRIPT> tags as a fallback for LINK tags in case JavaScript is disabled in the visitor's browser
                        // There's no point in adding any for the SCRIPT tags as they will not load at all anyway
                        $newFallbackTag = str_replace('data-wpacu-apply-media-query=', 'data-wpacu-applied-media-query=', $matchedTag);
                        $linkTagsToFallback[] = $newFallbackTag;
                    }
				}
			}
		}

        if ( ! empty($linkTagsToFallback) ) {
            ObjectCache::wpacu_cache_add('wpacu_link_tags_fallback', $linkTagsToFallback);
        }

		return $htmlSource;
	}

    /**
     * @param $matchedTag
     * @param $assetType
     *
     * @return mixed|string|void
     */
    public static function maybeAlterToMatchMediaTag($matchedTag, $assetType)
    {
        // Check if the tag has any 'data-wpacu-skip' attribute; if it does, do not alter it
        if (preg_match('#data-wpacu-skip([=>/ ])#i', $matchedTag)) {
            return $matchedTag;
        }

        if ($assetType === 'styles') {
            preg_match_all('#data-wpacu-style-handle=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
            $tagHandle = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';

            // [START] Check if Handle is Eligible For The Feature
            // The handle has to be a "child" or "independent", but not a "parent"
            $allCssDepsParentToChild = self::getAllParentToChildInRelationToMarkedHandles($assetType);
            // [END] Check if Handle is Eligible For The Feature

            if (isset($allCssDepsParentToChild[$tagHandle])) {
                // Has "children", this is not supported yet, and somehow it was added as a rule (or someone tries to hack it)
                return $matchedTag;
            }

            preg_match_all('#data-wpacu-apply-media-query=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
            $mediaQueryValue = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';

            return self::maybeAlterToMatchMedia($tagHandle, $matchedTag, $mediaQueryValue, $assetType);
        }

        if ($assetType === 'scripts') {
            // [START] Check if Handle is Eligible For The Feature
            // The handle has to be a "child" or "independent", but not a "parent"
            $allJsDepsParentToChild = self::getAllParentToChildInRelationToMarkedHandles($assetType);
            // [END] Check if Handle is Eligible For The Feature

            preg_match_all('#data-wpacu-script-handle=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
            $tagHandle = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';

            if (isset($allJsDepsParentToChild[$tagHandle])) {
                // Has "children", this is not supported yet, and somehow it was added as a rule (or someone tries to hack it)
                return $matchedTag;
            }

            preg_match_all('#data-wpacu-apply-media-query=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
            $mediaQueryValue = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';

            $matchedCompleteTag = $matchedTag.'</script>';

            return self::maybeAlterToMatchMedia($tagHandle, $matchedCompleteTag, $mediaQueryValue, $assetType);
        }
    }

	/**
	 * @param $tagHandle
	 * @param $htmlTag
	 * @param $mediaQueryValue
	 * @param $assetType
	 *
	 * @return string
	 */
	public static function maybeAlterToMatchMedia($tagHandle, $htmlTag, $mediaQueryValue, $assetType)
	{
		if ((! $tagHandle) || (! $htmlTag) || (! $mediaQueryValue)) {
			return $htmlTag;
		}

		// Extra check: make sure the targeted handle doesn't have any "children" (independent or has "parents")
		// as there's no support for such handles at this time


		// Check if there are any media queries set (e.g. mobile, desktop, custom ones) for this tag
		// To only load when the media query matches
		if ($assetType === 'styles') {
			$attrToSet = 'wpacu-' . str_replace(array(' '), '_', sanitize_title( $tagHandle ) . '-href');
			$htmlTag   = str_replace( ' href=', ' ' . $attrToSet . '=', $htmlTag );

			$wpacuJsFunc        = str_replace( '-', '_', 'wpacu_' . sanitize_title( $tagHandle ) . '_match_media' );
			$wpacuMatchMediaVar = str_replace( '-', '_', 'wpacu_' . sanitize_title( $tagHandle ) . '_match_media_var' );

			$wpacuHtmlMatchMedia = <<<HTML
<script>
function myFunc(matchMediaVar) {
    if (matchMediaVar.matches) {
        var wpacuHrefAttr = document.querySelectorAll("[{$attrToSet}]")[0].getAttribute('{$attrToSet}');
        document.querySelectorAll("[{$attrToSet}]")[0].setAttribute('href', wpacuHrefAttr);
    }
}
try { var matchMediaVar = window.matchMedia("{$mediaQueryValue}"); myFunc(matchMediaVar); matchMediaVar.addListener(myFunc); }
catch (wpacuError) {
	var wpacuHrefAttr = document.querySelectorAll("[{$attrToSet}]")[0].getAttribute('{$attrToSet}');
    document.querySelectorAll("[{$attrToSet}]")[0].setAttribute('href', wpacuHrefAttr);
}
</script>
HTML;
			$wpacuHtmlMatchMedia = str_replace(
				array( 'myFunc', 'matchMediaVar' ),
				array( $wpacuJsFunc, $wpacuMatchMediaVar ),
				$wpacuHtmlMatchMedia
            );

            $newHtmlTag = str_replace('data-wpacu-apply-media-query=', 'data-wpacu-applied-media-query=', $htmlTag);

			return $newHtmlTag . $wpacuHtmlMatchMedia;
		}

		if ($assetType === 'scripts') {
			$attrToSet = 'wpacu-' . str_replace(array(' '), '_', sanitize_title( $tagHandle ) . '-src');
			$htmlTag   = str_replace( ' src=', ' ' . $attrToSet . '=', $htmlTag );

			$wpacuJsFunc        = str_replace( array('-', ' '), '_', 'wpacu_' . sanitize_title( $tagHandle ) . '_match_media' );
			$wpacuMatchMediaVar = str_replace( array('-', ' '), '_', 'wpacu_' . sanitize_title( $tagHandle ) . '_match_media_var' );

			$wpacuHtmlMatchMedia = <<<HTML
<script>
function myFunc(matchMediaVar) {
    if (matchMediaVar.matches) {
        var wpacuSrcAttr = document.querySelectorAll("[{$attrToSet}]")[0].getAttribute('{$attrToSet}');
        document.querySelectorAll("[{$attrToSet}]")[0].setAttribute('src', wpacuSrcAttr);
    }
}
try { var matchMediaVar = window.matchMedia("{$mediaQueryValue}"); myFunc(matchMediaVar); matchMediaVar.addListener(myFunc); }
catch (wpacuError) {
  	var wpacuHrefAttr = document.querySelectorAll("[{$attrToSet}]")[0].getAttribute('{$attrToSet}');
    document.querySelectorAll("[{$attrToSet}]")[0].setAttribute('href', wpacuHrefAttr);
}
</script>
HTML;
			$wpacuHtmlMatchMedia = str_replace(
				array( 'myFunc', 'matchMediaVar' ),
				array( $wpacuJsFunc, $wpacuMatchMediaVar ),
				$wpacuHtmlMatchMedia
            );

            $newHtmlTag = str_replace('data-wpacu-apply-media-query=', 'data-wpacu-applied-media-query=', $htmlTag);

			return $newHtmlTag . $wpacuHtmlMatchMedia;
		}

		// Finally, return the tag if there were no changes applied
		return $htmlTag;
	}

	/**
	 * If any current handle marked for media query load has any "children", do not alter it
	 *
	 * @param $assetType
	 *
	 * @return array
	 */
	public static function getAllParentToChildInRelationToMarkedHandles($assetType)
	{
        if ($allCssDepsParentToChild = ObjectCache::wpacu_cache_get( 'wpacu_get_deps_parent_to_child_'.$assetType)) {
            return $allCssDepsParentToChild;
        }

		if ($assetType === 'styles') {
			$allCssDepsParentToChild = array();
			$allCssMediaQueriesLoadMarkedHandlesList = ObjectCache::wpacu_cache_get('wpacu_css_media_queries_load_current_page') ?: array();

			global $wp_styles;

			if ( ! empty($wp_styles->registered) ) {
				foreach ( $wp_styles->registered as $assetHandle => $assetObj ) {
					if ( ! empty( $assetObj->deps ) ) {
						foreach ( $assetObj->deps as $dep ) {
							if (isset($wp_styles->done) && in_array($assetHandle, $allCssMediaQueriesLoadMarkedHandlesList) && in_array($assetHandle, $wp_styles->done)) {
								$allCssDepsParentToChild[$dep][] = $assetHandle;
							}
						}
					}
				}
			}

            ObjectCache::wpacu_cache_set( 'wpacu_get_deps_parent_to_child_'.$assetType, $allCssDepsParentToChild );
			return $allCssDepsParentToChild;
		}

		if ($assetType === 'scripts') {
			$allJsDepsParentToChild = array();
			$allJsMediaQueriesLoadMarkedHandlesList = ObjectCache::wpacu_cache_get( 'wpacu_js_media_queries_load_current_page' ) ?: array();

			global $wp_scripts;

			if ( ! empty( $wp_scripts->registered ) ) {
				foreach ( $wp_scripts->registered as $assetHandle => $assetObj ) {
					if ( ! empty( $assetObj->deps ) ) {
						foreach ( $assetObj->deps as $dep ) {
							if ( isset( $wp_scripts->done ) && is_array($wp_scripts->done) && is_array($allJsMediaQueriesLoadMarkedHandlesList) &&
							     in_array($assetHandle, $allJsMediaQueriesLoadMarkedHandlesList) &&
							     in_array($assetHandle, $wp_scripts->done) ) {
								$allJsDepsParentToChild[ $dep ][] = $assetHandle;
							}
						}
					}
				}
			}

            ObjectCache::wpacu_cache_set( 'wpacu_get_deps_parent_to_child_'.$assetType, $allCssDepsParentToChild );
			return $allJsDepsParentToChild;
		}

		return array(); // should get here, unless the $assetType is not valid
	}
}
