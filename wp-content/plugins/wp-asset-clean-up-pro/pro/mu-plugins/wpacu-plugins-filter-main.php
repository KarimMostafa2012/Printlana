<?php
if (! defined('WP_PLUGIN_DIR') || ! isset($_SERVER['REQUEST_URI'])) {
	return;
}

require_once __DIR__.'/_common.php';

/*
 * The following is used to alert the admin if the constant was added incorrectly in wp-config.php
 * When the checking is done within /wp-admin/admin.php?page=wpassetcleanup_plugins_manager&wpacu_sub_page=manage_plugins_dash
 * If both WPACU_ALLOW_DASH_PLUGIN_FILTER and WPACU_ALLOW_DASH_PLUGIN_FILTER_NOT_IN_EFFECT are set to false
 * then the admin has done the addition of the constant incorrectly within wp-config.php
 */
if ( ! (defined( 'WPACU_ALLOW_DASH_PLUGIN_FILTER' ) && WPACU_ALLOW_DASH_PLUGIN_FILTER) && is_admin() ) {
	define('WPACU_ALLOW_DASH_PLUGIN_FILTER_NOT_IN_EFFECT', true);
}

if ( ! wpacuTriggerPluginsFilter() ) {
	return;
}

/* [START] Filter Plugins Hook */
function wpacuFilterActivePlugins( $originalActivePluginsList )
{
	$tagName = current_filter();

	// The structure of the array is different for network activated plugins in case multisite is used
	if ( $tagName === 'site_option_active_sitewide_plugins' ) {
		$activePlugins = array_keys( $originalActivePluginsList );
	} else {
		$activePlugins = $originalActivePluginsList; // "option_active_plugins" tag
	}

	$activePluginsBeforeFiltering = $activePlugins;

	// [START - Own Asset CleanPro AJAX calls]
	// Only valid if the constant is defined as some themes are calling automatically functions from other functions
	// e.g. if the theme calls "the_field" without checking if the function (belonging to Advanced Custom Fields) exists
	// then the following filtering will trigger an error, so if the admin decides to enable it, he/she needs to be careful and test it properly
	if ( defined( 'WPACU_SKIP_OTHER_ACTIVE_PLUGINS_ON_ADMIN_AJAX_CALL' ) && WPACU_SKIP_OTHER_ACTIVE_PLUGINS_ON_ADMIN_AJAX_CALL !== false ) {
		$onlyLoadWpacuPlugins = $onlyWpacuPlugins = false;
		require WPACU_MU_FILTER_PLUGIN_DIR . '/_if-wpacu-own-ajax-calls.php';
		if ( $onlyLoadWpacuPlugins && is_array($onlyWpacuPlugins) && ! empty($onlyWpacuPlugins) ) {
			return $onlyWpacuPlugins; // only the "Asset CleanUp Pro" plugin should be triggered (no other plugin is relevant in this case)
		}
	}
	// [END - Own Asset CleanPro AJAX calls]

	// This list is empty by default, and it might be filled depending on the rules set in "Plugins Manager"
	// for both /wp-admin/ and the front-end view (for guest visitors)
	$activePluginsToUnload = array();

	if ( is_admin() ) {
		// [START - Filter Plugins within the Dashboard]
		// The user is inside the Dashboard; calls to /wp-admin/admin-ajax.php are excluded
		// Filter $activePlugins loaded within the Dashboard for the targeted pages
		$wpacuAlreadyFilteredName = 'wpacu_active_plugins_to_unload_dash_'.$tagName;
		if ( ! empty($GLOBALS[$wpacuAlreadyFilteredName]) && is_array($GLOBALS[$wpacuAlreadyFilteredName]) ) {
			$activePluginsToUnload = $GLOBALS[$wpacuAlreadyFilteredName]; // read it from the cache to avoid using too many resources
		} else {
			require __DIR__.'/_filter-from-dash/main-filter-dash.php';
			$GLOBALS[$wpacuAlreadyFilteredName] = $activePluginsToUnload;
		}
		// [END - Filter Plugins within the Dashboard]
	} else {
		// [START - Filter Plugins within the frontend view]
		$wpacuAlreadyFilteredName = 'wpacu_active_plugins_to_unload_front_'.$tagName;
		if ( ! empty($GLOBALS[$wpacuAlreadyFilteredName]) && is_array($GLOBALS[$wpacuAlreadyFilteredName]) ) {
			$activePluginsToUnload = $GLOBALS[$wpacuAlreadyFilteredName]; // read it from the cache to avoid using too many resources
		} else {
			require __DIR__.'/_filter-from-front/main-filter-front.php';
			$GLOBALS[$wpacuAlreadyFilteredName] = $activePluginsToUnload;
		}
		// [END - Filter Plugins within the frontend view]
	}

	// If there are any plugins in $activePluginsToUnload, then $activePlugins will be filtered to avoid loading the plugins marked for unloading
	$wpacuAlreadyFilteredNamePluginsToLoad = is_admin() ? 'wpacu_final_active_plugins_dash_'.$tagName : 'wpacu_final_active_plugins_front_'.$tagName;

	if ( ! empty( $activePluginsToUnload ) ) {
		if ( ! empty($GLOBALS[$wpacuAlreadyFilteredNamePluginsToLoad]) ) {
			$originalActivePluginsList = $GLOBALS[$wpacuAlreadyFilteredNamePluginsToLoad]; // Retrieve it from the cache
		} else {
			if ( isset( $GLOBALS['wpacu_filtered_plugins'] ) ) {
				foreach ( $activePluginsToUnload as $activePluginToUnload ) {
					if ( ! in_array( $activePluginToUnload, $GLOBALS['wpacu_filtered_plugins'] ) ) {
						$GLOBALS['wpacu_filtered_plugins'][] = $activePluginToUnload;
					}
				}
			} else {
				$GLOBALS['wpacu_filtered_plugins'] = $activePluginsToUnload;
			}

			$GLOBALS['wpacu_filtered_plugins'] = array_unique( $GLOBALS['wpacu_filtered_plugins'] );

			// Multisite
			if ( $tagName === 'site_option_active_sitewide_plugins' ) {
				foreach ( $activePluginsToUnload as $activePluginToUnload ) {
					if ( isset( $originalActivePluginsList[ $activePluginToUnload ] ) ) {
						unset( $originalActivePluginsList[ $activePluginToUnload ] );
					}
				}
			} else {
				// Single site ("option_active_plugins" tag)
				$originalActivePluginsList = array_diff( $originalActivePluginsList, $activePluginsToUnload );
			}

			$GLOBALS[ $wpacuAlreadyFilteredNamePluginsToLoad ] = $originalActivePluginsList; // stored it in the cache to save resources
		}
	}

	// Either empty (no filtering was done, the plugin list is the same) or having the changes applied (the plugins that were unloaded)
	$unloadedPlugins = array_diff($activePluginsBeforeFiltering, $originalActivePluginsList);

	// Return a final list of active plugins (filtered or not)
	if ( has_filter( 'wpacu_active_plugins_filter' ) && ! defined( 'WPACU_PLUGGABLE_LOADED' ) ) {
		require_once WPACU_MU_FILTER_PLUGIN_DIR . '/pluggable-custom.php';
		define( 'WPACU_PLUGGABLE_LOADED', true );
	}

	return apply_filters('wpacu_active_plugins_filter', $originalActivePluginsList, $unloadedPlugins, $activePluginsBeforeFiltering);
}

add_filter( 'option_active_plugins', 'wpacuFilterActivePlugins', 1, 1 );

if ( is_multisite() ) {
	add_filter( 'site_option_active_sitewide_plugins', 'wpacuFilterActivePlugins', 1, 1 );
}

add_filter('plugins_loaded', function() {
	remove_filter( 'option_active_plugins', 'wpacuFilterActivePlugins', 1 );

	if ( is_multisite() ) {
		remove_filter( 'site_option_active_sitewide_plugins', 'wpacuFilterActivePlugins', 1 );
	}
});

/* [END] Filter Plugins Hook */

