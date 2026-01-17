<?php
if (! isset($activePlugins, $activePluginsToUnload)) {
	exit;
}

$pluginsRulesDbListJson = get_option('wpassetcleanup_global_data');

if ($pluginsRulesDbListJson) {
	$pluginsRulesDbList = @json_decode( $pluginsRulesDbListJson, true );

	// Are there any valid load exceptions / unload RegExes? Fill $activePluginsToUnload
	if ( ! empty( $pluginsRulesDbList[ 'plugins_dash' ] ) ) {
		$pluginsRules = $pluginsRulesDbList[ 'plugins_dash' ];

        // We want to make sure the RegEx rules will be working fine if certain characters (e.g. Thai ones) are used
		$requestUriAsItIs = rawurldecode($_SERVER['REQUEST_URI']);

		// Unload site-wide
		foreach ($pluginsRules as $pluginPath => $pluginRule) {
			if (! in_array($pluginPath, $activePlugins)) {
				// Only relevant if the plugin is active
				// Otherwise it's unloaded (inactive) anyway
				continue;
			}

			// 'status' refers to the Unload Status (any option that was chosen)
			if (isset($pluginRule['status']) && ! empty($pluginRule['status'])) {
				if ( ! is_array($pluginRule['status']) ) {
					$pluginRule['status'] = array($pluginRule['status']); // from v1.1.8.3
				}


				// Are there any load exceptions?
				$isLoadExceptionRegExMatch = isset($pluginRule['load_via_regex']['enable'], $pluginRule['load_via_regex']['value'])
				                        && $pluginRule['load_via_regex']['enable'] && wpacuPregMatchInput($pluginRule['load_via_regex']['value'], $requestUriAsItIs);

                $isLoadExceptionIfLoggedInViaRoleSet = in_array('load_logged_in_via_role', $pluginRule['status'])
                                                       && (! empty($pluginRule['load_logged_in_via_role']['values']))
                                                       && is_array($pluginRule['load_logged_in_via_role']['values']);

				if ( $isLoadExceptionRegExMatch ) {
					continue; // Skip to the next plugin as this one has a load exception matching the condition
				}

                $isUnloadIfLoggedInViaRoleSet = in_array('unload_logged_in_via_role', $pluginRule['status'])
                                                && (! empty($pluginRule['unload_logged_in_via_role']['values']))
                                                && is_array($pluginRule['unload_logged_in_via_role']['values']);

                if ( ($isLoadExceptionIfLoggedInViaRoleSet ||
                      $isUnloadIfLoggedInViaRoleSet) && ! defined('WPACU_PLUGGABLE_LOADED')) {
                    require_once WPACU_MU_FILTER_PLUGIN_DIR . '/pluggable-custom.php';
                    define('WPACU_PLUGGABLE_LOADED', true);
                }

                if ($isLoadExceptionIfLoggedInViaRoleSet && function_exists('wpacu_current_user_can')) {
                    foreach ($pluginRule['load_logged_in_via_role']['values'] as $role) {
                        if (wpacu_current_user_can($role)) {
                            continue 2; // Do not unload it (the user has a role from the load exception list, "If the logged-in user has any of these roles:")
                        }
                    }
                }

				if ( in_array('unload_site_wide', $pluginRule['status']) ) {
					$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
				} elseif ( in_array('unload_via_regex', $pluginRule['status']) ) {
					$isUnloadRegExMatch = isset($pluginRule['unload_via_regex']['value']) && wpacuPregMatchInput($pluginRule['unload_via_regex']['value'], $requestUriAsItIs);
					if ($isUnloadRegExMatch) {
						$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
					}
				} elseif ( $isUnloadIfLoggedInViaRoleSet && function_exists('wpacu_current_user_can') ) {
                    foreach ($pluginRule['unload_logged_in_via_role']['values'] as $role) {
                        if (wpacu_current_user_can($role)) {
                            $activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
                            break;
                        }
                    }
                }
			}
		}
	}
}

// [START - Make exception and load the plugin for debugging purposes]
if (isset($_GET['wpacu_load_plugins']) && $_GET['wpacu_load_plugins']) {
	require WPACU_MU_FILTER_PLUGIN_DIR.'/_common/_plugin-load-exceptions-via-query-string.php';
}
// [END - Make exception and load the plugin for debugging purposes]