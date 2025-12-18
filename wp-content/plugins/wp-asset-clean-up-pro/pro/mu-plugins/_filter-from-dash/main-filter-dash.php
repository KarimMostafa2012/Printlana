<?php
if (! isset($activePlugins, $activePluginsToUnload, $wpacuAlreadyFilteredName)) {
	exit;
}

// /?wpacu_only_load_plugins=
$wpacuOnlyLoadPluginsQueryStringUsed = false;

if ( isset( $_GET['wpacu_filter_plugins'] ) || isset( $_GET['wpacu_only_load_plugins'] ) ) {
	// Any /?wpacu_filter_plugins=[...] /?wpacu_only_load_plugins requests
	require dirname( __DIR__ ) . '/_common/_filter-via-debug.php';
}

// /?wpacu_only_load_plugins= was not used; go through the unload rules from "Plugins Manager" -> "IN THE DASHBOARD /wp-admin/"
if ( ! $wpacuOnlyLoadPluginsQueryStringUsed ) {
	// Fetch the existing rules (unload, load exceptions, etc.)
	require __DIR__ . '/_filter-from-rules-dash.php';
}
