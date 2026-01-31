<?php
// Exit if accessed directly
if (! defined('WPACU_PLUGIN_CLASSES_PATH')) {
    exit;
}

// Autoload Classes
function includeWpAssetCleanUpClassesAutoload($class)
{
	if ( ! (
		( function_exists( 'str_starts_with' ) && str_starts_with( $class, 'WpAssetCleanUp' ) ) ||
		( strncmp($class, 'WpAssetCleanUp', 14) === 0 )
	) ) {
		return;
	}

	// Reference Namespace
    if (strpos($class, '\\') === 14) {
	    $namespace = 'WpAssetCleanUp';
    }

    // [wpacu_pro]
    else {
        $namespace = 'WpAssetCleanUpPro';
    }
    // [/wpacu_pro]

	$classFilter = strtr($class, array(
		$namespace . '\\' => '',
		'\\'              => '/' // Can be directories such as "OptimiseAssets"
	));

	// [wpacu_pro]
	if ($namespace === 'WpAssetCleanUpPro') {
		include_once WPACU_PRO_CLASSES_PATH . $classFilter . '.php';
	}
	// [/wpacu_pro]

	if ($namespace === 'WpAssetCleanUp') {
		include_once WPACU_PLUGIN_CLASSES_PATH . $classFilter . '.php';
	}
}

spl_autoload_register('includeWpAssetCleanUpClassesAutoload');

\WpAssetCleanUp\ObjectCache::wpacu_cache_init();

if (isset($GLOBALS['wpacu_object_cache'])) {
	$wpacu_object_cache = $GLOBALS['wpacu_object_cache']; // just in case
}

// Main Class (common code for both the front-end and /wp-admin/ views)
\WpAssetCleanUp\Main::instance();

if ( is_admin() ) {
	\WpAssetCleanUp\MainAdmin::instance();
} else {
	\WpAssetCleanUp\MainFront::instance();
}

// Menu
new \WpAssetCleanUp\Menu;

$wpacuSettingsClass = new \WpAssetCleanUp\Settings();

if (is_admin()) {
	$wpacuSettingsClass->adminInit();
}

// The following are only relevant when you're logged in
add_action('init', function() {
	if (\WpAssetCleanUp\Menu::userCanManageAssets()) {
		\WpAssetCleanUp\AssetsManager::instance();

        $withinAdminAreaOrFrontendWithCssJsManagerOrClearCache = is_admin() ||
            (\WpAssetCleanUp\Menu::userCanManageAssets() && (\WpAssetCleanUp\AssetsManager::instance()->frontendShow() || \WpAssetCleanUp\OwnAssets::isPluginClearCacheLinkAccessible()));

		if ( $withinAdminAreaOrFrontendWithCssJsManagerOrClearCache ) {
            $wpacuOwnAssets = new \WpAssetCleanUp\OwnAssets;
            $wpacuOwnAssets->init();

			// Add / Update / Remove Settings
			$wpacuUpdate = new \WpAssetCleanUp\Update;
			$wpacuUpdate->init();

			// Initialize information (irrelevant for the guest visitor)
			new \WpAssetCleanUp\Info();
		}
	}
});

if ( ! is_admin() ) {
	add_action( 'plugins_loaded', function() use ( $wpacuSettingsClass ) {
		$wpacuSettings = $wpacuSettingsClass->getAll();

		// If "Manage in the front-end" is enabled & the admin is logged-in, do not trigger any Autoptimize caching at all
		if ( $wpacuSettings['frontend_show'] && \WpAssetCleanUp\Menu::userCanManageAssets() && ! defined( 'AUTOPTIMIZE_NOBUFFER_OPTIMIZE' ) ) {
			define( 'AUTOPTIMIZE_NOBUFFER_OPTIMIZE', true );
		}
	}, - PHP_INT_MAX );
}

// Admin Bar (Top Area of the website when user is logged in)
add_action('init', function() {
	if ( \WpAssetCleanUp\Menu::userCanManageAssets() &&
	     ( ! \WpAssetCleanUp\Main::instance()->settings['hide_from_admin_bar'] ) &&
		 is_admin_bar_showing() ) {
		new \WpAssetCleanUp\AdminBar();
	}
});

// Any debug?
if (isset($_GET['wpacu_debug']) ||
    isset($_GET['wpacu_get_cache_dir_size']) ||
	isset($_GET['wpacu_get_already_minified']) ||
    isset($_GET['wpacu_remove_already_minified']) ||
    isset($_GET['wpacu_limit_already_minified'])
) {
	new \WpAssetCleanUp\Debug();

	// [wpacu_pro]
	if (is_admin()) { // Dashboard view
		new \WpAssetCleanUpPro\DebugPro();
	}
	// [/wpacu_pro]
}

// Maintenance
new \WpAssetCleanUp\Maintenance();

// Common functions for both CSS & JS combinations
// Clear CSS/JS caching functionality
$wpacuOptimizeCommon = new \WpAssetCleanUp\OptimiseAssets\OptimizeCommon();
$wpacuOptimizeCommon->init();

if (is_admin()) {
	/*
	 * Trigger only within the Dashboard view (e.g., within /wp-admin/)
	 */
	$wpacuPlugin = new \WpAssetCleanUp\Plugin;
	$wpacuPlugin->init();

	new \WpAssetCleanUp\PluginReview();

	$wpacuPluginTracking = new \WpAssetCleanUp\PluginTracking();
	$wpacuPluginTracking->init();

	$wpacuTools = new \WpAssetCleanUp\Tools();
	$wpacuTools->init();

	new \WpAssetCleanUp\AjaxSearchAutocomplete();
} elseif (\WpAssetCleanUp\Misc::triggerFrontendOptimization()) {
	/*
	 * Trigger the CSS & JS combination only in the front-end view in certain conditions (not within the Dashboard)
	 */
	// Combine/Minify CSS Files Setup
	$wpacuOptimizeCss = new \WpAssetCleanUp\OptimiseAssets\OptimizeCss();
	$wpacuOptimizeCss->init();

	// Combine/Minify JS Files Setup
	$wpacuOptimizeJs = new \WpAssetCleanUp\OptimiseAssets\OptimizeJs();
	$wpacuOptimizeJs->init();

	/*
	 * Trigger only in the front-end view (e.g. Homepage URL, /contact/, /about/ etc.)
	 */
	$wpacuCleanUp = new \WpAssetCleanUp\CleanUp();
	$wpacuCleanUp->init();

	add_action('init', function() {
		$loadFontsLocalClass = ! ( (defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') && WPACU_ALLOW_ONLY_UNLOAD_RULES)
			|| ( ! is_admin() && \WpAssetCleanUp\Plugin::preventAnyFrontendOptimization() )
			|| \WpAssetCleanUp\Main::isTestModeActiveAndVisitorNonAdmin()
			|| ! trim(\WpAssetCleanUp\Main::instance()->settings['local_fonts_preload_files']) );

		if ( $loadFontsLocalClass ) {
			$wpacuFontsLocal = new \WpAssetCleanUp\OptimiseAssets\FontsLocal();
			$wpacuFontsLocal->init();
		}
	}, 11);

	$wpacuFontsGoogle = new \WpAssetCleanUp\OptimiseAssets\FontsGoogle();
	$wpacuFontsGoogle->init();
}

\WpAssetCleanUp\Preloads::instance()->init();
