<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;

/**
 * Class Misc
 * contains various common functions that are used by the plugin
 * @package WpAssetCleanUp
 */
class Misc
{
	/**
	 * @var array
	 */
	public static $potentialCachePlugins = array(
		'breeze/breeze.php', // Breeze WordPress Cache Plugin
		'cache-enabler/cache-enabler.php', // Cache Enabler
		'cachify/cachify.php', // Cachify
		'comet-cache/comet-cache.php', // Comet Cache
		'hyper-cache/plugin.php', // Hyper Cache
		'litespeed-cache/litespeed-cache.php', // LiteSpeed Cache
		'simple-cache/simple-cache.php', // Simple Cache
		'swift-performance-lite/performance.php', // Swift Performance Lite
		'w3-total-cache/w3-total-cache.php', // W3 Total Cache
		'wp-fastest-cache/wpFastestCache.php', // WP Fastest Cache
		'wp-rocket/wp-rocket.php', // WP Rocket
		'wp-super-cache/wp-cache.php' // WP Super Cache
	);

	/**
	 * @var array
	 */
	public $activeCachePlugins = array();

    /**
     * @var
     */
    public static $showOnFront;

	/**
	 *
	 */
	public function getActiveCachePlugins()
	{
		if (empty($this->activeCachePlugins)) {
			$activePlugins = self::getActivePlugins();

			foreach ( self::$potentialCachePlugins as $cachePlugin ) {
				if ( in_array( $cachePlugin, $activePlugins ) ) {
					$this->activeCachePlugins[] = $cachePlugin;
				}
			}
		}

		return $this->activeCachePlugins;
	}

    /**
     * @param $string
     * @param $start
     * @param $end
     * @return string
     */
    public static function extractBetween($string, $start, $end)
    {
        $pos = stripos($string, $start);

        $str = substr($string, $pos);

        $strTwo = substr($str, strlen($start));

        $secondPos = stripos($strTwo, $end);

        $strThree = substr($strTwo, 0, $secondPos);

        return trim($strThree); // remove whitespaces;
    }

	/**
	 * @param $string
	 * @param $endsWithString
	 * @return bool
	 */
	public static function endsWith($string, $endsWithString)
	{
		$stringLen = strlen($string);
		$endsWithStringLen = strlen($endsWithString);

		if ($endsWithStringLen > $stringLen) {
			return false;
		}

		return substr_compare(
			        $string,
			        $endsWithString,
			        $stringLen - $endsWithStringLen, $endsWithStringLen
		        ) === 0;
	}

	/**
	 * @param $content
	 *
	 * @return array|string|string[]|null
	 */
	public static function stripIrrelevantHtmlTags($content)
	{
		return preg_replace( '@<(script|style|iframe)[^>]*?>.*?</\\1>@si', '', $content );
	}

	/**
	 * @return bool
	 */
	public static function isHttpsSecure()
	{
		if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ) {
			return true;
		}

		if ( ( ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
		     || ( ! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) ) {
			// Is it behind a load balancer?
			return true;
		}

		return false;
	}

	/**
	 * @param $postId
	 *
	 * @return mixed
	 */
	public static function getPageUrl($postId)
    {
	    // Was the home page detected?
        if (self::isHomePage()) {
            if (get_site_url() !== get_home_url()) {
                $pageUrl = get_home_url();
            } else {
                $pageUrl = get_site_url();
            }

            return self::_filterPageUrl($pageUrl);
        }

	    // It's singular page: post, page, custom post type (e.g. 'product' from WooCommerce)
	    if ($postId > 0) {
		    return self::_filterPageUrl(get_permalink($postId));
	    }

	    // If it's not a singular page, nor the home page, continue...
	    // It could be: Archive page (e.g. author, category, tag, date, custom taxonomy), Search page, 404 page etc.
	    global $wp;

        $permalinkStructure = get_option('permalink_structure');

        if ($permalinkStructure) {
		    $pageUrl = home_url($wp->request);
	    } else {
		    $pageUrl = home_url($_SERVER['REQUEST_URI']);
	    }

        if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
	        list( $cleanRequestUri ) = explode( '?', $_SERVER['REQUEST_URI'] );
        } else {
	        $cleanRequestUri = $_SERVER['REQUEST_URI'];
        }

        if (substr($cleanRequestUri, -1) === '/') {
        	$pageUrl .= '/';
        }

        return self::_filterPageUrl($pageUrl);
    }

    /**
     * @param $postUrl
     * @return mixed
     */
    private static function _filterPageUrl($postUrl)
    {
        // If we are in the Dashboard on an HTTPS connection,
        // then we will make the AJAX call over HTTPS as well for the front-end
        // to avoid blocking
        if (self::isHttpsSecure() && strpos($postUrl, 'http://') === 0) {
            $postUrl = str_ireplace('http://', 'https://', $postUrl);
        }

        return $postUrl;
    }

	/**
	 * @param $postId
	 *
	 * @return string
	 */
	public static function getPageUri($postId)
    {
	    $parseUrl = parse_url(get_site_url());
	    $rootUrl = $parseUrl['scheme'].'://'.$parseUrl['host'];

	    $dbPageUrl = get_permalink($postId);

	    return str_replace( $rootUrl, '', $dbPageUrl );
    }

	/**
	 * @param $postTypes
	 *
	 * @return mixed
	 */
	public static function filterPostTypesList($postTypes)
	{
		foreach ($postTypes as $postTypeKey => $postTypeValue) {
			// Exclude irrelevant custom post types
			if (in_array($postTypeKey, MetaBoxes::$noMetaBoxesForPostTypes)) {
				unset($postTypes[$postTypeKey]);
			}

			// Polish existing values
			if ($postTypeKey === 'product' && self::isPluginActive('woocommerce/woocommerce.php')) {
				$postTypes[$postTypeKey] = 'product &#10230; WooCommerce';
			}

			if ($postTypeKey === 'download' && self::isPluginActive('easy-digital-downloads/easy-digital-downloads.php')) {
				$postTypes[$postTypeKey] = 'download &#10230; Easy Digital Downloads';
			}
		}

		return $postTypes;
	}

	/**
	 * Note: If plugins are disabled via "Plugins Manager" -> "IN THE DASHBOARD /wp-admin/"
	 * where the target pages require this function, the list could be incomplete if those plugins registered custom post types
	 *
	 * @param $postTypes
	 *
	 * @return mixed
	 */
	public static function filterCustomPostTypesList($postTypes)
	{
		foreach (array_keys($postTypes) as $postTypeKey) {
			if (in_array($postTypeKey, array('post', 'page', 'attachment'))) {
				unset($postTypes[$postTypeKey]); // no default post types
			}

			// Polish existing values
			if ($postTypeKey === 'product' && self::isPluginActive('woocommerce/woocommerce.php')) {
				$postTypes[$postTypeKey] = 'product &#10230; WooCommerce';
			}

			if ($postTypeKey === 'download' && self::isPluginActive('easy-digital-downloads/easy-digital-downloads.php')) {
				$postTypes[$postTypeKey] = 'download &#10230; Easy Digital Downloads';
			}
		}

		return $postTypes;
	}

	/**
	 * @param $postTypes
	 *
	 * @return mixed
	 */
	public static function filterCustomTaxonomyList($taxonomyList)
	{
		foreach (array_keys($taxonomyList) as $taxonomy) {
			if (in_array($taxonomy, array('category', 'post_tag', 'post_format'))) {
				unset($taxonomyList[$taxonomy]); // no default post types
			}

			// Polish existing values
			if ($taxonomy === 'product_cat' && self::isPluginActive('woocommerce/woocommerce.php')) {
				$taxonomyList[$taxonomy] = 'product_cat &#10230; Product\'s Category in WooCommerce';
			}
		}

		return $taxonomyList;
	}

	/**
	 * @return void
	 */
	public static function w3TotalCacheFlushObjectCache()
	{
		// Flush "W3 Total Cache" before printing the list as sometimes the old list shows after the CSS/JS manager is reloaded
		if (function_exists('w3tc_objectcache_flush') && self::isPluginActive('w3-total-cache/w3-total-cache.php')) {
			try {
				w3tc_objectcache_flush();
			} catch(\Exception $e) {}
		}
	}

	/**
	 * @return bool
	 */
	public static function isElementorMaintenanceModeOn()
    {
	    // Elementor's maintenance or coming soon mode
	    if (class_exists('\Elementor\Maintenance_Mode') && self::isPluginActive('elementor/elementor.php')) {
		    try {
			    $elementorMaintenanceMode = \Elementor\Maintenance_Mode::get( 'mode' ); // if any
			    if ( $elementorMaintenanceMode && in_array($elementorMaintenanceMode, array('maintenance', 'coming_soon')) ) {
					return true;
				    }
		    } catch (\Exception $err) {}
	    }

	    return false;
    }

	/**
	 * @return bool
	 */
	public static function isElementorMaintenanceModeOnForCurrentAdmin()
    {
    	if ( defined('WPACU_IS_ELEMENTOR_MAINTENANCE_MODE_TEMPLATE_ID') ) {
    		return true;
	    }

	    if (class_exists('\Elementor\Maintenance_Mode') && self::isPluginActive('elementor/elementor.php')) {
		    try {
			    // Elementor Template ID (Chosen for maintenance or coming soon mode)
			    $elementorMaintenanceModeTemplateId = \Elementor\Maintenance_Mode::get( 'template_id' );

			    if ( isset( $GLOBALS['post']->ID ) && (int)$elementorMaintenanceModeTemplateId === (int)$GLOBALS['post']->ID ) {
				    define( 'WPACU_IS_ELEMENTOR_MAINTENANCE_MODE_TEMPLATE_ID', $elementorMaintenanceModeTemplateId );
				    return true;
			    }
		    } catch (\Exception $err) {}
	    }

	    return false;
    }

    /**
     * @return bool
     */
    public static function isHomePage()
    {
	    // Docs: https://codex.wordpress.org/Conditional_Tags

	    // Elementor's Maintenance Mode is ON
	    if (defined('WPACU_IS_ELEMENTOR_MAINTENANCE_MODE_TEMPLATE_ID')) {
		    return false;
	    }

	    // "Your latest posts" -> sometimes it works as is_front_page(), sometimes as is_home())
	    // "A static page (select below)" -> In this case is_front_page() should work

	    // Sometimes neither of these two options are selected
	    // (it happens with some themes that have an incorporated page builder)
	    // and is_home() tends to work fine

	    // Both will be used to be sure the home page is detected

	    // VARIOUS SCENARIOS for "Your homepage displays" option from Settings -> Reading

	    // 1) "Your latest posts" is selected
	    if (self::getShowOnFront() === 'posts' && is_front_page()) {
	    	// Default homepage
	    	return true;
	    }

	    // 2) "A static page (select below)" is selected

	    // Note: Either "Homepage:" or "Posts page:" need to have a value set
	    // Otherwise, it will default to "Your latest posts", the other choice from "Your homepage displays"

	    if (self::getShowOnFront() === 'page') {
			$pageOnFront  = get_option('page_on_front');
			$pageForPosts = get_option('page_for_posts');

		    // "Homepage:" has a value
			if ($pageOnFront > 0 && is_front_page()) {
				// Static Homepage
				return true;
			}

		    // "Homepage:" has no value
			if (! $pageOnFront && self::isBlogPage()) {
				// Blog page
				return true;
			}

			// Both have values
		    if ($pageOnFront && $pageForPosts && ($pageOnFront !== $pageForPosts) && self::isBlogPage()) {
		    	return false; // Blog posts page (but not home page)
		    }

		    // Another scenario is when both 'Homepage:' and 'Posts page:' have values
		    // If we are on the blog page (which is "Posts page:" value), then it will return false
		    // As it's not the main page of the website
		    // e.g. Main page: www.yoursite.com - Blog page: www.yoursite.com/blog/
	    }

	    // Some WordPress themes such as "Extra" have their own custom value
	    return ( self::getShowOnFront() !== '' || self::getShowOnFront() === 'layout' )
               &&
               ( (is_home() || self::isBlogPage()) || self::isRootUrl() );
    }

	/**
	 * @return bool
	 */
	public static function isRootUrl()
	{
		$siteUrl = get_bloginfo('url');

		$urlPath = (string)parse_url($siteUrl, PHP_URL_PATH);

		$requestURI = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

		$urlPathNoForwardSlash = $urlPath;
		$requestURINoForwardSlash = $requestURI;

		if ($urlPath && substr($urlPath, -1) === '/') {
			$urlPathNoForwardSlash = substr($urlPath, 0, -1);
		}

		if ($requestURI && substr($requestURI, -1) === '/') {
			$requestURINoForwardSlash = substr($requestURI, 0, -1);
		}

		return $urlPathNoForwardSlash === $requestURINoForwardSlash;
	}

	/**
	 * @param $handleData
	 *
	 * @return bool
	 */
	public static function isCoreFile($handleData)
    {
	    $handleData = (object)$handleData;

	    $part = str_replace(
		    array(
			    'http://',
			    'https://',
			    '//'
		    ),
		    '',
		    $handleData->src
	    );

	    $parts     = explode('/', $part);
	    $parentDir = isset($parts[1]) ? $parts[1] : '';

	    // Loaded from WordPress directories (Core)
	    return in_array( $parentDir, array( 'wp-includes', 'wp-admin' ) ) || strpos( $handleData->src,
			    '/'.self::getPluginsDir('dir_name').'/jquery-updater/js/jquery-' ) !== false;
    }

	/**
     * This function does not just detect the fact that the call is a local one
     * But it will return true ONLY if the actual asset file (CSS/JS) actually exists
     *
	 * @param $src
	 *
	 * @return array
	 */
	public static function getLocalSrcIfExist($src)
    {
    	if (! $src) {
    	    return array();
	    }

    	// Clean it up first
	    if (strpos($src, '.css?') !== false) {
	    	list($src) = explode('.css?', $src);
		    $src .= '.css';
	    }

	    if (strpos($src, '.js?') !== false) {
		    list($src) = explode('.js?', $src);
		    $src .= '.js';
	    }

	    $paths = array('wp-includes/', 'wp-content/');

	    foreach ($paths as $path) {
	    	if (strpos($src, $path) !== false) {
	    		list ($baseUrl, $relSrc) = explode($path, $src);

                $fileFullRelPath = $path . $relSrc;

	    		$localPathToFile = self::getWpRootDirPathBasedOnPath($fileFullRelPath) . $fileFullRelPath;

	    		if (is_file($localPathToFile)) {
	    			return array('base_url' => $baseUrl, 'rel_src' => $fileFullRelPath, 'file_exists' => 1);
			    }
		    }
	    }

	    return array();
    }

    /**
     * This one checks if the "src" is local
     * Without the need for the CSS/JS file to exist
     * e.g. /index.php?value will return true if it's within the same domain
     * e.g. www.cdn-domain.com/wp-content/custom-file-name.css will return true
     * if the local file /wp-content/custom-file-name.css exists as some people use CDN
     *
     *
     * @param $src
     *
     * @return bool
     */
    public static function isLocalSrc($src)
    {
        if (self::getLocalSrcIfExist($src)) {
            return true;
        }

        if (strpos(str_replace(site_url(), '', $src), '?') === 0) {
            // Starts with ? right after the site url (it's a local URL)
            return true;
        }

        if (strpos($src, '?') !== false && strpos($src, site_url()) !== false) {
            // Dynamic Local URL (e.g. it could be something like /index.php?load_css=value
            return true;
        }

        return false;
    }

	/**
	 * @param bool $clean
	 *
	 * @return mixed|string
	 */
	public static function getCurrentPageUrl($clean = true)
    {
	    $currentPageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . parse_url(site_url(), PHP_URL_HOST) . $_SERVER['REQUEST_URI'];

	    if ($clean && strpos($currentPageUrl, '?') !== false) {
		    list($currentPageUrl) = explode('?', $currentPageUrl);
	    }

	    return $currentPageUrl;
    }

	/**
	 * @param $src
	 * @param $assetKey
	 *
	 * @return string|string[]
	 */
	public static function assetFromHrefToRelativeUri($src, $assetKey)
    {
	    // Make the "src" relative in case the information will be imported from Staging to Live, it won't show the handle's link referencing to the staging URL in the "Overview" page and other similar pages, as it's confusing
	    $localAssetPath = OptimizeCommon::getLocalAssetPath($src, (($assetKey === 'styles') ? 'css' : 'js'));

	    $relSrc = $src;

	    if ($localAssetPath) {
		    $relSrc = str_replace(self::getWpRootDirPathBasedOnPath($relSrc), '', $relSrc);
	    }

	    $relSrc = str_replace(site_url(), '', $relSrc);

	    // Does it start with '//'? (protocol is missing) - the replacement above wasn't made
	    if (strpos($relSrc, '//') === 0) {
		    $siteUrlNoProtocol = str_replace(array('http:', 'https:'), '', site_url());
		    $relSrc = str_replace($siteUrlNoProtocol, '', $relSrc);
	    }

	    return $relSrc;
    }

    /**
     * This is a basic RegEx check that is used in case there's a match
     * This is to avoid further resources to detect the 'src' (e.g. via DOMDocument)
     *
     * @param $tagOutput
     *
     * @return bool
     */
    public static function isScriptTagWithSrcRegExCheck($tagOutput)
    {
        // This is good for tags such as <script > alert('<script src="">');</script>
        if (preg_match_all('#<script(\s+|)>#Umi', $tagOutput, $matches) && isset($matches[0][0]) && strpos($tagOutput, $matches[0][0]) === 0) {
            return false; // no "src"
        }

        if ( preg_match(
            '#<script(\s+)type=(\'|"|)text/javascript(\'|"|)(\s+|)>'.'|'.
            '<script(\s+)id=(\'|"|)(.*?)(\s+)type=(\'|"|)text/javascript(\'|"|)(\s+|)>'.'|'.
            '<script(\s+)type=(\'|"|)text/javascript(\'|"|)(\s+)id=(\'|"|)(.*?)(\'|"|)(\s+|)>#Usmi',
            $tagOutput
        ) ) {
            return false; // no "src"
        }

        if (preg_match('#src(\s+|)=(\s+|)("|\'|)(.*)("|\'|)|src(\s+|)=(\s+|)(.*)(\s+)#Usmi', $tagOutput)) {
            return true;
        }

        return false;
    }

	/**
	 * @param $tagOutput ('script', 'link')
	 * @param $attribute
	 *
	 * @return false|string
	 */
	public static function getValueFromTag($tagOutput, $attribute = '', $method = 'regex')
	{
		$tagOutput = trim($tagOutput);

		if ( strpos( $tagOutput, '<script' ) === 0 ) {
			$tagNameToCheck = 'script';

			if ($attribute === '') {
				$attribute = 'src';
			}

            // First, do some basic check (maybe there's no attribute there, and there's no need to continue)
            if ($attribute === 'src' && ! self::isScriptTagWithSrcRegExCheck($tagOutput)) {
                return false;
            }
		} elseif ( strpos( $tagOutput, '<link' ) === 0 ) {
			$tagNameToCheck = 'link';

			if ($attribute === '') {
				$attribute = 'href';
			}
		} elseif ( strpos( $tagOutput, '<style' ) === 0 ) {
			$tagNameToCheck = 'style';

			if ($attribute === '') {
				$attribute = 'type';
			}
		} else {
			return false; // the tag it neither 'script' nor 'link'
		}

		if ($method === 'dom_with_fallback') {
			if (self::isDOMDocumentOn()) {
				$domForTag = self::initDOMDocument();

				$domForTag->loadHTML( $tagOutput );

				$scriptTagObj = $domForTag->getElementsByTagName( $tagNameToCheck )->item( 0 );

				if ( $scriptTagObj === null ) {
					return false;
				}

				if ( $scriptTagObj->hasAttributes() ) {
					foreach ( $scriptTagObj->attributes as $attrObj ) {
                        if ( $attrObj->nodeName === $attribute ) {
							return trim( $attrObj->nodeValue );
						}
					}

                    return ''; // There's no "src"
				}
			}

			return self::getValueFromTagViaRegEx($tagOutput, $attribute);
		}

		if ($method === 'regex') {
			return self::getValueFromTagViaRegEx($tagOutput, $attribute);
		}

		return false;
	}

	/**
	 * @param $tagOutput
	 * @param $attribute
	 *
	 * @return false|string
	 */
	public static function getValueFromTagViaRegEx($tagOutput, $attribute = '')
	{
		$tagOutput = trim( $tagOutput );

		if ( strpos($tagOutput, '<script') === 0 ) {
			if ( $attribute === '' ) {
				$attribute = 'src';
			}

			// Perhaps the strung "src" is inside an inline JS tag which would make the source value irrelevant
			// We only need the "src" attribute from a SCRIPT tag that loads a .js file (without any inline JS code)
			$tagOutputNoTags = trim(strip_tags($tagOutput));

			if ($tagOutputNoTags !== '' && stripos($tagOutputNoTags, 'src') !== false) {
				// This is an inline tag such as the following:
				// <script>j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;</script>
				return false;
			}

			preg_match_all( '#<script.*?'.$attribute.'\s*=\s*(.*?)#Usmi', $tagOutput, $outputMatches );
		}

		if ( strpos($tagOutput, '<link') === 0 ) {
			if ( $attribute === '' ) {
				$attribute = 'href';
			}

			preg_match_all( '#<link.*?'.$attribute.'\s*=\s*(.*?)#Usmi', $tagOutput, $outputMatches );
		}

		if ( strpos($tagOutput, '<style') === 0 ) {
			if ( $attribute === '' ) {
				$attribute = 'type';
			}

			preg_match_all( '#<style.*?'.$attribute.'\s*=\s*(.*?)#Usmi', $tagOutput, $outputMatches );
		}

		if ( isset($outputMatches[1][0]) && $outputMatches[1][0] ) {
			$scriptPart = trim($outputMatches[1][0]);

			foreach ( array('"', "'") as $quoteType ) {
				if ( $scriptPart[0] === $quoteType ) {
					$scriptPartTwo = ltrim( $scriptPart, $quoteType );

					$posEndingQuote = strpos( $scriptPartTwo, $quoteType );

					if ( $posEndingQuote === false ) {
						return false;
					}

					return trim ( substr( $scriptPartTwo, 0, $posEndingQuote ) );
				}
			}

			if ( ! in_array($scriptPart[0], array('"', "'") ) ) { // no quotes, just space or no wrapper
				$scriptPartTwo = ltrim( $scriptPart );

				$posFirstSpace = strpos( $scriptPartTwo, ' ' );

				if ($posFirstSpace === false ) {
					return false;
				}

				return trim( substr( $scriptPartTwo, 0, $posFirstSpace ) );
			}
		}

		return false;
	}

	/**
	 * @param $postType
	 *
	 * @return false[]
	 */
	public static function isValidPostType($postType)
	{
		global $wpdb;

		$status = array('has_records' => false); // default

		$hasRecords = $wpdb->get_var('SELECT COUNT(*) FROM `'.$wpdb->posts.'` WHERE post_type=\''.$postType.'\'');

		if ($hasRecords) {
			$status['has_records'] = $hasRecords;
		}

		return $status;
	}

	/**
	 * @return bool
	 */
	public static function isBlogPage()
    {
    	return (is_home() && !is_front_page());
    }

    /**
     * @return mixed
     */
    public static function getShowOnFront()
    {
        if (! self::$showOnFront) {
            self::$showOnFront = get_option('show_on_front');
        }

        return self::$showOnFront;
    }

	/**
	 * @param $plugin
	 *
	 * @return bool
	 */
	public static function isPluginActive($plugin)
	{
		// Site level check
		if (in_array( $plugin, (array) get_option( 'active_plugins', array() ), true )) {
			return true;
		}

		// Multisite check
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' );

		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}

		return false;
    }

	/**
	 * @return bool
	 */
	public static function isWpRocketMinifyHtmlEnabled()
    {
    	// Only relevant if WP Rocket's version is below 3.7
	    if (defined('WP_ROCKET_VERSION') && version_compare(WP_ROCKET_VERSION, '3.7') >= 0) {
	    	return false;
	    }

		if (self::isPluginActive('wp-rocket/wp-rocket.php')) {
			if (function_exists('get_rocket_option')) {
				$wpRocketMinifyHtml = trim(get_rocket_option('minify_html')) ?: false;
			} else {
				$wpRocketSettings = get_option('wp_rocket_settings');
				$wpRocketMinifyHtml = (isset($wpRocketSettings['minify_html']) && $wpRocketSettings['minify_html']);
			}

			return $wpRocketMinifyHtml;
		}

		return false;
    }

	/**
	 * If it matches true, it's very likely there is no need for the Gutenberg CSS Block Library
	 * The user will be reminded about it
	 *
	 * @return bool
	 */
	public static function isClassicEditorUsed()
    {
    	if (self::isPluginActive('classic-editor/classic-editor.php')) {
    		$ceReplaceOption = get_option('classic-editor-replace');
			$ceAllowUsersOption = get_option('classic-editor-allow-users');

    		if ($ceReplaceOption === 'classic' && $ceAllowUsersOption === 'disallow') {
    		    return true;
		    }
	    }

    	return false;
    }

	/**
	 *
	 * @return array|string|void
	 */
	public static function getWpCoreCssHandlesFromWpIncludesBlocks()
	{
		$transientName = 'wpacu_wp_core_css_handles_from_wp_includes_blocks';

		if ($transientValues = get_transient($transientName)) {
			return $transientValues;
		}

		$blocksDir = ABSPATH.'wp-includes/blocks/';

		$cssCoreHandlesList = array();

		if (is_dir($blocksDir)) {
			$list = scandir($blocksDir);

			if ( ! empty($list) && count($list) > 2 ) {
				foreach ($list as $fileOrDir) {
					$targetJsonFile = $blocksDir.$fileOrDir.'/block.json';

					if (is_dir($blocksDir.$fileOrDir) && is_file($targetJsonFile)) {
						$jsonToArray = function_exists('wp_json_file_decode') ? wp_json_file_decode($targetJsonFile, array('associative' => true))
						    : self::wpJsonFileDecode($targetJsonFile, array( 'associative' => true));

						if (isset($jsonToArray['style'])) {
							if ( is_array( $jsonToArray['style'] ) ) {
								foreach ( $jsonToArray['style'] as $style ) {
									$cssCoreHandlesList[] = $style;
								}
							} else {
								$cssCoreHandlesList[] = $jsonToArray['style'];
							}
						}

						if (isset($jsonToArray['editorStyle'])) {
							if ( is_array( $jsonToArray['editorStyle'] ) ) {
								foreach ( $jsonToArray['editorStyle'] as $editorStyle ) {
									$cssCoreHandlesList[] = $editorStyle;
								}
							} else {
								$cssCoreHandlesList[] = $jsonToArray['editorStyle'];
							}
						}
					}
				}

				foreach ($cssCoreHandlesList as $style) {
					if (self::endsWith($style, '-editor')) {
						$cssCoreHandlesList[] = substr($style, 0, -strlen('-editor'));
					}
				}

				$cssCoreHandlesList = array_unique($cssCoreHandlesList);
			}
		} else {
			// Different WordPress version, perhaps no longer using that directory
			set_transient($transientName, array(), 3600 * 24 * 7);

			return array();
		}

		if ( ! empty($cssCoreHandlesList) ) {
			set_transient($transientName, $cssCoreHandlesList, 3600 * 24 * 7);

			return $cssCoreHandlesList;
		}
	}

	/**
	 * Fallback in the case the WordPress version is below 5.9.0
	 *
	 * @param $filename
	 * @param $options
	 *
	 * @return mixed|null
	 */
	public static function wpJsonFileDecode( $filename, $options = array() )
	{
		$filename = wp_normalize_path( realpath( $filename ) );

		if ( ! is_file( $filename ) ) {
			trigger_error(
				sprintf(
					/* translators: %s: Path to the JSON file. */
					__( "File %s doesn't exist!" ),
					$filename
				)
			);
			return null;
		}

		$options      = wp_parse_args( $options, array( 'associative' => false ) );
		$decoded_file = json_decode( file_get_contents( $filename ), $options['associative'] );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			trigger_error(
				sprintf(
				/* translators: 1: Path to the JSON file, 2: Error message. */
					__( 'Error when decoding a JSON file at path %1$s: %2$s' ),
					$filename,
					json_last_error_msg()
				)
			);
			return null;
		}

		return $decoded_file;
	}

	/**
	 * @return bool
	 */
	public static function isDOMDocumentOn()
	{
		return function_exists('libxml_use_internal_errors') && function_exists('libxml_clear_errors') && class_exists('\DOMDocument') && class_exists('\DOMXpath');
	}

	/**
	 * @return \DOMDocument
	 */
	public static function initDOMDocument()
	{
		$dom = new \DOMDocument();

		// Any document errors reported in the HTML source (lots of websites have them) are irrelevant for the functionality of the plugin
		libxml_use_internal_errors(true);

		return $dom;
	}

	/**
	 * @param $e
	 *
	 * @return string
	 */
	public static function getOuterHTML( $e )
	{
		$doc = self::initDOMDocument();

		$doc->appendChild( $doc->importNode( $e, true ) );

		return trim( $doc->saveHTML() );
	}

	/**
	 * @return array|string
	 */
	public static function getW3tcMasterConfig()
	{
		if (! ObjectCache::wpacu_cache_get('wpacu_w3tc_master_config')) {
			$w3tcConfigMasterFile = WP_CONTENT_DIR . '/w3tc-config/master.php';
			$w3tcMasterConfig = FileSystem::fileGetContents($w3tcConfigMasterFile);
			ObjectCache::wpacu_cache_set('wpacu_w3tc_master_config', trim($w3tcMasterConfig));
		} else {
			$w3tcMasterConfig = ObjectCache::wpacu_cache_get('wpacu_w3tc_master_config');
		}

		return $w3tcMasterConfig;
	}

	/**
	 * @param bool $forceReturn
	 *
	 * @return string
	 */
	public static function preloadAsyncCssFallbackOutput($forceReturn = false)
	{
		// Unless it has to be returned (e.g. for debugging purposes), check it if it was returned before
		// To avoid duplicated HTML code
		if (! $forceReturn) {
			if ( defined( 'WPACU_PRELOAD_ASYNC_SCRIPT_SHOWN' ) ) {
				return '';
			}

			define( 'WPACU_PRELOAD_ASYNC_SCRIPT_SHOWN', 1 ); // mark it as already printed
		}

		return <<<HTML
<script id="wpacu-preload-async-css-fallback">
/*! LoadCSS. [c]2020 Filament Group, Inc. MIT License */
/* This file is meant as a standalone workflow for
- testing support for link[rel=preload]
- enabling async CSS loading in browsers that do not support rel=preload
- applying rel preload css once loaded, whether supported or not.
*/
(function(w){"use strict";var wpacuLoadCSS=function(href,before,media,attributes){var doc=w.document;var ss=doc.createElement('link');var ref;if(before){ref=before}else{var refs=(doc.body||doc.getElementsByTagName('head')[0]).childNodes;ref=refs[refs.length-1]}
var sheets=doc.styleSheets;if(attributes){for(var attributeName in attributes){if(attributes.hasOwnProperty(attributeName)){ss.setAttribute(attributeName,attributes[attributeName])}}}
ss.rel="stylesheet";ss.href=href;ss.media="only x";function ready(cb){if(doc.body){return cb()}
setTimeout(function(){ready(cb)})}
ready(function(){ref.parentNode.insertBefore(ss,(before?ref:ref.nextSibling))});var onwpaculoadcssdefined=function(cb){var resolvedHref=ss.href;var i=sheets.length;while(i--){if(sheets[i].href===resolvedHref){return cb()}}
setTimeout(function(){onwpaculoadcssdefined(cb)})};function wpacuLoadCB(){if(ss.addEventListener){ss.removeEventListener("load",wpacuLoadCB)}
ss.media=media||"all"}
if(ss.addEventListener){ss.addEventListener("load",wpacuLoadCB)}
ss.onwpaculoadcssdefined=onwpaculoadcssdefined;onwpaculoadcssdefined(wpacuLoadCB);return ss};if(typeof exports!=="undefined"){exports.wpacuLoadCSS=wpacuLoadCSS}else{w.wpacuLoadCSS=wpacuLoadCSS}}(typeof global!=="undefined"?global:this))
</script>
HTML;
	}

	/**
	 * @param $array
	 *
	 * @return string
	 */
	public static function arrayKeyFirst($array)
	{
		if (function_exists('array_key_first')) {
			return array_key_first($array);
		}

		$arrayKeys = array_keys($array);

		return $arrayKeys[0];
	}

	/**
	 * @return int
	 */
	public static function jsonLastError()
	{
		if (function_exists('json_last_error')) {
			return json_last_error();
		}

		// Fallback (notify the user through a warning)
		return 0;
	}

	/**
	 * @param $requestMethod
	 * @param $key
	 * @param mixed $defaultValue
	 *
	 * @return mixed
	 */
	public static function getVar($requestMethod, $key, $defaultValue = '')
    {
	    if ($requestMethod === 'get' && $key && isset($_GET[$key])) {
		    return $_GET[$key];
	    }

		if ($requestMethod === 'post' && $key && isset($_POST[$key])) {
			return $_POST[$key];
		}

	    if ($requestMethod === 'request' && $key && isset($_REQUEST[$key])) {
		    return $_REQUEST[$key];
	    }

	    return $defaultValue;
    }

	/**
	 * @param $requestMethod
	 * @param $key
	 *
	 * @return bool
	 */
	public static function isValidRequest($requestMethod, $key)
    {
	    if ($requestMethod === 'post' && $key && ! empty($_POST[$key])) {
		    return true;
	    }

	    if ($requestMethod === 'get' && $key && ! empty($_GET[$key])) {
		    return true;
	    }

	    return false;
    }

	/**
	 * @param $pageId
	 */
	public static function doNotApplyOptimizationOnPage($pageId)
    {
    	// Do not trigger the code below if there is already a change in place
    	if (get_post_meta($pageId, '_' . WPACU_PLUGIN_ID . '_page_options', true)) {
    	    return;
	    }

	    $pageOptionsJson = wp_json_encode(array(
		    'no_css_minify'   => 1,
		    'no_css_optimize' => 1,
		    'no_js_minify'    => 1,
		    'no_js_optimize'  => 1
	    ));

	    if (! add_post_meta($pageId, '_' . WPACU_PLUGIN_ID . '_page_options', $pageOptionsJson, true)) {
		    update_post_meta($pageId, '_' . WPACU_PLUGIN_ID . '_page_options', $pageOptionsJson);
	    }
    }

	/**
	 * @param $optionName
	 * @param $optionValue
	 * @param string $autoload
     *
     * @return bool|void
	 */
	public static function addUpdateOption($optionName, $optionValue, $autoload = 'no')
    {
		$optionValue = is_string($optionValue) ? trim($optionValue) : $optionValue;

	    // Empty array encoded into JSON; No point in keeping the option in the database if it's already there
	    if ($optionValue === '[]') {
		    delete_option($optionName);
		    return;
	    }

    	// Nothing in the database? Since option does not exist, add it
    	if (get_option($optionName) === false) {
		    add_option($optionName, $optionValue, '', $autoload);
		    return;
	    }

		// get_option($optionName) didn't return false, thus the option is either an empty string or it has a value
	    // either way, it exists in the database, and the update will be triggered

    	// Value is in the database already | Update it
    	return update_option($optionName, $optionValue, $autoload);
    }

	/**
	 * @param $type
	 * e.g. 'per_page' will fetch only per page rules, excluding the bulk ones
	 * such as unload everywhere, on this post type etc.
	 *
	 * @return int
	 */
	public static function getTotalUnloadedAssets($type = 'all')
	{
		if ($unloadedTotalAssets = get_transient(WPACU_PLUGIN_ID. '_total_unloaded_assets_'.$type)) {
			return $unloadedTotalAssets;
		}

		global $wpdb;

		$frontPageNoLoad      = get_option(WPACU_PLUGIN_ID . '_front_page_no_load');
		$frontPageNoLoadArray = json_decode($frontPageNoLoad, ARRAY_A);

		$unloadedTotalAssets = 0;

		// Home Page: Unloads
		if (isset($frontPageNoLoadArray['styles'])) {
			$unloadedTotalAssets += count($frontPageNoLoadArray['styles']);
		}

		if (isset($frontPageNoLoadArray['scripts'])) {
			$unloadedTotalAssets += count($frontPageNoLoadArray['scripts']);
		}

		// Posts, Pages, Custom Post Types: Individual Page Unloads
		$sqlPart = '_' . WPACU_PLUGIN_ID . '_no_load';
		$sqlQuery = <<<SQL
SELECT pm.meta_value FROM `{$wpdb->prefix}postmeta` pm
LEFT JOIN `{$wpdb->prefix}posts` p ON (p.ID = pm.post_id)
WHERE (p.post_status='publish' OR p.post_status='private') AND pm.meta_key='{$sqlPart}'
SQL;

		$sqlResults = $wpdb->get_results($sqlQuery, ARRAY_A);

		if (! empty($sqlResults)) {
			foreach ($sqlResults as $row) {
				$metaValue    = $row['meta_value'];
				$unloadedList = @json_decode($metaValue, ARRAY_A);

				if (empty($unloadedList)) {
					continue;
				}

				foreach ($unloadedList as $assets) {
					if (! empty($assets)) {
						$unloadedTotalAssets += count($assets);
					}
				}
			}
		}

		if ($type === 'all') {
			$unloadedTotalAssets += self::getTotalBulkUnloadsFor( 'all' );
		}

		// To avoid the complex SQL query next time
		set_transient(WPACU_PLUGIN_ID. '_total_unloaded_assets_'.$type, $unloadedTotalAssets, 28800);

		return $unloadedTotalAssets;
	}

	/**
	 * @param string $for
	 *
	 * @return int
	 */
	public static function getTotalBulkUnloadsFor($for)
	{
		$unloadedTotalAssets = 0;

		if (in_array($for, array('everywhere', 'all'))) {
			// Everywhere (Site-wide) unloads
			$globalUnloadListJson = get_option(WPACU_PLUGIN_ID . '_global_unload');
			$globalUnloadArray    = @json_decode($globalUnloadListJson, ARRAY_A);

			foreach (array('styles', 'scripts') as $assetType) {
				if ( ! empty( $globalUnloadArray[$assetType] ) ) {
					$unloadedTotalAssets += count( $globalUnloadArray[$assetType] );
				}
			}
		}

		if (in_array($for, array('bulk', 'all'))) {
			// Any bulk unloads? e.g. unload specific CSS/JS on all pages of a specific post type
			$bulkUnloadListJson = get_option(WPACU_PLUGIN_ID . '_bulk_unload');
			$bulkUnloadArray  = @json_decode($bulkUnloadListJson, ARRAY_A);

			$bulkUnloadedAllTypes = array('search', 'date', '404', 'taxonomy', 'post_type', 'author');
			foreach (array('styles', 'scripts') as $assetType) {
				if ( isset( $bulkUnloadArray[ $assetType ] ) ) {
					foreach ( array_keys( $bulkUnloadArray[ $assetType ] ) as $dataType ) {
						if ( strpos( $dataType, 'custom_post_type_archive_' ) !== false ) {
							$bulkUnloadedAllTypes[] = $dataType;
						}
					}
				}
			}

			foreach ( $bulkUnloadedAllTypes as $bulkUnloadedType ) {
				if (in_array($bulkUnloadedType, array('search', 'date', '404')) || (strpos($bulkUnloadedType, 'custom_post_type_archive_') !== false)) {
					foreach (array('styles', 'scripts') as $assetType) {
						if ( ! empty( $bulkUnloadArray[$assetType][ $bulkUnloadedType ] ) ) {
							$unloadedTotalAssets += count( $bulkUnloadArray[$assetType][ $bulkUnloadedType ] );
						}
					}
				} elseif ($bulkUnloadedType === 'author') {
					foreach (array('styles', 'scripts') as $assetType) {
						if ( ! empty( $bulkUnloadArray[$assetType][ $bulkUnloadedType ]['all']) )  {
							$unloadedTotalAssets += count( $bulkUnloadArray[$assetType][ $bulkUnloadedType ]['all'] );
						}
					}
				} elseif (in_array($bulkUnloadedType, array('post_type', 'taxonomy'))) {
					foreach (array('styles', 'scripts') as $assetType) {
						if ( ! empty( $bulkUnloadArray[$assetType][ $bulkUnloadedType ] ) ) {
							foreach ( $bulkUnloadArray[$assetType][ $bulkUnloadedType ] as $objectValues ) {
								$unloadedTotalAssets += count( $objectValues );
							}
						}
					}
				}
			}
		}

		return $unloadedTotalAssets;
	}

	/**
	 * @param string $get
	 *
	 * @return false|string
	 */
	public static function getPluginsDir($get = 'rel_path')
	{
		$return = '';
		$relPath = trim( str_replace( self::getWpRootDirPath(), '', WP_PLUGIN_DIR ), '/' );

		if ($get === 'rel_path') {
			$return = $relPath;
		} elseif ($get === 'dir_name') {
			$return = substr(strrchr($relPath, '/'), 1);
		}

		return $return;
	}

	/**
	 * @return string
	 */
	public static function getThemesDirRel()
	{
		$relPathCurrentTheme = str_replace( site_url(), '', get_template_directory_uri() );

		$posLastForwardSlash = strrpos($relPathCurrentTheme,'/');

		return substr($relPathCurrentTheme, 0, $posLastForwardSlash) . '/';
	}

    /**
     * @return array
     */
    public static function getUrlsToThemeDirs()
    {
        $urlsToThemeDirs = array();

        foreach (search_theme_directories() as $themeDir => $themeDirArray) {
            $themeUrl = '/'. str_replace(
                '//',
                '/',
                str_replace(self::getWpRootDirPath(), '', $themeDirArray['theme_root']) . '/'. $themeDir . '/'
            );

            $urlsToThemeDirs[] = $themeUrl;
        }

        return array_unique($urlsToThemeDirs);
    }

	/**
	 * Needed when the plugins' directory is different from the default one: /wp-content/plugins/
	 *
	 * @param $values
	 *
	 * @return array
	 */
	public static function replaceRelPluginPath($values)
	{
		$relPluginPath = self::getPluginsDir();

		if ($relPluginPath !== 'wp-content/plugins') {
			return array_filter( $values, function( $value ) use ( $relPluginPath ) {
				return str_replace( '/wp-content/plugins/', '/' . $relPluginPath . '/', $value );
			} );
		}

		return $values;
	}

	/**
	 * @param $src
	 *
	 * @return bool|array
	 */
	public static function maybeIsInactiveAsset($src)
	{
		$pluginsDirRel = self::getPluginsDir();

		$srcAlt = $src;

		if ( strpos( $srcAlt, '//' ) === 0 ) {
			$srcAlt = str_replace( str_replace( array( 'http://', 'https://' ), '//', site_url() ), '', $srcAlt );
		}

		$relSrc = str_replace( site_url(), '', $srcAlt );

		/*
		 * [START] plugin path
		 */
		if (strpos($src, $pluginsDirRel) !== false) {
			// Quickest way
			preg_match_all( '#/' . $pluginsDirRel . '/(.*?)/#', $src, $matches, PREG_PATTERN_ORDER );

			if ( isset( $matches[1][0] ) && $matches[1][0] ) {
				$pluginDirName = $matches[1][0];

				$activePlugins    = self::getActivePlugins();
				$activePluginsStr = implode( ',', $activePlugins );

				if ( strpos( $activePluginsStr, $pluginDirName . '/' ) === false ) {
					return array(
						'from' => 'plugin',
						'name' => $pluginDirName
					); // it belongs to an inactive plugin
				}
			}

			$relPluginsUrl = str_replace( site_url(), '', plugins_url() );

			if ( strpos( $relSrc, '/' . $pluginsDirRel ) !== false ) {
				list ( , $relSrc ) = explode( '/' . $pluginsDirRel, $relSrc );
			}

			if ( strpos( $relSrc, $relPluginsUrl ) !== false ) {
				// Determine the plugin behind the $src
				$relSrc = trim( str_replace( $relPluginsUrl, '', $relSrc ), '/' );

				if ( strpos( $relSrc, '/' ) !== false ) {
					list ( $pluginDirName, ) = explode( '/', $relSrc );

					$activePlugins    = self::getActivePlugins();
					$activePluginsStr = implode( ',', $activePlugins );

					if ( strpos( $activePluginsStr, $pluginDirName . '/' ) === false ) {
						return array(
							'from' => 'plugin',
							'name' => $pluginDirName
						); // it belongs to an inactive plugin
					}
				}
			}
		}
		/*
		 * [END] plugin path
		 */

		/*
		 * [START] theme path
		 */
		$themesDirRel = self::getThemesDirRel();

		if (strpos($relSrc, $themesDirRel) !== false) {
			if ( strpos( $relSrc, $themesDirRel ) !== false ) {
				list ( , $relSrc ) = explode( $themesDirRel, $relSrc );
			}

			if ( strpos( $relSrc, '/' ) !== false ) {
				list ( $themeDirName, ) = explode( '/', $relSrc );
			}

			if (isset($themeDirName)) {
				$activeThemes = self::getActiveThemes();

				if ( ! empty( $activeThemes ) && ! in_array($themeDirName, $activeThemes) ) {
					return array(
						'from' => 'theme',
						'name' => $themeDirName
					);
				}
			}
		}
		/*
		 * [END] theme path
		 */

		return false;
	}

	/**
	 * @return array
	 */
	public static function getActivePlugins($type = 'all')
	{
		$wpacuActivePlugins = array();

		if (in_array($type, array('site', 'all'))) {
			$wpacuActivePlugins = (array) get_option( 'active_plugins', array() );
		}

		// In case we're dealing with a MultiSite setup
		if (in_array($type, array('network', 'all')) && is_multisite()) {
			$wpacuActiveSiteWidePlugins = (array)get_site_option('active_sitewide_plugins', array());

			if ( ! empty($wpacuActiveSiteWidePlugins) ) {
				foreach (array_keys($wpacuActiveSiteWidePlugins) as $activeSiteWidePlugin) {
					$wpacuActivePlugins[] = $activeSiteWidePlugin;
				}
			}
		}

		return array_unique($wpacuActivePlugins);
	}

	/**
	 * @return array
	 */
	public static function getActiveThemes()
	{
		$activeThemes     = array();
		$currentThemeSlug = get_stylesheet();

		if ( current_user_can( 'switch_themes' ) ) {
			$themes = wp_get_themes( array( 'allowed' => true ) );
		} else {
			$themes = array( wp_get_theme() );
		}

		foreach ( $themes as $theme ) {
			$themeSlug = $theme->get_stylesheet();

			if ( $themeSlug === $currentThemeSlug ) {
				// Make sure both the parent and the child theme are in the list of active themes
				// in case there are references from
				$activeThemes[] = $currentThemeSlug;

				$childEndsWith = '-child';
				if ( self::endsWith( $currentThemeSlug, $childEndsWith ) ) {
					$activeThemes[] = substr( $currentThemeSlug, 0, - strlen( $childEndsWith ) );
				} else {
					$activeThemes[] = $currentThemeSlug . $childEndsWith;
				}
			}
		}

		return $activeThemes;
	}

	/**
	 * @return array
	 */
	public static function getCachedActiveFreePluginsIcons()
	{
		$activePluginsIconsJson = get_transient( 'wpacu_active_plugins_icons' );

		if ( $activePluginsIconsJson ) {
			$activePluginsIcons = @json_decode( $activePluginsIconsJson, ARRAY_A );

			if ( ! empty( $activePluginsIcons ) && is_array( $activePluginsIcons ) ) {
				return $activePluginsIcons;
			}
		}

		return array(); // default
	}

	/**
	 * @return array|bool|mixed|object
	 */
	public static function fetchActiveFreePluginsIconsFromWordPressOrg()
    {
	    $allActivePlugins = self::getActivePlugins();

	    if (empty($allActivePlugins)) {
	    	return array();
	    }

	    foreach ($allActivePlugins as $activePlugin) {
		    if (! is_string($activePlugin) || strpos($activePlugin, '/') === false) {
	    		continue;
		    }

	    	list($pluginSlug) = explode('/', $activePlugin);
		    $pluginSlug = trim($pluginSlug);

	    	if (! $pluginSlug) {
	    		continue;
		    }

	    	// Avoid the calls to WordPress.org as much as possible
		    // as it would decrease the resources and timing to fetch the data we need

	    	// not relevant to check Asset CleanUp's plugin info in this case
	    	if (in_array($pluginSlug, array('wp-asset-clean-up', 'wp-asset-clean-up-pro'))) {
	    		continue;
		    }

	    	// no readme.txt file in the plugin's root folder? skip it
			if (! is_file(WP_PLUGIN_DIR.'/'.$pluginSlug.'/readme.txt')) {
				continue;
			}

		    $payload = array(
			    'action'  => 'plugin_information',
			    'request' => serialize( (object) array(
				    'slug'   => $pluginSlug,
				    'fields' => array(
					    'tags'          => false,
					    'icons'         => true, // that's what will get fetched
					    'sections'      => false,
					    'description'   => false,
					    'tested'        => false,
					    'requires'      => false,
					    'rating'        => false,
					    'downloaded'    => false,
					    'downloadlink'  => false,
					    'last_updated'  => false,
					    'homepage'      => false,
					    'compatibility' => false,
					    'ratings'       => false,
					    'added'         => false,
					    'donate_link'   => false
				    ),
			    ) ),
		    );

		    $body = @wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array('body' => $payload));

		    if (is_wp_error($body) || (! (isset($body['body']) && is_serialized($body['body'])))) {
		        continue;
		    }

		    $pluginInfo = @unserialize($body['body']);

		    if (! isset($pluginInfo->name, $pluginInfo->icons)) {
		    	continue;
		    }

		    if (empty($pluginInfo->icons)) {
		    	continue;
		    }

		    $pluginIcon = array_shift($pluginInfo->icons);

		    if ($pluginIcon !== '') {
			    $activePluginsIcons[$pluginSlug] = $pluginIcon;
		    }
	    }

	    if (empty($activePluginsIcons)) {
	    	return array();
	    }

	    $expiresInSeconds = 604800; // one week

	    set_transient('wpacu_active_plugins_icons', wp_json_encode($activePluginsIcons), $expiresInSeconds);

	    return $activePluginsIcons;
    }

	/**
	 * @return array
	 */
	public static function getAllActivePluginsIcons()
    {
	    $popularPluginsIcons = array(
	    	'all-in-one-wp-migration-s3-extension' => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/all-in-one-wp-migration-s3-extension.png',
		    'elementor'     => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/elementor.svg',
		    'elementor-pro' => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/elementor-pro.jpg',
		    'oxygen'        => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/oxygen.png',
		    'gravityforms'  => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/gravityforms-blue.svg',
		    'revslider'     => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/revslider.png',
		    'LayerSlider'   => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/LayerSlider.jpg',
		    'wpdatatables'  => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/wpdatatables.jpg',
		    'monarch'       => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/monarch.jpg',
		    'wp-rocket'     => WPACU_PLUGIN_URL . '/assets/icons/premium-plugins/wp-rocket.png'
	    );

	    $allActivePluginsIcons = self::getCachedActiveFreePluginsIcons();

	    if ( ! is_array($allActivePluginsIcons) ) {
		    $allActivePluginsIcons = array();
	    }

	    foreach (self::getActivePlugins() as $activePlugin) {
		    if (strpos($activePlugin, '/') !== false) {
			    list ($pluginSlug) = explode('/', $activePlugin);

			    if (! array_key_exists($pluginSlug, $allActivePluginsIcons) && array_key_exists($pluginSlug, $popularPluginsIcons)) {
				    $allActivePluginsIcons[$pluginSlug] = $popularPluginsIcons[$pluginSlug];
			    }
		    }
	    }

	    return $allActivePluginsIcons;
    }

	/**
	 * @param $themeName
	 *
	 * @return array|string
	 */
	public static function getThemeIcon($themeName)
    {
	    $themesIconsPathToDir = WPACU_PLUGIN_DIR.'/assets/icons/themes/';
	    $themesIconsUrlDir    = WPACU_PLUGIN_URL.'/assets/icons/themes/';

	    if (! is_dir($themesIconsPathToDir)) {
	        return array();
	    }

	    $themeName = strtolower($themeName);

	    $themesIcons = scandir($themesIconsPathToDir);

	    foreach ($themesIcons as $themesIcon) {
	    	if (strpos($themesIcon, $themeName.'.') !== false) {
				return $themesIconsUrlDir . $themesIcon;
		    }
	    }

	    return '';
    }

	/**
	 * @return string
	 */
	public static function getStyleTypeAttribute()
	{
		$typeAttr = '';

		if ( function_exists( 'is_admin' ) && ! is_admin() &&
		     function_exists( 'current_theme_supports' ) && ! current_theme_supports( 'html5', 'style' )
		) {
			$typeAttr = " type='text/css'";
		}

		return wp_kses($typeAttr, array('type' => array()));
	}

	/**
	 * @return string
	 */
	public static function getScriptTypeAttribute()
    {
	    $typeAttr = '';

	    if ( function_exists( 'is_admin' ) && ! is_admin() &&
	         function_exists( 'current_theme_supports' ) && ! current_theme_supports( 'html5', 'script' )
	    ) {
		    $typeAttr = " type='text/javascript'";
	    }

	    return $typeAttr;
    }

	/**
	 * Triggers only in the front-end view (e.g. Homepage URL, /contact/, /about/ etc.)
	 * Except the situations below: no page builders edit mode etc.
	 *
	 * @return bool
	 */
	public static function triggerFrontendOptimization()
	{
		// Not when the CSS/JS is fetched
		if (WPACU_GET_LOADED_ASSETS_ACTION === true) {
			return false;
		}

		// "Elementor" Edit Mode
		if (isset($_GET['elementor-preview']) && $_GET['elementor-preview']) {
			return false;
		}

		// "Divi" Edit Mode
		if (isset($_GET['et_fb']) && $_GET['et_fb']) {
			return false;
		}

		// Not within the Dashboard
		if (is_admin()) {
			return false;
		}

		// Default (triggers in most cases)
		return true;
	}

	/**
	 * @return bool
	 */
	public static function doingCron()
	{
		if (function_exists('wp_doing_cron') && wp_doing_cron()) {
			return true;
		}

		if (defined( 'DOING_CRON') && (true === DOING_CRON)) {
			return true;
		}

		// Default to false
		return false;
	}

	/**
	 * Adapted from: https://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
	 *
	 * @param $size
	 * @param int $precision
	 * @param string $getItIn
	 * @param bool $includeHtmlTags
	 *
	 * @return string
	 */
	public static function formatBytes($size, $precision = 2, $getItIn = '', $includeHtmlTags = true)
	{
		if ((int)$size === 0) {
			return (($includeHtmlTags) ? '<span style="vertical-align: middle;" class="dashicons dashicons-warning"></span> ' : '') .
					__('The file appears to be empty', 'wp-asset-clean-up');
		}

		// In case a string is passed, make it to float
		$size = (float)$size;

		// Just for internal usage (no printing in nice format)
		if ($getItIn === 'bytes') {
			return $size;
		}

		if ($getItIn === 'KB') {
			return round(($size / 1024), $precision);
		}

		if ($getItIn === 'MB') {
			return round((($size / 1024) / 1024), $precision);
		}

		$base = log($size, 1024);

		$suffixes = array('bytes', 'KB', 'MB');

		$floorBase = floor($base);

		if ($floorBase > 2) {
			$floorBase = 2;
		}

		$result = round(
			// 1024 ** ($base - $floorBase) is available only from PHP 5.6+
			pow(1024, ($base - $floorBase)),
			$precision
		);

		$resultForPrint = $result;

		if ($includeHtmlTags && $suffixes[$floorBase] === 'KB' && $floorBase !== 1) {
			$resultForPrint = str_replace('.', '<span style="font-size: 80%; font-weight: 200;">.', $result).'</span>';
		}

		$output = $resultForPrint.' '. $suffixes[$floorBase];

		// If KB, also show the MB equivalent
		if ($floorBase === 1) {
			$output .= ' ('.number_format($result / 1024, 4).' MB)';
		}

		return wp_kses($output, array('span' => array('style' => array(), 'class' => array())));
	}

	/**
	 * @return string
	 */
	public static function getWpRootDirPath()
	{
		if (isset($GLOBALS['wpacu_wp_root_dir_path']) && $GLOBALS['wpacu_wp_root_dir_path']) {
			return $GLOBALS['wpacu_wp_root_dir_path'];
		}

		$possibleWpConfigFile = dirname(WP_CONTENT_DIR).'/wp-config.php';
		$possibleIndexFile = dirname(WP_CONTENT_DIR).'/index.php';

		// This is good for hosting accounts under FlyWheel which have a different way of loading WordPress,
		// and we can't rely on ABSPATH; On most hosting accounts, the condition below would be a match and would work well
		if (is_file($possibleWpConfigFile) && is_file($possibleIndexFile)) {
			$GLOBALS['wpacu_wp_root_dir_path'] = dirname(WP_CONTENT_DIR).'/';
			return $GLOBALS['wpacu_wp_root_dir_path'];
		}

		// Default to the old ABSPATH
		$GLOBALS['wpacu_wp_root_dir_path'] = ABSPATH.'/';
		return $GLOBALS['wpacu_wp_root_dir_path'];
	}

    /**
     * @param $path
     *
     * @return string
     */
    public static function getWpRootDirPathBasedOnPath($path)
    {
        $lastDirWpContent = ltrim(strrchr(WP_CONTENT_DIR, '/'), '/');

        if (strpos($path, $lastDirWpContent) !== false && dirname(WP_CONTENT_DIR) !== ABSPATH) {
            return dirname(WP_CONTENT_DIR) . '/';
        }

        return self::getWpRootDirPath(); // default (most common)
    }

	/**
	 * @param array $targetDirs
	 * @param string $filterExt
	 *
	 * @return array
	 */
	public static function getSizeOfDirectoryRootFiles($targetDirs = array(), $filterExt = '')
	{
		if ( empty($targetDirs) ) {
			return array(); // no relevant target dirs set as a parameter
		}

		$totalSize = 0;

		foreach ( $targetDirs as $targetDir ) {
			if ( ! is_dir($targetDir) ) {
				continue; // skip it as the directory does not exist
			}

			$listOfFiles = scandir( $targetDir );

			if ( ! empty( $listOfFiles ) ) {
				foreach ( $listOfFiles as $fileName ) {
					// Only relevant root files matter
					if ( $fileName === '.' || $fileName === '..' || $fileName === 'index.php' || is_dir( $fileName ) ) {
						continue;
					}

					// If .js is specified, then do not consider any other extension
					if ( $filterExt !== '' && ! strrchr( $fileName, $filterExt ) ) {
						continue;
					}

					$totalSize += filesize( $targetDir . $fileName );
				}
			}
		}

		if ($totalSize > 0) {
			$totalSizeMb = self::formatBytes( $totalSize, 2, 'MB' );

			return array(
				'total_size'    => $totalSize,
				'total_size_mb' => $totalSizeMb
			);
		}

		return array(); // no relevant files
	}

	/**
	 * @param $targetDir
	 */
	public static function rmDir($targetDir)
	{
		if (! is_dir($targetDir)) {
			return;
		}

		$scanDirResult = @scandir($targetDir);

		if (! is_array($scanDirResult)) {
			return;
		}

		$totalFiles = count($scanDirResult) - 2; // exclude . and ..

		if ($totalFiles < 1) { // could be 0 or negative
			@rmdir($targetDir); // @ was appended just in case
		}
	}

	/**
	 * @param $targetVersion
	 *
	 * @return bool
	 */
	public static function isWpVersionAtLeast($targetVersion)
	{
		global $wp_version;
		return version_compare($wp_version, $targetVersion) >= 0;
	}

	/**
	 * @param $list
	 * @param string $for
	 *
	 * @return array
	 */
	public static function filterList($list, $for = 'empty_values')
	{
		if (! empty($list) && $for === 'empty_values') {
			$list = self::arrayUnsetRecursive($list);
		}

		return $list;
	}

	/**
	 * Source: https://stackoverflow.com/questions/7696548/php-how-to-remove-empty-entries-of-an-array-recursively
	 *
	 * @param $array
	 *
	 * @return array
	 */
	public static function arrayUnsetRecursive($array)
	{
		$array = (array)$array; // in case it's object, convert it to array

		foreach ($array as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$array[$key] = self::arrayUnsetRecursive($value);
			}

			// Values such as '0' are not considered empty values
			if (is_string($value) && trim($value) === '0') {
				continue;
			}

			// Clear it if it's empty
			if (empty($array[$key])) {
				unset($array[$key]);
			}
		}

		return $array;
	}

	/**
	 * Single value (no multiple RegExes)
	 *
	 * @param $regexValue
	 *
	 * @return mixed|string
	 */
	public static function purifyRegexValue($regexValue)
	{
		try {
			if ( class_exists( '\CleanRegex\Pattern' )
			     && class_exists( '\SafeRegex\preg' )
			     && method_exists( '\CleanRegex\Pattern', 'delimitered' )
			     && method_exists( '\SafeRegex\preg', 'match' ) ) {
					$cleanRegexPattern = new \CleanRegex\Pattern( $regexValue );
					$delimiteredValue  = $cleanRegexPattern->delimitered(); // auto-correct it if there's no delimiter

					if ( $delimiteredValue ) {
						// Tip: https://stackoverflow.com/questions/4440626/how-can-i-validate-regex
						// Validate it and if it doesn't match, do not add it to the list
						@preg_match( $delimiteredValue, null );

						if ( preg_last_error() !== PREG_NO_ERROR ) {
							return $regexValue;
						}

						}
				$regexValue = trim($regexValue);
			}
		} catch( \Exception $e) {} // if T-Regx library didn't load as it should, the textarea value will be kept as it is

		return $regexValue;
	}

	/**
	 * @param $name
	 * @param $action
	 *
	 * @return mixed|string
	 */
	public static function scriptExecTimer($name, $action = 'start')
	{
		if (! isset($_GET['wpacu_debug'])) {
			return ''; // only trigger it in debugging mode
		}

		$wpacuStartTimeName = 'wpacu_' . $name . '_start_time';
		$wpacuExecTimeName  = 'wpacu_' . $name . '_exec_time';

		if ($action === 'start') {
			$startTime = microtime(true) * 1000;

			ObjectCache::wpacu_cache_set($wpacuStartTimeName, $startTime, 'wpacu_exec_time');
		}

		if ($action === 'end' && ($startTime = ObjectCache::wpacu_cache_get($wpacuStartTimeName, 'wpacu_exec_time'))) {
			// End clock time
			$endTime = microtime(true) * 1000;
			$scriptExecTime = ( $endTime > $startTime ) ? ( $endTime - $startTime ) : 0;

			// Calculate script execution time
			// Is there an existing exec time (e.g. from a function called several times)?
			// Append it to the total execution time
			$scriptExecTimeExisting  = ObjectCache::wpacu_cache_get( $wpacuExecTimeName, 'wpacu_exec_time' ) ?: 0;
			$scriptExecTimeExisting += $scriptExecTime;
			ObjectCache::wpacu_cache_set($wpacuExecTimeName, $scriptExecTimeExisting, 'wpacu_exec_time');

			return $scriptExecTime;
		}

		return '';
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	public static function sanitizeValueForHtmlAttr($value)
	{
		// Keep a standard that is used for specific HTML attributes such as "id" and "for"
		$value = str_replace(array('-', '/', '.'), array('_', '_', '_'), $value);

		return esc_attr(sanitize_title_for_query($value));
	}
}
