<?php
if (! isset($activePlugins, $wpacuOnlyLoadPluginsQueryStringUsed, $wpacuIsUnloadPluginsViaDebugForm)) {
	exit;
}

$anyMatchesFromQueryString = false;
$filterRequestedPluginStrings = array();

// LOAD (only) the requested plugins (all the unmatched ones will not be loaded)
if (isset($_GET['wpacu_only_load_plugins']) && $_GET['wpacu_only_load_plugins']) {
	$filterRequestedPluginsRequests = trim( $_GET['wpacu_only_load_plugins'], ' ,' );
	// Disable plugins on page request for testing purposes
	if ( strpos( $filterRequestedPluginsRequests, ',' ) !== false ) {
		// With comma? Could be something like /?wpacu_only_load_plugins=cache,woocommerce that will load only plugins containing "cache" and "woocommerce"
		foreach ( explode( ',', $filterRequestedPluginsRequests ) as $filterRequestedPluginString ) {
			if ( trim( $filterRequestedPluginString ) ) {
				$filterRequestedPluginStrings[] = $filterRequestedPluginString;
			}
		}
	} else {
		// Without any comma? Could be something like /?wpacu_only_load_plugins=cache that will load all plugins containing "cache"
		$filterRequestedPluginStrings[] = $filterRequestedPluginsRequests;
	}

	foreach ($activePlugins as $activePlugin) {
		// Does the plugin name/path match anything from the query string?
		// Either one if no comma was used, or multiple of them
		foreach ($filterRequestedPluginStrings as $filterRequestedPluginString) {
			if ( strpos( $activePlugin, $filterRequestedPluginString ) === false ) {
				// If it does not match, unload it
				// Only keep the ones that match loaded

				// Always keep Asset CleanUp
				if (strpos($activePlugin, 'wp-asset-clean-up') !== false) {
					continue;
				}

				$wpacuOnlyLoadPluginsQueryStringUsed = $anyMatchesFromQueryString = true;

				$activePluginsToUnload[] = $activePlugin;
			}
		}
	}

	}

// The form from /?wpacu_debug has been submitted and only the chosen plugins (the marked ones) from the list will be unloaded for debugging purposes
elseif ($wpacuIsUnloadPluginsViaDebugForm) {
	foreach ($_POST['wpacu_filter_plugins'] as $wpacuPluginPath) {
		if (in_array($wpacuPluginPath, $activePlugins)) {
			$activePluginsToUnload[] = $wpacuPluginPath;
		}
	}
}

// UNLOAD the ones from the request (all the unmatched ones will remain loaded)
elseif (isset($_GET['wpacu_filter_plugins']) && $_GET['wpacu_filter_plugins']) {
	$filterRequestedPluginsRequests = trim( $_GET['wpacu_filter_plugins'], ' ,' );

	// Disable plugins on page request for testing purposes
	if ( strpos( $filterRequestedPluginsRequests, ',' ) !== false ) {
		// With comma? Could be something like /?wpacu_filter_plugins=cache,woocommerce that will deactivate all plugins containing "cache" and "woocommerce"
		foreach ( explode( ',', $filterRequestedPluginsRequests ) as $filterRequestedPluginString ) {
			if ( trim( $filterRequestedPluginString ) ) {
				$filterRequestedPluginStrings[] = $filterRequestedPluginString;
			}
		}
	} else {
		// Without any comma? Could be something like /?wpacu_filter_plugins=cache that will deactivate all plugins containing "cache"
		$filterRequestedPluginStrings[] = $filterRequestedPluginsRequests;
	}

	foreach ($activePlugins as $activePlugin) {
		// Does the plugin name/path match anything from the query string?
		// Either one if no comma was used, or multiple of them
		foreach ($filterRequestedPluginStrings as $filterRequestedPluginString) {
			if ( strpos( $activePlugin, $filterRequestedPluginString ) !== false ) {
				$activePluginsToUnload[] = $activePlugin;
				$anyMatchesFromQueryString = true;
				continue 2;
			}
		}
	}
}

if ( ! empty($activePluginsToUnload) && $anyMatchesFromQueryString ) {
	@ini_set('display_errors', 'off');
	@error_reporting(0);

	if ( ! defined('WP_DEBUG') ) {
		define( 'WP_DEBUG', false );
	}

	if ( ! defined('WP_DEBUG_DISPLAY') ) {
		define( 'WP_DEBUG_DISPLAY', false );
	}
}
