<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCss;

/**
 * Class Preloads
 * @package WpAssetCleanUp
 */
class Preloads
{
	/**
	 * Printed in HEAD
	 */
	const DEL_STYLES_PRELOADS = '<meta name="wpacu-generator" content="ASSET CLEANUP STYLES PRELOADS">';

	/**
	 * Printed in HEAD
	 */
	const DEL_SCRIPTS_PRELOADS = '<meta name="wpacu-generator" content="ASSET CLEANUP SCRIPTS PRELOADS">';

	/**
	 * @var array
	 */
	public $preloads = array('styles' => array(), 'scripts' => array());

	/**
	 * @var Preloads|null
	 */
	private static $singleton;

	/**
	 * @return null|Preloads
	 */
	public static function instance()
	{
		if (self::$singleton === null) {
			self::$singleton = new self();
		}

		return self::$singleton;
	}

	/**
	 * Preloads constructor.
	 */
	public function __construct()
	{
	    if (is_admin() || self::preventPreload()) {
	        return;
        }

		$this->preloads = $this->getPreloads();

		add_filter('wpfc_buffer_callback_filter', static function ($buffer) {
			$buffer = str_replace('rel=\'preload\' data-from-rel=\'stylesheet\'', 'rel=\'preload\'', $buffer);

			// [wpacu_pro]
            $buffer = apply_filters('wpacu_wpfc_update_deferred_css_links', $buffer);
            // [/wpacu_pro]

			return $buffer;
		});
	}

	/**
	 *
	 */
	public function init()
	{
        if (is_admin()) {
            // Trigger only within the Dashboard
            if (Misc::getVar('post', 'wpacu_remove_preloaded_assets_nonce')) {
                add_action('admin_init', static function() {
                    Preloads::removePreloadFromChosenAssets();
                });
            }

            // Trigger only in "Bulk Changes" -> "Preloaded CSS/JS"
            if (isset($_GET['page']) && $_GET['page'] === WPACU_PLUGIN_ID.'_bulk_unloads'
                && get_transient('wpacu_preloads_just_removed')) {
                add_action('wpacu_admin_notices', array($this, 'noticePreloadsRemoved'));
                delete_transient('wpacu_preloads_just_removed');
            }
        }

        add_action('init', function() {
            if ( ! is_admin() && ! ( (WPACU_GET_LOADED_ASSETS_ACTION === true) || Plugin::preventAnyFrontendOptimization() || self::preventPreload() || Main::isTestModeActiveAndVisitorNonAdmin() ) ) { // Trigger only in the front-end
                add_filter('style_loader_tag', array($this, 'preloadCss'), 11, 2);
                add_filter('script_loader_tag', array($this, 'preloadJs'), 11, 2);
            }
        });
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public function doChanges($htmlSource)
    {
        if (self::preventPreload()) {
            return $htmlSource;
        }

	    $this->preloads = $this->getPreloads();

	    if (isset($this->preloads['styles']) && ! empty($this->preloads['styles'])) {
		    $htmlSource = self::appendPreloadsForStylesToHead($htmlSource);
	    }

	    return $htmlSource;
    }

	/**
	 * @param string $for
	 * @return bool
	 */
	public function enablePreloads($for)
	{
	    if (self::preventPreload()) {
	        return false;
        }

	    $assetType = ($for === 'css') ? 'styles' : 'scripts';

	    if (! (isset($this->preloads[$assetType]) && ! empty($this->preloads[$assetType]))) {
			return false;
		}

		// Do not use the preloads if "Optimize CSS Delivery" is enabled in WP Rocket
		if ($for === 'css' && Misc::isPluginActive('wp-rocket/wp-rocket.php') && function_exists('get_rocket_option') && get_rocket_option('async_css')) {
			return false;
		}

		// WP Fastest Cache: Combine CSS/JS is enabled
		if (! Menu::userCanManageAssets() && Misc::isPluginActive('wp-fastest-cache/wpFastestCache.php')) {
			$wpfcOptionsJson = get_option('WpFastestCache');
			$wpfcOptions     = @json_decode($wpfcOptionsJson, ARRAY_A);

			if ($for === 'css' && isset($wpfcOptions['wpFastestCacheCombineCss'])) {
				return false;
			}

			if ($for === 'js' && isset($wpfcOptions['wpFastestCacheCombineJs'])) {
				return false;
			}
		}

		// W3 Total Cache
		if (Misc::isPluginActive('w3-total-cache/w3-total-cache.php')) {
			$w3tcConfigMaster = Misc::getW3tcMasterConfig();

			if ($for === 'css') {
				$w3tcEnableCss = (int)trim(Misc::extractBetween($w3tcConfigMaster, '"minify.css.enable":', ','), '" ');

				if ($w3tcEnableCss === 1) {
					return false;
				}
			}

			if ($for === 'js') {
				$w3tcEnableJs = (int)trim(Misc::extractBetween($w3tcConfigMaster, '"minify.js.enable":', ','), '" ');

				if ($w3tcEnableJs === 1) {
					return false;
				}
			}
		}

		// LiteSpeed Cache
		if (Misc::isPluginActive('litespeed-cache/litespeed-cache.php') && ($liteSpeedCacheConf = apply_filters('litespeed_cache_get_options', get_option('litespeed-cache-conf')))) {
			if ($for === 'css' && isset($liteSpeedCacheConf['css_minify']) && $liteSpeedCacheConf['css_minify']) {
				return false;
			}

			if ($for === 'js' && isset($liteSpeedCacheConf['js_minify']) && $liteSpeedCacheConf['js_minify']) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function getPreloads()
	{
		$preloadsListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

		if ($preloadsListJson) {
			$preloadsList = @json_decode($preloadsListJson, true);

			// Issues with decoding the JSON file? Return an empty list
			if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
				return $this->preloads;
			}

			// Are new positions set for styles and scripts?
			foreach (array('styles', 'scripts') as $assetKey) {
				if ( ! empty( $preloadsList[$assetKey]['preloads'] ) ) {
					$this->preloads[$assetKey] = $preloadsList[$assetKey]['preloads'];
				}
			}
		}

		return $this->preloads;
	}

	/**
	 * @param $htmlTag
	 * @param $handle
	 *
	 * @return string
	 */
	public function preloadCss($htmlTag, $handle)
	{
		/* [wpacu_timing] */ $wpacuTimingName = 'style_loader_tag_preload_css'; Misc::scriptExecTimer( $wpacuTimingName ); /* [/wpacu_timing] */

		if ($wpacuAsyncPreloadHandle = Misc::getVar('get', 'wpacu_preload_css')) {
			// For testing purposes: Check how the page loads with the requested CSS preloaded
			$this->preloads['styles'][$wpacuAsyncPreloadHandle] = 'basic';
		}

		// [wpacu_pro]
		if ($wpacuAsyncPreloadHandle = Misc::getVar('get', 'wpacu_preload_css_async')) {
		    // For testing purposes: Check how the page loads with the requested CSS preloaded & loaded asynchronously
		    $this->preloads['styles'][$wpacuAsyncPreloadHandle] = 'async';
        }
		// [/wpacu_pro]

		// Only valid for front-end pages with LINKs
		if (! $this->enablePreloads('css') || strpos($htmlTag,'<link ') === false || Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
			return $htmlTag;
		}

		if (! isset($this->preloads['styles'])) {
			/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
			return $htmlTag;
		}

		if ( ! empty($this->preloads['styles'][$handle]) ) {
            if (strpos($htmlTag, 'data-wpacu-apply-media-query') !== false) {
                // Preloading will not be applied if a "match query load" rule is already applied
                return $htmlTag;
            }

		    // [wpacu_pro]
            if ($this->preloads['styles'][$handle] === 'async') {
	            if (isset($_REQUEST['wpacu_no_css_preload_async'])) { // do not apply it for debugging purposes
		            /* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
		            return str_replace('<link ', '<link data-wpacu-skip-preload=\'1\' ', $htmlTag);
	            }

	            /* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
                return apply_filters('wpacu_preload_css_async_tag', $htmlTag);
            }
            // [/wpacu_pro]

            if (isset($_REQUEST['wpacu_no_css_preload_basic'])) { // do not apply it for debugging purposes
	            /* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
	            return str_replace('<link ', '<link data-wpacu-skip-preload=\'1\' ', $htmlTag);
            }

			ObjectCache::wpacu_cache_set($handle, 1, 'wpacu_basic_preload_handles');

			/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */

            if (strpos($handle, 'wpacu_hardcoded_') === 0) {
                $htmlTag = str_replace('<link ', '<link id=\''.$handle.'-css\' ', $htmlTag);
            }

            return str_replace('<link ', '<link data-wpacu-to-be-preloaded-basic=\'1\' ', $htmlTag);
		}

		/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
		return $htmlTag;
	}

	/**
	 * @param $htmlTag
	 * @param $handle
	 * @return string
	 */
	public function preloadJs($htmlTag, $handle)
	{
		/* [wpacu_timing] */ $wpacuTimingName = 'script_loader_tag_preload_js'; Misc::scriptExecTimer( $wpacuTimingName ); /* [/wpacu_timing] */
		if (isset($_REQUEST['wpacu_no_js_preload_basic'])) {
			/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
			return str_replace('<script ', '<script data-wpacu-skip-preload=\'1\' ', $htmlTag);
        }

		// For testing purposes: Check how the page loads with the requested JS preloaded
		if ($wpacuJsPreloadHandle = Misc::getVar('get', 'wpacu_preload_js')) {
			$this->preloads['scripts'][$wpacuJsPreloadHandle] = 1;
		}

		// Only valid for front-end pages with SCRIPTs
		if (! $this->enablePreloads('js') || strpos($htmlTag,'<script ') === false || Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
			//endRemoveIf(development)
			return $htmlTag;
		}

		if (! isset($this->preloads['scripts'])) {
			return $htmlTag;
		}

		if (array_key_exists($handle, $this->preloads['scripts']) && $this->preloads['scripts'][$handle]) {
            if (strpos($htmlTag, 'data-wpacu-apply-media-query') !== false) {
                /* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
                // Preloading will not be applied if a "match query load" rule is already applied
                return $htmlTag;
            }

            if (strpos($handle, 'wpacu_hardcoded_') === 0) {
                /* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
                $htmlTag = str_replace('<script ', '<script id=\''.$handle.'-js\' ', $htmlTag);
            }

            /* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
			return str_replace('<script ', '<script data-wpacu-to-be-preloaded-basic=\'1\' ', $htmlTag);
		}

		/* [wpacu_timing] */Misc::scriptExecTimer( $wpacuTimingName, 'end' );/* [/wpacu_timing] */
		return $htmlTag;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function appendPreloadsForStylesToHead($htmlSource)
	{
	    if (empty($htmlSource)) {
	        return $htmlSource;
        }

		// Perhaps it's not applicable in the current page (no LINK tags are loaded that should be preloaded)
		if (strpos($htmlSource, 'data-wpacu-to-be-preloaded-basic') === false) {
			return $htmlSource;
		}

        $allHrefs = array();
		$stickToRegEx = true; // default

		// Something might not be right with the RegEx; Fallback to DOMDocument, more accurate, but slower
		if ( Misc::isDOMDocumentOn() ) {
			$documentForCSS = Misc::initDOMDocument();

			$htmlSourceAlt = preg_replace( '@<(noscript|style|script)[^>]*?>.*?</\\1>@si', '', $htmlSource );

            if (empty($htmlSourceAlt)) {
                $htmlSourceAlt = $htmlSource;
            }

			$documentForCSS->loadHTML($htmlSourceAlt);

            $linkTags = $documentForCSS->getElementsByTagName( 'link' );

			if ( count($linkTags) > 0 ) {
	            $matchesSourcesFromLinkTags = array(); // reset its value; new fetch method was used

	            foreach ( $linkTags as $tagObject ) {
		            if ( empty( $tagObject->attributes ) ) {
			            continue;
		            }

		            $linkAttributes = array();

		            foreach ( $tagObject->attributes as $attrObj ) {
			            $linkAttributes[ $attrObj->nodeName ] = trim( $attrObj->nodeValue );
		            }

		            if ( ! isset( $linkAttributes['data-wpacu-to-be-preloaded-basic'], $linkAttributes['href'] ) ) {
                        continue;
                    }

		            if (strpos($htmlSourceAlt, $linkAttributes['href']) === false) {
			            $stickToRegEx = true; // the source value is not the same as in the HTML source (e.g. altered by the DOM) / fallback to RegEx
			            break;
		            }

                    $linkTag = Misc::getOuterHTML( $tagObject );

                    $allHrefs[$linkTag] = $linkAttributes['href'];
	            }
            }

			libxml_clear_errors();
        }

        if ($stickToRegEx) {
	        // Use the RegEx as it's much faster and very accurate in this situation
	        $strContainsFormat = preg_quote('data-wpacu-to-be-preloaded-basic', '/');
	        preg_match_all('#<link[^>]*'.$strContainsFormat.'[^>]*' . '\shref(\s+|)=(\s+|)(\'|"|)(.*)(\\3)' . '.*(>)#Usmi', $htmlSource, $matchesSourcesFromLinkTags, PREG_SET_ORDER);

	        if ( ! empty($matchesSourcesFromLinkTags) ) {
		        foreach ( $matchesSourcesFromLinkTags as $linkTagArray ) {
                    $linkTag = isset( $linkTagArray[0] ) ? $linkTagArray[0] : false;

                    if ($linkTag) {
	                    $linkHref = isset( $linkTagArray[0] ) ? Misc::getValueFromTag( $linkTag ) : false;

	                    if ( $linkHref ) {
		                    $allHrefs[$linkTag] = $linkHref;
	                    }
                    }
		        }
	        }
        }

		$allHrefs = array_unique($allHrefs);

		if ( ! empty($allHrefs) ) {
	        foreach ( $allHrefs as $linkTag => $linkHref ) {
                $condBefore = $condAfter = '';

		        // Any IE comments around the tag?
		        $linkIdAttr = Misc::getValueFromTag($linkTag, 'id');

		        if ($linkIdAttr && substr( $linkIdAttr, -4 ) === '-css') {
                    $linkHandle = substr( $linkIdAttr, 0, -4 );

                    // This is for enqueued (the WordPress way) LINKs
                    $linkObj = isset(Main::instance()->wpAllStyles['registered'][$linkHandle]) ? Main::instance()->wpAllStyles['registered'][$linkHandle] : false;

                    $conditional = '';

                    if ($linkObj) {
	                    $conditional = isset($linkObj->extra['conditional']) ? $linkObj->extra['conditional'] : '';
                    } elseif (strpos($linkHandle, 'wpacu_hardcoded_') === 0) {
                        $conditional = Misc::getValueFromTag($linkTag, 'data-wpacu-cond-comm');
                    }

                    if ($conditional) {
                        $condBefore = "<!--[if {$conditional}]>\n";
                        $condAfter  = "<![endif]-->\n";
                    }
		        }

                $linkPreload = $condBefore;

                // [wpacu_pro]
                $extraAttrs = array();

                $mediaAttr = Misc::getValueFromTag($linkTag, 'media');

                if ( $mediaAttr && $mediaAttr !== 'all' ) {
                    $extraAttrs['media'] = $mediaAttr;
                }

                $linkPreload .= self::linkPreloadCssFormat( $linkHref, $extraAttrs );
                $linkPreload .= $condAfter;

                $htmlSource = str_replace( self::DEL_STYLES_PRELOADS, $linkPreload . self::DEL_STYLES_PRELOADS, $htmlSource );
	        }
        }

		return $htmlSource;
	}

	/**
	 * @param $linkHref string
     * @param $extraAttrs array
	 *
	 * @return string
	 */
	public static function linkPreloadCssFormat($linkHref, $extraAttrs = array())
    {
        $extraAttrsPrinted = '';

        if ( ! empty($extraAttrs) ) {
            foreach ($extraAttrs as $attrName => $attrValue) {
                $extraAttrsPrinted .= $attrName . '="'.esc_attr($attrValue).'" ';
            }
        }

        if (OptimizeCss::wpfcMinifyCssEnabledOnly()) {
            return '<link rel=\'preload\' data-from-rel=\'stylesheet\' as=\'style\' data-href-before=\''.$linkHref.'\' '.$extraAttrsPrinted.' href=\''.esc_attr($linkHref).'\' data-wpacu-preload-css-basic=\'1\' />' . "\n";
        }

        return '<link rel=\'preload\' as=\'style\' href=\''.esc_attr($linkHref).'\' '.$extraAttrsPrinted.' data-wpacu-preload-css-basic=\'1\' />'."\n";
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function appendPreloadsForScriptsToHead($htmlSource)
	{
	    if (self::preventPreload()) {
	        return $htmlSource;
        }

		$strContainsFormat = preg_quote('data-wpacu-to-be-preloaded-basic=\'1\'', '/');

		preg_match_all('#<script[^>]*'.$strContainsFormat.'[^>]*' . 'src(\s+|)=(\s+|)(\'|"|)(.*)(\\3)' . '.*(>)#Usmi', $htmlSource, $matchesSourcesFromScriptTags, PREG_SET_ORDER);

		if (empty($matchesSourcesFromScriptTags)) {
			return $htmlSource;
		}

		foreach ($matchesSourcesFromScriptTags as $scriptTagArray) {
            $scriptTag = isset($scriptTagArray[0]) ? $scriptTagArray[0] : false;

			if (! $scriptTag) {
				continue;
			}

            $scriptSrc = Misc::getValueFromTag($scriptTag);

            $condBefore = $condAfter = '';

            // Any IE comments around the tag?
            $scriptIdAttr = Misc::getValueFromTag($scriptTag, 'id');

            if ($scriptIdAttr && strpos($scriptTag, '-js') !== false) {
	            $scriptHandle = rtrim($scriptIdAttr, '-js');

                if ($scriptHandle) {
                    // This is for enqueued (the WordPress way) SCRIPTs
                    $scriptObj = isset(Main::instance()->wpAllScripts['registered'][$scriptHandle]) ? Main::instance()->wpAllScripts['registered'][$scriptHandle] : false;

                    $conditional = '';

                    if ($scriptObj) {
	                    $conditional = isset($scriptObj->extra['conditional']) ? $scriptObj->extra['conditional'] : '';
                    } elseif (strpos($scriptHandle, 'wpacu_hardcoded_') === 0) {
                        $conditional = Misc::getValueFromTag($scriptTag, 'data-wpacu-cond-comm');
                    }

                    if ($conditional) {
                        $condBefore = "<!--[if {$conditional}]>\n";
                        $condAfter  = "<![endif]-->\n";
                    }
                }
            }

            $linkPreload  = $condBefore;
			$linkPreload .= '<link rel=\'preload\' as=\'script\' href=\''.esc_attr($scriptSrc).'\' data-wpacu-preload-js=\'1\'>'."\n";
			$linkPreload .= $condAfter;

			$htmlSource = str_replace(self::DEL_SCRIPTS_PRELOADS, $linkPreload . self::DEL_SCRIPTS_PRELOADS, $htmlSource);
		}

		return $htmlSource;
	}

	/**
	 * Triggered from "Bulk Unloads" - "Preloaded CSS/JS"
	 * after the selection is made, and the button is clicked
	 *
	 * @return void
	 */
	public static function removePreloadFromChosenAssets()
	{
		$stylesCheckedList  = Misc::getVar('post', 'wpacu_styles_remove_preloads',  array());
		$scriptsCheckedList = Misc::getVar('post', 'wpacu_scripts_remove_preloads', array());

		if (empty($stylesCheckedList) && empty($scriptsCheckedList)) {
			return;
		}

		\check_admin_referer('wpacu_remove_preloaded_assets', 'wpacu_remove_preloaded_assets_nonce');

		$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
		$globalKey = 'preloads';

		$existingListEmpty = array('styles' => array($globalKey => array()), 'scripts' => array($globalKey => array()));
		$existingListJson = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		if (! empty($stylesCheckedList)) {
			foreach ($stylesCheckedList as $styleHandle => $action) {
				if ($action === 'remove') {
					unset($existingList['styles'][$globalKey][$styleHandle]);
				}
			}
		}

		if (! empty($scriptsCheckedList)) {
			foreach ($scriptsCheckedList as $scriptHandle => $action) {
				if ($action === 'remove') {
					unset($existingList['scripts'][$globalKey][$scriptHandle]);
				}
			}
		}

		Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));

		set_transient('wpacu_preloads_just_removed', 1, 30);

		wp_safe_redirect(admin_url('admin.php?page=wpassetcleanup_bulk_unloads&wpacu_bulk_menu_tab=preloaded_assets&wpacu_time='.time()));
		exit();
	}

	/**
	 *
	 */
	public function noticePreloadsRemoved()
	{
		?>
		<div class="updated notice wpacu-notice is-dismissible">
			<p><span class="dashicons dashicons-yes"></span>
				<?php
				_e('The preload option was removed for the chosen CSS/JS.', 'wp-asset-clean-up');
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * @return bool
	 */
	public static function preventPreload()
    {
        if (defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') && WPACU_ALLOW_ONLY_UNLOAD_RULES) {
            return true;
        }

        return false;
    }
}
