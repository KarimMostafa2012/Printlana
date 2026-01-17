<?php
if (! isset($originalActivePluginsList, $activePlugins, $activePluginsToUnload, $tagName, $wpacuAlreadyFilteredName)) {
	exit;
}

// Any /?wpacu_filter_plugins=[...] /?wpacu_only_load_plugins=[...] requests
// Any plugins from /?wpacu_debug marked for unloading (the system will only mark those for debugging purposes)
$wpacuOnlyLoadPluginsQueryStringUsed = false; // default
$wpacuIsUnloadPluginsViaDebugForm = (! empty($_POST['wpacu_filter_plugins']) && is_array($_POST['wpacu_filter_plugins'])) &&
                                    (isset($_POST['wpacu_debug']) && $_POST['wpacu_debug'] === 'on');


if ( isset( $_GET['wpacu_filter_plugins'] ) || isset( $_GET['wpacu_only_load_plugins'] ) || $wpacuIsUnloadPluginsViaDebugForm ) {
	$wpacuAllowPluginFilterViaDebugForGuests = defined( 'WPACU_FILTER_PLUGINS_VIA_QUERY_STRING_FOR_GUESTS' ) && WPACU_FILTER_PLUGINS_VIA_QUERY_STRING_FOR_GUESTS;

	if ( $wpacuAllowPluginFilterViaDebugForGuests ) {
		// Non-logged visitors can also do the query string filtering
		require WPACU_MU_FILTER_PLUGIN_DIR . '/_common/_filter-via-debug.php';
	} else {
		// Only the admin can do the query string filtering (default)
		if ( ! defined( 'WPACU_PLUGGABLE_LOADED' ) ) {
			require_once WPACU_MU_FILTER_PLUGIN_DIR . '/pluggable-custom.php';
			define( 'WPACU_PLUGGABLE_LOADED', true );
		}

		if ( function_exists( 'wpacu_current_user_can' ) && wpacu_current_user_can( 'administrator' ) ) {
			require WPACU_MU_FILTER_PLUGIN_DIR . '/_common/_filter-via-debug.php';
		}
	}
}

// Neither of the following was used for debugging purposes?
// 1) /?wpacu_only_load_plugins=
// 2) The plugin unload form from /?wpacu_debug
// As a result, go through the list of unloading rules from "Plugins Manager" -> "IN FRONTEND VIEW (your visitors)"
if ( ! $wpacuOnlyLoadPluginsQueryStringUsed && ! $wpacuIsUnloadPluginsViaDebugForm ) {
	// Is "Test Mode" disabled OR enabled but the admin is viewing the page? Continue
	// Fetch the existing rules (unload, load exceptions, etc.)
	require __DIR__ . '/_filter-from-rules-front.php';
}

