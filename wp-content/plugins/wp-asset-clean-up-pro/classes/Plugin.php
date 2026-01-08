<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUp\OptimiseAssets\OptimizeCss;
use WpAssetCleanUp\OptimiseAssets\OptimizeJs;

/**
 * Class Plugin
 * @package WpAssetCleanUp
 */
class Plugin
{
	/**
	 *
	 */
	const RATE_URL = 'https://wordpress.org/support/plugin/wp-asset-clean-up/reviews/#new-post';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	    register_activation_hook(WPACU_PLUGIN_FILE, array($this, 'whenActivated'));
		register_deactivation_hook(WPACU_PLUGIN_FILE, array($this, 'whenDeactivated'));
	}

	/**
	 *
	 */
	public function init()
	{
		// After fist time activation or in specific situations within the Dashboard
		add_action('admin_init', array($this, 'adminInit'));

		// Show default action links: "Getting Started", "Settings"
		add_filter('plugin_action_links_'.WPACU_PLUGIN_BASE, array($this, 'addActionLinksInPluginsPage'));

		}

	/**
	 * Actions taken when the plugin is activated
	 */
	public function whenActivated()
	{
	    if (WPACU_WRONG_PHP_VERSION === 'true') {
		    $recordMsg = __( '"Asset CleanUp Pro" plugin has not been activated because the PHP version used on this server is below 5.6.',
			    'wp-asset-clean-up' );
		    deactivate_plugins( WPACU_PLUGIN_BASE );
		    error_log( $recordMsg );
		    wp_die($recordMsg);
	    }

		// Prepare for the redirection to the WPACU_ADMIN_PAGE_ID_START plugin page
        // If there is no record that the plugin was already activated at least once
		if ( ! get_option(WPACU_PLUGIN_ID.'_first_usage') ) {
			set_transient(WPACU_PLUGIN_ID . '_redirect_after_activation', 1, 15);

			// Make a record when Asset CleanUp (Pro) is used for the first time
			// In case this is the first time the plugin is activated
			self::triggerFirstUsage();
		}

		/**
         * Note: Could be /wp-content/uploads/ if constant WPACU_CACHE_DIR was used
         *
		 * /wp-content/cache/asset-cleanup/
		 * /wp-content/cache/asset-cleanup/index.php
		 * /wp-content/cache/asset-cleanup/.htaccess
		 *
		 * /wp-content/cache/asset-cleanup/css/
         * /wp-content/cache/asset-cleanup/css/item/
		 * /wp-content/cache/asset-cleanup/css/index.php
         *
         * /wp-content/cache/asset-cleanup/js/
         * /wp-content/cache/asset-cleanup/js/item/
         * /wp-content/cache/asset-cleanup/js/index.php
         *
		 */
		self::createCacheFoldersFiles(array('css','js'));

		// Do not apply plugin's settings/rules on WooCommerce/EDD Checkout/Cart pages
		if (function_exists('wc_get_page_id')) {
			if ($wooCheckOutPageId = wc_get_page_id('checkout')) {
				Misc::doNotApplyOptimizationOnPage($wooCheckOutPageId);
			}

			if ($wooCartPageId = wc_get_page_id('cart')) {
				Misc::doNotApplyOptimizationOnPage($wooCartPageId);
			}
		}

		if (function_exists('edd_get_option') && $eddPurchasePage = edd_get_option('purchase_page', '')) {
			Misc::doNotApplyOptimizationOnPage($eddPurchasePage);
		}
	}

	/**
	 * Actions taken when the plugin is deactivated
	 */
	public function whenDeactivated()
    {
    	// Clear traces of the plugin which are re-generated once the plugin is enabled
	    // This is good when the admin wants to completely uninstall the plugin
        self::clearAllTransients();
	    self::removeCacheDirWithoutAssets();

	    // Clear other plugin's cache (if they are active)
        OptimizeCommon::clearOtherPluginsCache();
    }

	/**
	 * Removes all plugin's transients, this is usually done when the plugin is deactivated
	 */
	public static function clearAllTransients()
    {
	    global $wpdb;

	    // Remove all transients
	    $transientLikes = array(
		    '_transient_wpacu_',
		    '_transient_'.WPACU_PLUGIN_ID.'_'
	    );

	    $transientLikesSql = '';

	    foreach ($transientLikes as $transientLike) {
		    $transientLikesSql .= " option_name LIKE '".$transientLike."%' OR ";
	    }

	    $transientLikesSql = rtrim($transientLikesSql, ' OR ');

	    $sqlQuery = <<<SQL
SELECT option_name FROM `{$wpdb->prefix}options` WHERE {$transientLikesSql}
SQL;
	    $transientsToClear = $wpdb->get_col($sqlQuery);

	    if (! empty($transientsToClear)) {
		    foreach ( $transientsToClear as $transientToClear ) {
			    $transientNameToClear = str_replace( '_transient_', '', $transientToClear );
			    delete_transient( $transientNameToClear );
		    }
	    }
    }

	/**
	 * This is usually triggered when the plugin is deactivated
	 * If the caching directory doesn't have any CSS/JS left, it will clear itself
	 * The admin might want to clear all traces of the plugin
	 * If the plugin is re-activated, the caching directory will be re-created automatically
	 */
	public static function removeCacheDirWithoutAssets()
    {
	    $pathToCacheDir    = WP_CONTENT_DIR . OptimizeCommon::getRelPathPluginCacheDir();

	    if (! is_dir($pathToCacheDir)) {
	        return;
        }

	    $pathToCacheDirCss = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir();
	    $pathToCacheDirJs  = WP_CONTENT_DIR . OptimizeJs::getRelPathJsCacheDir();

	    $allCssFiles = glob( $pathToCacheDirCss . '**/*.css' );
	    $allJsFiles  = glob( $pathToCacheDirJs . '**/*.js' );

	    // Only valid when there's no CSS or JS (not one single file) there
	    if ( count( $allCssFiles ) === 0 && count( $allJsFiles ) === 0 ) {
		    $dirItems = new \RecursiveDirectoryIterator( $pathToCacheDir );

		    $allDirs = array($pathToCacheDir);

		    // First, remove the files
		    foreach ( new \RecursiveIteratorIterator( $dirItems, \RecursiveIteratorIterator::SELF_FIRST,
				    \RecursiveIteratorIterator::CATCH_GET_CHILD ) as $item) {
		        if (is_dir($item)) {
		            $allDirs[] = $item;
                } else {
		            @unlink($item);
                }
		    }

            if ( ! empty($allDirs) ) {
	            usort( $allDirs, static function( $a, $b ) {
		            return strlen( $b ) - strlen( $a );
	            } );

	            // Then, remove the empty dirs in descending order (up to the root)
	            foreach ($allDirs as $dir) {
		            Misc::rmDir($dir);
	            }
            }
	    }
    }

	/**
	 * @param $assetTypes
	 */
	public static function createCacheFoldersFiles($assetTypes)
	{
	    foreach ($assetTypes as $assetType) {
	        if ($assetType === 'css') {
		        $cacheDir = WP_CONTENT_DIR . OptimiseAssets\OptimizeCss::getRelPathCssCacheDir();
	        } elseif ($assetType === 'js') {
	            $cacheDir = WP_CONTENT_DIR . OptimiseAssets\OptimizeJs::getRelPathJsCacheDir();
            } else {
	            return;
            }

		    $emptyPhpFileContents = <<<TEXT
<?php
// Silence is golden.
TEXT;

		    $htAccessContents = <<<HTACCESS
<IfModule mod_autoindex.c>
Options -Indexes
</IfModule>
HTACCESS;

		    if ( ! is_dir( $cacheDir ) ) {
			    @mkdir( $cacheDir, FS_CHMOD_DIR, true );
		    }

		    if ( ! is_file( $cacheDir . 'index.php' ) ) {
			    // /wp-content/cache/asset-cleanup/cache/(css|js)/index.php
			    FileSystem::filePutContents( $cacheDir . 'index.php', $emptyPhpFileContents );
		    }

			if ( ! is_dir( $cacheDir . OptimizeCommon::$optimizedSingleFilesDir ) ) {
				// /wp-content/cache/asset-cleanup/cache/(css|js)/item/
				@mkdir( $cacheDir . OptimizeCommon::$optimizedSingleFilesDir, FS_CHMOD_DIR );
			}

			// For large inline STYLE & SCRIPT tags
			if ( ! is_dir( $cacheDir . OptimizeCommon::$optimizedSingleFilesDir.'/inline' ) ) {
				// /wp-content/cache/asset-cleanup/cache/(css|js)/item/inline/
			    @mkdir( $cacheDir . OptimizeCommon::$optimizedSingleFilesDir.'/inline', FS_CHMOD_DIR );
		    }

		    if ( ! is_file( $cacheDir . OptimizeCommon::$optimizedSingleFilesDir.'/inline/index.php' ) ) {
			    // /wp-content/cache/asset-cleanup/cache/(css|js)/item/inline/index.php
			    FileSystem::filePutContents( $cacheDir . OptimizeCommon::$optimizedSingleFilesDir.'/inline/index.php', $emptyPhpFileContents );
		    }

		    $htAccessFilePath = dirname( $cacheDir ) . '/.htaccess';

		    if ( ! is_file( $htAccessFilePath ) ) {
			    // /wp-content/cache/asset-cleanup/.htaccess
			    FileSystem::filePutContents( $htAccessFilePath, $htAccessContents );
		    }

		    if ( ! is_file( dirname( $cacheDir ) . '/index.php' ) ) {
			    // /wp-content/cache/asset-cleanup/index.php
			    FileSystem::filePutContents( dirname( $cacheDir ) . '/index.php', $emptyPhpFileContents );
		    }
	    }

	    // Storage directory for JSON/TEXT files (information purpose)
		$storageDir = WP_CONTENT_DIR . OptimiseAssets\OptimizeCommon::getRelPathPluginCacheDir() . '_storage/';

		if ( ! is_dir($storageDir . OptimizeCommon::$optimizedSingleFilesDir) ) {
			@mkdir( $storageDir . OptimizeCommon::$optimizedSingleFilesDir, FS_CHMOD_DIR, true );
		}

        $siteStorageCache = $storageDir.'/'.str_replace(array('https://', 'http://', '//'), '', site_url());

		if ( ! is_dir($storageDir) ) {
			@mkdir( $siteStorageCache, FS_CHMOD_DIR, true );
		}
	}

	/**
	 *
	 */
	public function adminInit()
	{
		if ( // If this condition does not match, do not make the extra DB calls to "options" table to save resources
             isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/plugins.php') !== false &&
             get_transient(WPACU_PLUGIN_ID . '_redirect_after_activation') ) {
            // Remove it as only one redirect is needed (first time the plugin is activated)
            delete_transient(WPACU_PLUGIN_ID . '_redirect_after_activation');

            // Do the 'first activation time' redirection
            wp_redirect(admin_url('admin.php?page=' . WPACU_ADMIN_PAGE_ID_START));
            exit();
		}

		}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public function addActionLinksInPluginsPage($links)
	{
		$links['getting_started'] = '<a href="admin.php?page=' . WPACU_PLUGIN_ID . '_getting_started">'.__('Getting Started', 'wp-asset-clean-up').'</a>';
		$links['settings']        = '<a href="admin.php?page=' . WPACU_PLUGIN_ID . '_settings">'.__('Settings', 'wp-asset-clean-up').'</a>';

		return $links;
	}

	/**
	 * Make a record when Asset CleanUp (Pro) is used for the first time (if it's not there already)
	 */
	public static function triggerFirstUsage()
	{
        Misc::addUpdateOption(WPACU_PLUGIN_ID . '_first_usage', time());
	}

	/**
     * This works like /?wpacu_no_load with a fundamental difference:
     * It needs to be triggered through a very early 'init' / 'setup_theme' action hook after all plugins are loaded, thus it can't be used in /early-triggers.php
     * e.g. in situations when the page is an AMP one, prevent any changes to the HTML source by Asset CleanUp (Pro)
     *
	 * @param string $tagActionName
     * @param string $htmlSource
	 *
	 * @return bool
	 */
	public static function preventAnyFrontendOptimization($tagActionName = '', $htmlSource = '')
	{
        if (isset($GLOBALS['wpacu_prevent_any_frontend_optimization'])) {
            return $GLOBALS['wpacu_prevent_any_frontend_optimization'];
        }

		// Only relevant if all the plugins are already loaded
		// and in the front-end view
		if (! defined('WPACU_ALL_ACTIVE_PLUGINS_LOADED') || is_admin()) {
			$GLOBALS['wpacu_prevent_any_frontend_optimization'] = false;
			return false;
		}

		// Perhaps the editor from "Pro" (theme.co) is on
		if (apply_filters('wpacu_prevent_any_frontend_optimization', false)) {
			$GLOBALS['wpacu_prevent_any_frontend_optimization'] = true;
			return true;
		}

		// e.g. /amp/ - /amp? - /amp/? - /?amp or ending in /amp
		$isAmpInRequestUri = ( (isset($_SERVER['REQUEST_URI']) && (preg_match('/(\/amp$|\/amp\?)|(\/amp\/|\/amp\/\?)/', $_SERVER['REQUEST_URI'])))
                               || isset($_GET['amp']) );

		// Is it an AMP endpoint?
		if ( ($isAmpInRequestUri && Misc::isPluginActive('accelerated-mobile-pages/accelerated-mobile-pages.php')) // "AMP for WP – Accelerated Mobile Pages"
		     || ($isAmpInRequestUri && Misc::isPluginActive('amp/amp.php')) // "AMP – WordPress plugin"
		     || (function_exists('is_wp_amp') && Misc::isPluginActive('wp-amp/wp-amp.php') && is_wp_amp()) // "WP AMP — Accelerated Mobile Pages for WordPress and WooCommerce" (Premium plugin)
		) {
			$GLOBALS['wpacu_prevent_any_frontend_optimization'] = true;
			return true; // do not print anything on an AMP page
		}

		// Some pages are AMP but their URI does not end in /amp
		if ( ! defined('WPACU_DO_EXTRA_CHECKS_FOR_AMP') &&
             ( Misc::isPluginActive('accelerated-mobile-pages/accelerated-mobile-pages.php')
		        || Misc::isPluginActive('amp/amp.php')
		        || Misc::isPluginActive('wp-amp/wp-amp.php') )
		) {
			define('WPACU_DO_EXTRA_CHECKS_FOR_AMP', true);
		}

		if ( isset($_GET['wpacu_clean_load']) ) {
			$GLOBALS['wpacu_prevent_any_frontend_optimization'] = true;
			return true;
		}

		// $tagActionName needs to be different from 'parse_query' because is_singular() would trigger too soon and cause notice errors
		// Has the following page option set: "Do not apply any front-end optimization on this page (this includes any changes related to CSS/JS files)"
		if ($tagActionName !== 'parse_query' && MetaBoxes::hasNoFrontendOptimizationPageOption()) {
			$GLOBALS['wpacu_prevent_any_frontend_optimization'] = true;
			return true;
		}

		// e.g. it could be JS content loaded dynamically such as /?wpml-app=ate-widget
        // in this case, any Asset CleanUp alteration of the content should be avoided
        // often, the code below is not reached as extra measures are taken before if well known plugins are used
        if ($htmlSource !== '') {
	        $startsWithPassed = $endsWithPassed = false;

            // More common delimiters can be added with time
            // This is just an extra measure to prevent possible empty pages due to lots of memory used in case a possible JavaScript output is too large
	        $startsWithAnyFromTheList = array(
		        '/*!',
		        '(function()'
	        );

	        $endsWithAnyFromTheList = array(
		        ');',
                ')'
	        );

            // Possible JS content
            foreach ($startsWithAnyFromTheList as $startsWithString) {
                if (substr(trim($htmlSource), 0, strlen($startsWithString)) === $startsWithString) {
	                $startsWithPassed = true;
                    break;
                }
            }

            if ($startsWithPassed) {
	            foreach ($endsWithAnyFromTheList as $endsWithString) {
		            if (substr(trim($htmlSource), -strlen($endsWithString)) === $endsWithString) {
			            $endsWithPassed = true;
			            break;
		            }
	            }
            }

            if ($startsWithPassed && $endsWithPassed) {
	            $GLOBALS['wpacu_prevent_any_frontend_optimization'] = true;
                return true;
            }
        }

		$GLOBALS['wpacu_prevent_any_frontend_optimization'] = false;
		return false;
	}
}
