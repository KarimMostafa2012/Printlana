<?php
if (! isset($activePlugins, $activePluginsToUnload, $tagName)) {
	exit;
}

$pluginsRulesDbListJson = get_option('wpassetcleanup_global_data');

if ($pluginsRulesDbListJson) {
	$pluginsRulesDbList = @json_decode( $pluginsRulesDbListJson, true );

	// Are there any valid load exceptions / unload RegExes? Fill $activePluginsToUnload
	if ( ! empty( $pluginsRulesDbList[ 'plugins' ] ) ) {
		$pluginsRules = $pluginsRulesDbList[ 'plugins' ];

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
			if ( ! empty($pluginRule['status']) ) {
				if ( ! is_array($pluginRule['status']) ) {
					$pluginRule['status'] = array($pluginRule['status']); // from v1.1.8.3
				}

				// Are there any load exceptions?
				if ( in_array( 'load_home_page', $pluginRule['status'] ) && wpacuIsHomePageUrl($requestUriAsItIs) ) {
					continue;
				}

				$isLoadExceptionViaPostTypeSet = in_array('load_via_post_type', $pluginRule['status'])
				    && (! empty($pluginRule['load_via_post_type']['values']))
                    && is_array($pluginRule['load_via_post_type']['values']);

				if ($isLoadExceptionViaPostTypeSet && $requestUriAsItIs) {
					$uriToPost = wpacuUrlToPageType($requestUriAsItIs);

					if (isset($uriToPost['post_type'])
					    && $uriToPost['post_type']
					    && in_array($uriToPost['post_type'], $pluginRule['load_via_post_type']['values'])) {
						continue; // Skip to the next plugin as this one has a load exception matching the condition
					}
				}

				$isLoadExceptionViaTaxSet = in_array('load_via_tax', $pluginRule['status'])
                          && (! empty($pluginRule['load_via_tax']['values']))
                          && is_array($pluginRule['load_via_tax']['values']);

				if ($isLoadExceptionViaTaxSet && $requestUriAsItIs) {
					$uriToPageType = wpacuUrlToPageType($requestUriAsItIs);

					$isTaxMatch = false;

					// Custom taxonomy or the default WordPress ones
					if ( isset($uriToPageType['is_taxonomy']) && $uriToPageType['is_taxonomy'] ) {
						$toCheckInValues = $uriToPageType['page_type'];

						if ( in_array($uriToPageType['page_type'], wpacuGetCommonTaxonomies()) ) {
							$toCheckInValues .= '_all';
						}

						if ( in_array($toCheckInValues, $pluginRule['load_via_tax']['values']) ) {
							$isTaxMatch = true;
						}
					}

					if ($isTaxMatch) {
						continue; // Skip to the next plugin as this one has a load exception matching the condition
					}
				}

				$isLoadExceptionViaArchiveSet = in_array('load_via_archive', $pluginRule['status'])
			                                && (! empty($pluginRule['load_via_archive']['values']))
				                            && is_array($pluginRule['load_via_archive']['values']);

				if ( $isLoadExceptionViaArchiveSet && $requestUriAsItIs ) {
					$uriToPageType = wpacuUrlToPageType( $requestUriAsItIs );
					$isArchiveMatch = false;

					// Custom taxonomy or the default WordPress ones
					if ( isset($uriToPageType['is_archive_type']) && $uriToPageType['is_archive_type'] ) {
						$toCheckArchiveType = $uriToPageType['page_type'];

						if ( in_array($toCheckArchiveType, $pluginRule['load_via_archive']['values']) ) {
							$isArchiveMatch = true;
						}
					}

					if ($isArchiveMatch) {
						continue; // Skip to the next plugin as this one has a load exception matching the condition
					}
				}

				$isLoadExceptionRegExMatch = isset($pluginRule['load_via_regex']['enable'], $pluginRule['load_via_regex']['value'])
				                        && $pluginRule['load_via_regex']['enable'] && wpacuPregMatchInput($pluginRule['load_via_regex']['value'], $requestUriAsItIs);

				if ( $isLoadExceptionRegExMatch ) {
					continue; // Skip to the next plugin as this one has a load exception matching the condition
				}

				// Should the plugin be always loaded as an if the user is logged-in? (priority over the same rule for unloading)
				$isLoadExceptionIfLoggedInEnable     = isset($pluginRule['load_logged_in']['enable']) && $pluginRule['load_logged_in']['enable'];
				$isLoadExceptionIfLoggedInViaRoleSet = in_array('load_logged_in_via_role', $pluginRule['status'])
				                                             && (! empty($pluginRule['load_logged_in_via_role']['values']))
			                                                 && is_array($pluginRule['load_logged_in_via_role']['values']);

				// Unload the plugin if the user is logged-in?
				$isUnloadIfLoggedInEnable     = in_array('unload_logged_in', $pluginRule['status']);
				$isUnloadIfLoggedInViaRoleSet = in_array('unload_logged_in_via_role', $pluginRule['status'])
                                                && (! empty($pluginRule['unload_logged_in_via_role']['values']))
                                                && is_array($pluginRule['unload_logged_in_via_role']['values']);

				if ( ($isLoadExceptionIfLoggedInEnable  ||
				      $isUnloadIfLoggedInEnable         ||
				      $isUnloadIfLoggedInViaRoleSet     ||
				      $isLoadExceptionIfLoggedInViaRoleSet ) && ! defined('WPACU_PLUGGABLE_LOADED')) {
					require_once WPACU_MU_FILTER_PLUGIN_DIR . '/pluggable-custom.php';
					define('WPACU_PLUGGABLE_LOADED', true);
				}

				if ($isLoadExceptionIfLoggedInEnable && function_exists('wpacu_is_user_logged_in') && wpacu_is_user_logged_in()) {
					continue; // Do not unload it (priority)
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
				} else {
					if ( in_array( 'unload_home_page', $pluginRule['status'] ) && wpacuIsHomePageUrl($requestUriAsItIs) ) {
						$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
					}

					if ( in_array( 'unload_via_post_type', $pluginRule['status'] ) ) {
						$isUnloadViaPostTypeSet = is_array( $pluginRule['unload_via_post_type']['values'] ) &&
						                          ( ! empty( $pluginRule['unload_via_post_type']['values'] ) );

						if ( $isUnloadViaPostTypeSet && $requestUriAsItIs ) {
							$uriToPost = wpacuUrlToPageType( $requestUriAsItIs );

							if ( isset( $uriToPost['post_type'] ) && in_array( $uriToPost['post_type'], $pluginRule['unload_via_post_type']['values'] ) ) {
								$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
							}
						}
					}

					if ( in_array( 'unload_via_tax', $pluginRule['status'] ) ) {
						$isUnloadViaTaxSet = ( ! empty( $pluginRule['unload_via_tax']['values'] ) ) && is_array( $pluginRule['unload_via_tax']['values'] );

						if ( $isUnloadViaTaxSet && $requestUriAsItIs ) {
							$uriToPageType = wpacuUrlToPageType( $requestUriAsItIs );
							$isTaxMatch = false;

							// Custom taxonomy or the default WordPress ones
							if ( isset($uriToPageType['is_taxonomy']) && $uriToPageType['is_taxonomy'] ) {
								$toCheckInValues = $uriToPageType['page_type'];

								if ( in_array($uriToPageType['page_type'], wpacuGetCommonTaxonomies()) ) {
									$toCheckInValues .= '_all';
								}

								if ( in_array($toCheckInValues, $pluginRule['unload_via_tax']['values']) ) {
									$isTaxMatch = true;
								}
							}

							if ( $isTaxMatch ) {
								$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
							}
						}
					}

					if ( in_array( 'unload_via_archive', $pluginRule['status'] ) ) {
						$isUnloadViaArchiveSet = ( ! empty( $pluginRule['unload_via_archive']['values'] ) )
						                     && is_array( $pluginRule['unload_via_archive']['values'] );

						if ( $isUnloadViaArchiveSet && $requestUriAsItIs ) {
							$uriToPageType = wpacuUrlToPageType( $requestUriAsItIs );
							$isArchiveMatch = false;

							// Custom taxonomy or the default WordPress ones
							if ( isset($uriToPageType['is_archive_type']) && $uriToPageType['is_archive_type'] ) {
								$toCheckArchiveType = $uriToPageType['page_type'];

								if ( in_array($toCheckArchiveType, $pluginRule['unload_via_archive']['values']) ) {
									$isArchiveMatch = true;
								}
							}

							if ( $isArchiveMatch ) {
								$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
							}
						}
					}

					if ( in_array( 'unload_via_regex', $pluginRule['status'] ) ) {
						$isUnloadRegExMatch = isset( $pluginRule['unload_via_regex']['value'] ) && wpacuPregMatchInput( $pluginRule['unload_via_regex']['value'],
								$requestUriAsItIs );
						if ( $isUnloadRegExMatch ) {
							$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
						}
					}

					// Unload the plugin if the user is logged-in?
					if ($isUnloadIfLoggedInEnable && (function_exists('wpacu_is_user_logged_in') && wpacu_is_user_logged_in())) {
						$activePluginsToUnload[] = $pluginPath; // Add it to the unloading list
					}

					if ($isUnloadIfLoggedInViaRoleSet && function_exists('wpacu_current_user_can')) {
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
}

// [START - Make exception and load the plugin for debugging purposes]
if (isset($_GET['wpacu_load_plugins']) && $_GET['wpacu_load_plugins']) {
	require WPACU_MU_FILTER_PLUGIN_DIR . '/_common/_plugin-load-exceptions-via-query-string.php';
}
// [END - Make exception and load the plugin for debugging purposes
