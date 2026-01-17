<?php
// Exit if accessed directly
if (! defined('WPACU_PRO_CLASSES_PATH')) {
    exit;
}

add_action('init', function() {
    $triggerIf = \WpAssetCleanUp\Menu::userCanManageAssets() && (is_admin() || \WpAssetCleanUp\AssetsManager::instance()->frontendShow());

    // Load the classes and its actions only when the user is an admin
    // and the admin is within the /wp-admin/ area or when is visiting the main website and CSS/JS manager is loaded at the bottom of the page
	if ($triggerIf) {
		new \WpAssetCleanUpPro\OutputPro();

        $updatePro = new \WpAssetCleanUpPro\UpdatePro();
        $updatePro->init();
    }
});

$exceptionsPro = new \WpAssetCleanUpPro\LoadExceptionsPro();
$exceptionsPro->init();

$wpacuMainPro = new \WpAssetCleanUpPro\MainPro();
$wpacuMainPro->init();

if (is_admin()) {
    $wpacuMainAdminPro = new \WpAssetCleanUpPro\MainAdminPro();
    $wpacuMainAdminPro->init();

    new \WpAssetCleanUpPro\PluginsManagerPro();

	$wpacuLicensePro = new \WpAssetCleanUpPro\LicensePro();
	$wpacuLicensePro->init();

	$wpacuPluginPro = new \WpAssetCleanUpPro\PluginPro();
	$wpacuPluginPro->init();
}

if (! is_admin() && ! (defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') && WPACU_ALLOW_ONLY_UNLOAD_RULES)) {
	$optimizeCssPro = new \WpAssetCleanUpPro\OptimiseAssets\OptimizeCssPro();
	$optimizeCssPro->init();

	$wpacuPreloadsPro = new \WpAssetCleanUpPro\PreloadsPro();
	$wpacuPreloadsPro->init();
}

// Triggers in both the front-end and the Dashboard
new \WpAssetCleanUpPro\OptimiseAssets\CriticalCssPro();

// Update the premium plugin within the Dashboard similar to other plugins from WordPress.org
include_once WPACU_PRO_DIR . '/wpacu-pro-updater.php';
