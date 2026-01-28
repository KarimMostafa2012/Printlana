<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
	exit;
}
?>
<hr style="margin: 15px 0;"/>

<div id="wpacu-plugins-load-manager-wrap">
	<?php
	foreach ($data['plugins_with_rules'] as $locationKey => $pluginsWithRules) {
		if ( ! empty($pluginsWithRules) ) {
			?>
			<h3><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugins with unload rules', 'wp-asset-clean-up'); ?>
				<?php
				if ($locationKey === 'plugins') {
					$pageTypeText = 'frontend';
					echo ' (in frontend view)';
				} elseif ($locationKey === 'plugins_dash') {
					$pageTypeText = 'admin';
					echo ' (within the dashboard, where the user is always logged-in)';
				}

				if (isset($data['plugins_with_rules'][$locationKey]) && count($data['plugins_with_rules'][$locationKey]) > 0) {
					echo ' &#10230; Total: '.count($data['plugins_with_rules'][$locationKey]);
				}
				?>
			</h3>

			<table class="wp-list-table wpacu-list-table widefat plugins striped" style="width: 100%;">
				<?php
				foreach ($pluginsWithRules as $pluginValues) {
					$pluginTitle = $pluginValues['title'];
					$pluginPath  = $pluginValues['path'];
					$pluginRules = $pluginValues['rules'];

					if (! is_array($pluginRules['status'])) {
						$pluginRules['status'] = array($pluginRules['status']); // from v1.1.8.3
					}

					list($pluginDir) = explode('/', $pluginPath);

					$isPluginActive = in_array($pluginPath, $data['plugins_active']);
					?>
					<tr <?php if ( ! $isPluginActive) { echo 'style="opacity: 0.6;"'; } ?>>
						<td class="wpacu_plugin_details">
							<div class="wpacu_plugin_icon" style="float: left;">
								<?php if (isset($data['plugins_icons'][$pluginDir])) { ?>
									<img width="40" height="40" alt="" src="<?php echo esc_attr($data['plugins_icons'][$pluginDir]); ?>" />
								<?php } else { ?>
									<div><span class="dashicons dashicons-admin-plugins"></span></div>
								<?php } ?>
							</div>

							<div style="float: left; margin-left: 8px; width: 80%;">
								<div>
									<span class="wpacu_plugin_title"><?php echo esc_html($pluginTitle); ?></span>
									<?php
									if (in_array($pluginPath, $data['plugins_active_network'])) {
										echo '&nbsp;<span title="Network Activated" class="dashicons dashicons-admin-multisite wpacu-tooltip"></span>';
									}
									?>
								</div>
								<div><span class="wpacu_plugin_path"><small><?php echo esc_html($pluginPath); ?></small></span></div>

								<?php
								if ( ! in_array($pluginPath, $data['plugins_active']) ) {
									?>
									<div><small><strong>Note:</strong> <span style="color: darkred;">The plugin is inactive, thus any of the rules set are also inactive &amp; irrelevant. They would be removed whenever the form from "Plugins Manager" is submitted.</span></small></div>
									<?php
								}
								?>
							</div>

							<div class="wpacu_clearfix"></div>
						</td>
						<td class="wpacu_plugin_rules" style="padding-left: 10px;">
							<?php
							$rulesList = array();

							$taxListValueToText = array(
								'category_all'          => __('"Category" taxonomy', 'wp-asset-clean-up'),
								'post_tag_all'          => __('"Tag" taxonomy', 'wp-asset-clean-up'),
								'product_cat_all'       => __('"WooCommerce Product Category" taxonomy', 'wp-asset-clean-up'),
								'product_tag_all'       => __('"WooCommerce Product Tag" taxonomy', 'wp-asset-clean-up'),
								'download_category_all' => __('"Easy Digital Downloads Download Category" taxonomy', 'wp-asset-clean-up'),
								'download_tag_all'      => __('"Easy Digital Downloads Download Tag" taxonomy', 'wp-asset-clean-up')
							);

                            $archiveListValueToText = \WpAssetCleanUpPro\PluginsManagerPro::generateArchivePageTypesList();

							global $wp_roles;
							$allUsersRoles = $wp_roles->roles;

							$unloadHomePage = in_array('unload_home_page', $pluginRules['status']);
							$unloadSiteWide = in_array('unload_site_wide', $pluginRules['status']);

							$unloadedViaPostType = (in_array('unload_via_post_type', $pluginRules['status']) && ! empty($pluginRules['unload_via_post_type']['values']));
							$unloadedViaTax      = (in_array('unload_via_tax', $pluginRules['status'])       && ! empty($pluginRules['unload_via_tax']['values']));
							$unloadedViaArchive  = (in_array('unload_via_archive', $pluginRules['status'])   && ! empty($pluginRules['unload_via_archive']['values']));

							$unloadedViaRegEx = in_array('unload_via_regex', $pluginRules['status']) &&
							                    isset($pluginRules['unload_via_regex']['value']) && $pluginRules['unload_via_regex']['value'];

							$unloadedIfLoggedIn      = in_array('unload_logged_in', $pluginRules['status']);
							$unloadedLoggedInViaRole = in_array('unload_logged_in_via_role', $pluginRules['status']) && ! empty($pluginRules['unload_logged_in_via_role']['values']);

							if ( $unloadSiteWide ) {
								$rulesList[] = '<span style="color: #cc0000;">Unloaded in all '.esc_html($pageTypeText).' pages</span>';
							} else {
								if ( $unloadHomePage ) {
									$rulesList[] = '<span style="color: #cc0000;">Unloaded in the homepage</span>';
								}

								if ( $unloadedViaPostType ) {
									$rulesList[] = '<span style="color: #cc0000;">Unloaded in all ' . esc_html( $pageTypeText ) . ' pages</span> belonging to the following post types: <strong style="color: #cc0000;">' . implode( ', ', $pluginRules['unload_via_post_type']['values'] ) . '</strong>';
								}

								if ( $unloadedViaTax ) {
									$rulesList[] = '<span style="color: #cc0000;">Unloaded in all ' . esc_html( $pageTypeText ) . ' pages</span> of these taxonomy page types: <strong style="color: #cc0000;">'
									               . implode( ', ', array_map(function($value) use($taxListValueToText) { return isset($taxListValueToText[$value]) ? $taxListValueToText[$value] : $value; }, $pluginRules['unload_via_tax']['values']) ) . '</strong>';
								}

                                if ( $unloadedViaArchive ) {
	                                $rulesList[] = '<span style="color: #cc0000;">Unloaded in all ' . esc_html( $pageTypeText ) . ' pages</span> of these archive (page list) page types</span>: <strong style="color: #cc0000;">'
	                                               . implode( ', ', array_map(function($value) use($archiveListValueToText) { return isset($archiveListValueToText[$value]) ? $archiveListValueToText[$value] : $value; }, $pluginRules['unload_via_archive']['values']) ) . '</strong>';
                                }

								if ( $unloadedViaRegEx ) {
									$rulesList[] = '<span style="color: #cc0000;">Unloaded in all ' . esc_html( $pageTypeText ) . ' pages</span> with the URIs (from the URL) matching this RegEx(es): <code style="color: #cc0000;">' . nl2br( $pluginRules['unload_via_regex']['value'] ) . '</code>';
								}

								if ( $unloadedIfLoggedIn ) {
									$rulesList[] = '<span style="color: #cc0000;">Unloaded if the user is logged-in</span>';
								}

                                if ( $unloadedLoggedInViaRole ) {
	                                $rulesList[] = '<span style="color: #cc0000;">Unloaded if the user is logged-in and has the following role(s):</span> <strong>'.implode( ', ', array_map(function($value) use($allUsersRoles) { return isset($allUsersRoles[$value]) ? translate_user_role($allUsersRoles[$value]['name']). ' ('.$value.')' : $value; }, $pluginRules['unload_logged_in_via_role']['values']) ).'</strong>';
                                }
							}

							$loadedHomePage    = (in_array('load_home_page', $pluginRules['status']));
							$loadedViaPostType = (in_array('load_via_post_type', $pluginRules['status']) && ! empty($pluginRules['load_via_post_type']['values']));
							$loadedViaTax      = (in_array('load_via_tax', $pluginRules['status'])       && ! empty($pluginRules['load_via_tax']['values']));
                            $loadedViaArchive  = (in_array('load_via_archive', $pluginRules['status'])   && ! empty($pluginRules['load_via_archive']['values']));

							$loadedLoggedInViaRole = in_array('load_logged_in_via_role', $pluginRules['status']) && ! empty($pluginRules['load_logged_in_via_role']['values']);

							if ($loadedHomePage) {
								$rulesList[] = '<span style="color: green;">Loaded (as an exception) on the homepage</span>';
							}

							if ($loadedViaPostType) {
								$rulesList[] = '<span style="color: green;">Loaded (as an exception) in all '.esc_html($pageTypeText).' pages</span> of these post types: <strong style="color: green;">' . implode( ', ', $pluginRules['load_via_post_type']['values'] ) . '</strong>';
							}

							if ($loadedViaTax) {
								$rulesList[] = '<span style="color: green;">Loaded (as an exception) in all '.esc_html($pageTypeText).' pages</span> of these taxonomy page types: <strong style="color: green;">' . implode( ', ', array_map(function($value) use($taxListValueToText) { return isset($taxListValueToText[$value]) ? $taxListValueToText[$value] : $value; }, $pluginRules['load_via_tax']['values']) ) . '</strong>';
							}

							if ($loadedViaArchive) {
								$rulesList[] = '<span style="color: green;">Loaded (as an exception) in all '.esc_html($pageTypeText).' pages</span> of these archive (page list) page types: <strong style="color: green;">' . implode( ', ', array_map(function($value) use($archiveListValueToText) { return isset($archiveListValueToText[$value]) ? $archiveListValueToText[$value] : $value; }, $pluginRules['load_via_archive']['values']) ) . '</strong>';
							}

							if (isset($pluginRules['load_via_regex']['enable'], $pluginRules['load_via_regex']['value'])) {
								$rulesList[] = '<span style="color: green;">Loaded (as an exception) for all '.esc_html($pageTypeText).' URIs</span> (from the URL) matching this RegEx(es): <code style="color: green;">'.nl2br($pluginRules['load_via_regex']['value']).'</code>';
							}

							if (isset($pluginRules['load_logged_in']['enable'], $pluginRules['load_logged_in']['enable'])) {
								$rulesList[] = '<span style="color: green;">Loaded (as an exception) in all '.esc_html($pageTypeText).' pages</span> if the user is logged in';
							}

                            if ($loadedLoggedInViaRole) {
	                            $rulesList[] = '<span style="color: green;">Loaded (as an exception) if the user is logged-in and has the following role(s):</span> <strong>'.implode( ', ', array_map(function($value) use($allUsersRoles) { return isset($allUsersRoles[$value]) ? translate_user_role($allUsersRoles[$value]['name']). ' ('.$value.')' : $value; }, $pluginRules['load_logged_in_via_role']['values']) ).'</strong>';
                            }

							if ( ! empty($rulesList) ) {
								echo '<ul style="margin: 0;">' . "\n";

								foreach ($rulesList as $ruleText) {
									echo '<li>'.$ruleText.'</li>'."\n";
								}

								echo '</ul>';
							}
							?>
							<div class="wpacu_clearfix"></div>
						</td>
					</tr>
				<?php } ?>
			</table>
			<?php
		} else {
			?>
			<p><?php _e('There are no rules added to any of the active plugins.', 'wp-asset-clean-up'); ?></p>
			<?php
		}
	}
	?>
</div>
<hr style="margin: 15px 0;"/>