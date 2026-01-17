<?php
namespace WpAssetCleanUpPro\OptimiseAssets;

use WpAssetCleanUp\Main;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\OptimiseAssets\MinifyCss;
use WpAssetCleanUp\OptimiseAssets\OptimizeCss;
use WpAssetCleanUp\Plugin;

/**
 * Class CriticalCssPro
 * @package WpAssetCleanUpPro\OptimiseAssets
 */
class CriticalCssPro
{
	/**
	 *
	 */
	const CRITICAL_CSS_MARKER = '<meta data-name=wpacu-delimiter data-content="ASSET CLEANUP CRITICAL CSS" />';

	/**
	 * This will be later filled with custom post types & custom taxonomies (if any)
	 *
	 * @var string[]
	 */
	public static $allKeyPages = array(
		'homepage', 'posts', 'pages', 'media', 'category', 'tag', 'search', 'author', 'date', '404_not_found'
	);

	/**
	 * CriticalCssPro constructor.
	 */
	public function __construct()
	{
	    // Dashboard's management: "CSS & JS Manager" -> "Manage Critical CSS"
        if (is_admin()) {
	        $this->initInAdmin();
        } else {
	        // Show the critical CSS in the front-end view (for regular visitors)
	        $this->initInFrontend();
        }
	}

	/**
	 *
	 */
	public function initInAdmin()
    {
		$wpacuSubPage = isset($_GET['wpacu_sub_page']) ? $_GET['wpacu_sub_page'] : '';

		if ( $wpacuSubPage === 'manage_critical_css' ) {
			add_action( 'admin_init', function() {
				self::$allKeyPages = CriticalCssPro::fillAllKeyPages( self::$allKeyPages );
			}, 1 );
		}

	    add_action('admin_init', array($this, 'updateCriticalCss'), 10, 1);
    }

	/**
	 *
	 */
	public function initInFrontend()
    {
	    // Show any critical CSS signature in the front-end view?
	    add_action('wp_head', static function() {
		    if ( Plugin::preventAnyFrontendOptimization() || Main::isTestModeActiveAndVisitorNonAdmin() || ( Main::instance()->settings['critical_css_status'] === 'off' ) || ! has_filter('wpacu_critical_css') ) {
		        return;
		    }
		    echo self::CRITICAL_CSS_MARKER; // Add the marker that will be later replaced with the critical CSS
	    }, -PHP_INT_MAX);

	    // 1) Alter the HTML source to prepare it for the critical CSS
	    add_filter('wpacu_alter_source_for_critical_css', array($this, 'alterHtmlSourceForCriticalCss'));

	    // 2) Print the critical CSS
	    add_filter('wpacu_critical_css', array($this, 'showAnyCriticalCss'));
    }

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function alterHtmlSourceForCriticalCss($htmlSource)
	{
		// The marker needs to be there
		if (strpos($htmlSource, self::CRITICAL_CSS_MARKER) === false) {
			return $htmlSource;
		}

		// For debugging purposes, do not print any critical CSS, nor preload any of the LINk tags (with rel="stylesheet")
		if ( isset($_GET['wpacu_no_critical_css_and_preload']) ) {
			return str_replace(self::CRITICAL_CSS_MARKER, '', $htmlSource);
		}

		$criticalCssData = apply_filters('wpacu_critical_css', array('content' => false, 'minify' => false));

		// If it's through the Dashboard it always has a location key (e.g. posts, pages, categories)
		// Otherwise, the "wpacu_critical_hook" was used via custom coding (e.g. in functions.php)
		if (! isset($criticalCssData['location_key'])) {
			$criticalCssData['location_key'] = 'custom_via_hook';
		}

		if ( ! (isset($criticalCssData['content']) && $criticalCssData['content']) ) {
			// No critical CSS set? Return the HTML source as it is with the critical CSS location marker stripped
			return str_replace(self::CRITICAL_CSS_MARKER, '', $htmlSource);
		}

		$keepRenderBlockingList = ( isset( $criticalCssData['keep_render_blocking'] ) && $criticalCssData['keep_render_blocking'] ) ? $criticalCssData['keep_render_blocking'] : array();

		// If just a string was added (one in the list), convert it as an array with one item
		if (! is_array($keepRenderBlockingList)) {
			$keepRenderBlockingList = array($keepRenderBlockingList);
		}

		$doCssMinify        = isset( $criticalCssData['minify'] ) && $criticalCssData['minify']; // leave no room for any user errors in case the 'minify' parameter is unset by mistake
		$criticalCssContent = OptimizeCss::maybeAlterContentForCssFile( $criticalCssData['content'], $doCssMinify, array( 'alter_font_face' ) );

		$criticalCssStyleTag = '<style '.Misc::getStyleTypeAttribute().' id="wpacu-critical-css" data-wpacu-critical-css-type="'.$criticalCssData['location_key'].'">'.$criticalCssContent.'</style>';

		/*
		 * By default the page will have the critical CSS applied as well as non-render blocking LINK tags (non-critical)
		 * For development purposes only, you can append:
		 * 1) /?wpacu_only_critical_css to ONLY load the critical CSS
		 * 2) /?wpacu_no_critical_css to ONLY load the non-render blocking LINK tags (non-critical)
		 * For a cleaner load, &wpacu_no_admin_bar can be added to avoid loading the top admin bar
		*/
		if ( isset($_GET['wpacu_only_critical_css']) )  {
			// For debugging purposes: preview how the page would load only with the critical CSS loaded (all LINK/STYLE CSS tags are stripped)
            // Do not remove the admin bar's (and other marked ones) CSS as it would make sense to keep it as it is if the admin is logged-in
			$htmlSource = preg_replace('#<link(.*?)data-wpacu-skip-preload#Umi', "<wpacu_link$1data-wpacu-skip-preload", $htmlSource);

			$htmlSource = preg_replace('#<link[^>]*(stylesheet|(as(\s+|)=(\s+|)(|"|\')style(|"|\')))[^>]*(>)#Umi', '', $htmlSource);
			$htmlSource = preg_replace('@(<style[^>]*?>).*?</style>@si', '', $htmlSource);
			$htmlSource = str_replace(Misc::preloadAsyncCssFallbackOutput(true), '', $htmlSource);

			// Restore any LINKs to admin-bar and others (if any)
			$htmlSource = preg_replace('#<wpacu_link(.*?)data-wpacu-skip-preload#Umi', "<link$1data-wpacu-skip-preload", $htmlSource);
		} else {
			// Convert render-blocking LINK CSS tags into non-render blocking ones
			$cleanerHtmlSource = preg_replace( '/<!--(.|\s)*?-->/', '', $htmlSource );
			$cleanerHtmlSource = preg_replace( '@<(noscript)[^>]*?>.*?</\\1>@si', '', $cleanerHtmlSource );

			preg_match_all( '#<link[^>]*(stylesheet|(as(\s+|)=(\s+|)(|"|\')style(|"|\')))[^>]*(>)#Umi',
				$cleanerHtmlSource, $matchesSourcesFromTags, PREG_SET_ORDER );

			if ( empty( $matchesSourcesFromTags ) ) {
				return $htmlSource;
			}

			foreach ( $matchesSourcesFromTags as $results ) {
				$matchedTag = $results[0];

				if (! empty($keepRenderBlockingList) && preg_match('#('.implode('|', $keepRenderBlockingList).')#Usmi', $matchedTag)) {
					continue;
				}

				// Marked for no alteration or for loading based on the media query match? Then, it's already non-render blocking, and it has to be skipped!
				if (preg_match('#data-wpacu-skip([=>/ ])#i', $matchedTag)
				    || strpos($matchedTag, 'data-wpacu-apply-media-query=') !== false) {
					continue;
				}

				if ( strpos ($matchedTag, 'data-wpacu-skip-preload=\'1\'') !== false  ) {
					continue; // skip async preloaded (for debugging purposes or when it is not relevant)
				}

				if ( preg_match( '#rel(\s+|)=(\s+|)([\'"])preload([\'"])#i', $matchedTag ) ) {
					if ( strpos( $matchedTag, 'data-wpacu-preload-css-basic=\'1\'' ) !== false ) {
						$htmlSource = str_replace( $matchedTag, '', $htmlSource );
					} elseif ( strpos( $matchedTag, 'data-wpacu-preload-it-async=\'1\'' ) !== false ) {
						continue; // already async preloaded
					}
				} elseif ( preg_match( '#rel(\s+|)=(\s+|)([\'"])stylesheet([\'"])#i', $matchedTag ) ) {
					$matchedTagAlteredForPreload = str_ireplace(
						array(
							'<link ',
							'rel=\'stylesheet\'',
							'rel="stylesheet"',
							'id=\'',
							'id="',
							'data-wpacu-to-be-preloaded-basic=\'1\''
						),
						array(
							'<link rel=\'preload\' as=\'style\' data-wpacu-preload-it-async=\'1\' ',
							'onload="this.onload=null;this.rel=\'stylesheet\'"',
							'onload="this.onload=null;this.rel=\'stylesheet\'"',
							'id=\'wpacu-preload-',
							'id="wpacu-preload-',
							''
						),
						$matchedTag
					);

					$htmlSource = str_replace( $matchedTag, $matchedTagAlteredForPreload, $htmlSource );
				}
			}
		}

		// For debugging purposes: preview how the page would load without critical CSS & all the non-render blocking CSS files loaded
		// It should show a flash of unstyled content: https://en.wikipedia.org/wiki/Flash_of_unstyled_content
		if ( isset($_GET['wpacu_no_critical_css']) ) {
			$criticalCssStyleTag = '';
		}

		return str_replace(
			self::CRITICAL_CSS_MARKER,
			$criticalCssStyleTag . Misc::preloadAsyncCssFallbackOutput(),
			$htmlSource
		);
	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	public function showAnyCriticalCss($args)
	{
	    // Do not continue if critical CSS is globally deactivated
		if (Main::instance()->settings['critical_css_status'] === 'off') {
		    return $args;
        }

		$criticalCssLocationKey = false; // default value until any location is detected (e.g. homepage)

		if (Misc::isHomePage()) {
			$criticalCssLocationKey = 'homepage'; // Main page of the website when just the default site URL is loaded
		} elseif (is_singular()) {
			if (get_post_type() === 'post') { // "Posts" -> "All Posts" -> "View"
				$criticalCssLocationKey = 'posts';
			} elseif (get_post_type() === 'page') { // "Pages" -> "All Pages" -> "View"
				$criticalCssLocationKey = 'pages';
			} elseif (is_attachment()) {
				$criticalCssLocationKey = 'media'; // "Media" -> "Library" -> "View" (rarely used, but added it just in case)
			} else {
				global $post;

				if ( isset( $post->post_type ) && $post->post_type ) {
					$criticalCssLocationKey = 'custom_post_type_' . $post->post_type;
				}
			}
		} elseif (is_category()) {
		    $criticalCssLocationKey = 'category'; // "Posts" -> "Categories" -> "View"
		} elseif (is_tag()) {
		    $criticalCssLocationKey = 'tag'; // "Posts" -> "Tags" -> "View"
		} elseif (is_tax()) { // Custom Taxonomy (e.g. "product_cat" from WooCommerce, found in "Products" -> "Categories")
            global $wp_query;
            $object = $wp_query->get_queried_object();

            if ( isset( $object->taxonomy ) && $object->taxonomy ) {
                $criticalCssLocationKey = 'custom_taxonomy_' . $object->taxonomy;
            }
		} elseif (is_search()) {
			$criticalCssLocationKey = 'search'; // /?s=[keyword_here] in the front-end view
		} elseif (is_author()) {
			$criticalCssLocationKey = 'author'; // /author/demo/ in the front-end view
        } elseif (is_date()) {
			$criticalCssLocationKey = 'date'; // e.g. /2020/10/ in the front-end view
		} elseif (is_404()) {
			$criticalCssLocationKey = '404_not_found'; // e.g. /a-page-slug-that-is-non-existent/
		}

		if (! $criticalCssLocationKey) {
			return $args; // there's no critical CSS to apply on the current page as no critical CSS is set for it
		}

		$allCriticalCssOptions = self::getAllCriticalCssOptions($criticalCssLocationKey);

		if ( ! (isset($allCriticalCssOptions['enable']) && $allCriticalCssOptions['enable']) ) {
			return $args;  // there's no critical CSS to apply on the current page because it's disabled for the current page (location key)
		}

		$criticalCssContentJson = get_option(WPACU_PLUGIN_ID . '_critical_css_location_key_' . $criticalCssLocationKey);
		$criticalCssContentArray = @json_decode($criticalCssContentJson, true);

		// Issues with decoding the JSON content? Do not apply any critical CSS
		if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
			return $args;
		}

		if (isset($allCriticalCssOptions['show_method'], $criticalCssContentArray['content_minified']) && $allCriticalCssOptions['show_method'] === 'minified' && $criticalCssContentArray['content_minified']) {
			$args['content'] = stripslashes($criticalCssContentArray['content_minified']); // serve minified as instructed
		} elseif (isset($criticalCssContentArray['content_original']) && $criticalCssContentArray['content_original']) {
			$args['content'] = stripslashes($criticalCssContentArray['content_original']); // serve the original content which could be already minified
		}

		$args['location_key'] = $criticalCssLocationKey;

		return $args;
	}

	/**
	 * @param $criticalCssLocationKey
	 *
	 * @return array|mixed
	 */
	public static function getAllCriticalCssOptions($criticalCssLocationKey)
	{
		$criticalCssConfigDbListJson = get_option(WPACU_PLUGIN_ID . '_critical_css_config');

		if ($criticalCssConfigDbListJson) {
			$criticalCssConfigDbList = @json_decode($criticalCssConfigDbListJson, true);

			// Issues with decoding the JSON file? Return an empty list
			if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
				return array();
			}

			// Are there any critical CSS options for the targeted location?
			if ( ! empty( $criticalCssConfigDbList[$criticalCssLocationKey] ) ) {
				return $criticalCssConfigDbList[$criticalCssLocationKey];
			}
		}

		return array();
	}

	/**
	 * @param $criticalCssConfig
	 *
	 * @return array
	 */
	public static function getAllEnabledLocations($criticalCssConfig)
	{
		$allEnabledLocations = array();

		foreach (self::$allKeyPages as $locationKey) {
			if ( is_string($locationKey) && isset( $criticalCssConfig[$locationKey]['enable'] ) && $criticalCssConfig[$locationKey]['enable'] ) {
				$allEnabledLocations[] = $locationKey;
			}
		}

		return $allEnabledLocations;
	}

	/**
	 * @param $allPossibleKeys
	 */
	public static function fillAllKeyPages($allPossibleKeys)
	{
		// Any custom post types
		$postTypes = get_post_types( array( 'public' => true ) );

		foreach ( $postTypes as $postType ) {
			if ( ! in_array($postType, $allPossibleKeys) ) {
				$allPossibleKeys['custom_post_type'] = $postType;
			}
		}

		// Any custom taxonomies
		$taxonomies = get_taxonomies(array( 'public' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! in_array($taxonomy, $allPossibleKeys) ) {
				$allPossibleKeys['custom_taxonomy'] = $taxonomy;
			}
		}

		return $allPossibleKeys;
	}

	/**
	 * @param $postTypesList
	 * @param $chosenPostType
     * @param $criticalCssConfig
	 */
	public static function buildCustomPostTypesListLinks($postTypesList, $chosenPostType, $criticalCssConfig)
	{
		?>
		<ul id="wpacu_custom_pages_nav_links">
			<?php
			foreach ($postTypesList as $postTypeKey => $postTypeValue) {
			    $liClass = ($chosenPostType === $postTypeKey) ? 'wpacu-current' : '';
			    $navLink = esc_url(admin_url('admin.php?page=wpassetcleanup_assets_manager&wpacu_sub_page=manage_critical_css&wpacu_for=custom-post-types&wpacu_current_post_type='.$postTypeKey));
			    $wpacuStatus = (isset($criticalCssConfig['custom_post_type_'.$postTypeKey]['enable']) && $criticalCssConfig['custom_post_type_'.$postTypeKey]['enable']) ? 'wpacu-on' : 'wpacu-off';
			?>
                <li class="<?php echo esc_attr($liClass); ?>">
                    <a href="<?php echo esc_url($navLink); ?>"><?php echo esc_html($postTypeValue); ?><span data-wpacu-custom-page-type="<?php echo esc_attr($postTypeKey); ?>_post_type" class="wpacu-circle-status <?php echo esc_attr($wpacuStatus); ?>"></span></a>
                </li>
			<?php
			}
			?>
		</ul>
		<?php
	}

	/**
	 * @param $taxonomyList
	 * @param $chosenTaxonomy
	 * @param $criticalCssConfig
	 */
	public static function buildTaxonomyListLinks($taxonomyList, $chosenTaxonomy, $criticalCssConfig)
	{
		?>
        <ul id="wpacu_custom_pages_nav_links">
			<?php
			foreach ($taxonomyList as $taxonomyKey => $taxonomyValue) {
				$liClass = ($chosenTaxonomy === $taxonomyKey) ? 'wpacu-current' : '';
				$navLink = esc_url(admin_url('admin.php?page=wpassetcleanup_assets_manager&wpacu_sub_page=manage_critical_css&wpacu_for=custom-taxonomy&wpacu_current_taxonomy='.$taxonomyKey));
				$wpacuStatus = (isset($criticalCssConfig['custom_taxonomy_'.$taxonomyKey]['enable']) && $criticalCssConfig['custom_taxonomy_'.$taxonomyKey]['enable']) ? 'wpacu-on' : 'wpacu-off';
				?>
                <li class="<?php echo esc_attr($liClass); ?>">
                    <a href="<?php echo esc_url($navLink); ?>"><?php echo esc_html($taxonomyValue); ?><span data-wpacu-custom-page-type="<?php echo esc_attr($taxonomyKey); ?>_taxonomy" class="wpacu-circle-status <?php echo esc_attr($wpacuStatus); ?>"></span></a>
                </li>
				<?php
			}
			?>
        </ul>
		<?php
	}

	/**
	 * @param $criticalCssConfig
	 * @param $type
	 *
	 * @return bool
	 */
	public static function isEnabledForAtLeastOnePageType($criticalCssConfig, $type)
    {
        // Fix: There might be dormant custom post types or taxonomies (not used anymore on the website)
	    // That have traces left / These will not count as it would confuse the admin

        if ($type === 'custom_taxonomy') {
	        $allTaxonomies = array_keys(get_taxonomies(array( 'public' => true ) ));
        } elseif ($type === 'custom_post_type') {
	        $allCustomPostTypes = array_keys(get_post_types(array( 'public' => true ) ));
        }
	    foreach ($criticalCssConfig as $locationConfigKey => $locationConfigValue) {
	        if ($type === 'custom_taxonomy') {
                $savedTax = str_replace($type.'_', '', $locationConfigKey);

                if (! in_array($savedTax, $allTaxonomies)) {
                    continue; // taxonomy not used anymore
                }
            } elseif ($type === 'custom_post_type') {
		        $savedCustomPostType = str_replace($type.'_', '', $locationConfigKey);

		        if (! in_array($savedCustomPostType, $allCustomPostTypes)) {
			        continue; // custom post type not used anymore
		        }
	        }

            if (isset($locationConfigValue['enable']) && $locationConfigValue['enable'] && strpos($locationConfigKey, $type.'_') === 0) {
                return true;
            }
        }

        return false;
    }

	/**
	 *
	 */
	public function updateCriticalCss()
	{
		if ( ! Misc::getVar('post', 'wpacu_critical_css_submit') ) {
			return;
		}

		$mainKeyForm = WPACU_PLUGIN_ID . '_critical_css';

		check_admin_referer('wpacu_critical_css_update', 'wpacu_critical_css_nonce');

		$locationKey = isset($_POST[$mainKeyForm]['location_key']) ? $_POST[$mainKeyForm]['location_key'] : false;

		if (! $locationKey) {
			return;
		}

		$enable     = isset($_POST[$mainKeyForm]['enable'])      ? $_POST[$mainKeyForm]['enable']  : false;
		$content    = isset($_POST[$mainKeyForm]['content'])     ? $_POST[$mainKeyForm]['content'] : '';
		$showMethod = isset($_POST[$mainKeyForm]['show_method']) ? $_POST[$mainKeyForm]['show_method'] : 'original';

		$optionToUpdate = WPACU_PLUGIN_ID . '_critical_css_config';

		$existingListEmpty = array();
		$existingListJson  = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		if ($enable && $content) {
			$existingList[$locationKey]['enable'] = true;
		} elseif (! $enable) {
			$existingList[$locationKey]['enable'] = false;
		}

		$existingList[$locationKey]['show_method'] = $showMethod;

		Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));

		$optionToUpdateForCssContent = WPACU_PLUGIN_ID . '_critical_css_location_key_'.$locationKey;

		if ($content) {
			$contentToSaveArray = array();
			$contentOriginal = $content;

			$contentToSaveArray['content_original'] = $contentOriginal;

			if ($showMethod === 'minified') {
				$contentToSaveArray['content_minified'] = MinifyCss::applyMinification($contentOriginal, true);

				if ($contentToSaveArray['content_minified'] === $contentToSaveArray['content_original']) {
					// No change? The content is already minified and there's no point in saving duplicate contents
					unset($contentToSaveArray['content_minified']);
				}
			}

			$optionValue = wp_json_encode($contentToSaveArray);
			Misc::addUpdateOption($optionToUpdateForCssContent, $optionValue);
		} else {
			delete_option($optionToUpdateForCssContent);
		}
	}
}
