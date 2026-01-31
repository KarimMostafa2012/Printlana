<?php
if (! isset($activePlugins, $onlyLoadWpacuPlugins, $tagName, $originalActivePluginsList)) {
	exit;
}

// Are there specific plugin AJAX (admin/ajax-admin.php) calls? Only trigger Asset CleanUp (Pro) plugin as loading other plugins is useless (save resources)
$wpacuIsAjaxRequest = (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
if ( isset( $_REQUEST['action'], $_SERVER['REQUEST_URI'] ) && $wpacuIsAjaxRequest
     && strpos( $_REQUEST['action'], 'wpassetcleanup_' ) !== false
     && strpos( $_SERVER['REQUEST_URI'], '/admin-ajax.php' ) !== false
	 && is_admin() // extra check to make sure /admin/admin-ajax.php is accessed
) {
	if ( strpos( $_REQUEST['action'], '_clear_cache' ) !== false ) {
		// Leave other plugins loaded when the caching is cleared via AJAX calls
		// e.g. If "Cache Enabler" is enabled, its caching is cleared after Asset CleanUp cache is cleared
		return;
	}

	$onlyLoadWpacuPlugins = true;

	$originalActivePluginsListFiltered = $originalActivePluginsList;

	$possibleWpacuPlugins = array(
		'wp-asset-clean-up/wpacu.php',
		'wp-asset-clean-up-pro/wpacu.php',
		'wpacu-keep-it-on/index.php'
	);

	if ( $tagName === 'site_option_active_sitewide_plugins' ) {
		foreach ( $activePlugins as $activePlugin ) {
			if ( ! in_array($activePlugins, $possibleWpacuPlugins) ) {
				unset( $originalActivePluginsListFiltered[$activePlugin] );
			}
		}

		$onlyWpacuPlugins = $originalActivePluginsListFiltered;
	} else {
		foreach ( $activePlugins as $activePlugin ) {
			if ( ! in_array($activePlugins, $possibleWpacuPlugins) ) {
				$pluginKeyToUnset = array_search($activePlugin, $activePlugins);

                if ($pluginKeyToUnset !== false) {
                    unset($activePlugins[$pluginKeyToUnset]);
                }
			}
		}

		$onlyWpacuPlugins = $activePlugins;
	}
}
