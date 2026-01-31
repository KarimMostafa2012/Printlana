<?php
use WpAssetCleanUp\Misc;

if (! isset($data)) {
	exit;
}
?>
<!-- [wpacu_pro] -->
<div class="<?php if ($data['plugin_settings']['input_style'] !== 'standard') { echo 'wpacu-switch-enhanced'; } ?>" style="margin-bottom: 35px;">
    Enable all the rules below:&nbsp;
    <label class="wpacu_switch wpacu_with_text">
        <input type="checkbox"
               id="<?php echo WPACU_PLUGIN_ID . '_plugins_manager_checkbox'; ?>"
            <?php echo ($data['plugins_manager_dash_enable']) ? 'checked="checked"' : ''; ?>
               data-wpacu-type="dash"
               name="<?php echo WPACU_PLUGIN_ID . '_plugins_manager_dash_enable'; ?>"
               value="1" /> <span class="wpacu_slider wpacu_round"></span>
    </label> &nbsp; <small>* by default, this option is enabled; for debugging purposes, you can disable all rules in case there are any issues (resulting in all the plugins loaded), edit them and re-enable this option</small>
</div>
<!-- [/wpacu_pro] -->

<div data-wpacu-sub-page-area="<?php echo $data['wpacu_sub_page']; ?>"
     class="wpacu-wrap <?php if ( ! $data['plugins_manager_dash_enable'] ) { ?>wpacu-area-disabled<?php } ?>"
     id="wpacu-plugins-load-manager-wrap">
	<form method="post" action="" class="wpacu_settings_form">
		<?php
		$pluginsRows = array();

		foreach ($data['active_plugins'] as $pluginData) {
			$data['plugin_path'] = $pluginPath = $pluginData['path'];
			list($pluginDir) = explode('/', $pluginPath);

			// [wpacu_pro]
			$pluginStatus = isset($data['rules'][$pluginPath]['status']) ? $data['rules'][$pluginPath]['status'] : array(); // array() from v1.1.8.3

			if (! is_array($pluginStatus)) {
				$pluginStatus = array($pluginStatus); // from v1.1.8.3
			}
			// [/wpacu_pro]

			$data['is_unload_site_wide'] = in_array('unload_site_wide', $pluginStatus);
			$data['is_unload_via_regex'] = in_array('unload_via_regex', $pluginStatus);

            // [Unload logged-in via role]
            $data['is_unload_logged_in_via_role'] = in_array( 'unload_logged_in_via_role', $pluginStatus );
            $data['unload_logged_in_via_role_chosen'] = ( ( isset( $data['rules'][ $pluginPath ]['unload_logged_in_via_role']['values'] ) && is_array( $data['rules'][ $pluginPath ]['unload_logged_in_via_role']['values'] ) )
                ? $data['rules'][ $pluginPath ]['unload_logged_in_via_role']['values']
                : array() );

            if (empty($data['unload_logged_in_via_role_chosen'])) {
                $data['is_unload_logged_in_via_role'] = false; // the option was somehow set, but no values were set
            }
            // [/Unload logged-in via role]

            $data['no_unload_rule_set'] = ! (
                $data['is_unload_site_wide'] ||
                $data['is_unload_via_regex'] ||
                $data['is_unload_logged_in_via_role']
            );

            // [Load exceptions]
            $data['is_load_via_regex'] = isset($data['rules'][$pluginPath]['load_via_regex']['enable']) && $data['rules'][$pluginPath]['load_via_regex']['enable'];

            $data['is_load_logged_in_via_role'] = in_array( 'load_logged_in_via_role', $pluginStatus );
            $data['load_logged_in_via_role_chosen'] = ( ( isset( $data['rules'][ $pluginPath ]['load_logged_in_via_role']['values'] ) && is_array( $data['rules'][ $pluginPath ]['load_logged_in_via_role']['values'] ) )
                ? $data['rules'][ $pluginPath ]['load_logged_in_via_role']['values']
                : array() );
            // [/Load exceptions]

			ob_start();

			$pluginAreaState = array_key_exists($pluginData['path'], $data['plugins_contracted_list']) ? 'contracted' : 'expanded';
			?>
			<tr>
				<td class="wpacu_plugin_icon" width="46">
					<?php if(isset($data['plugins_icons'][$pluginDir])) { ?>
						<img width="44" height="44" alt="" src="<?php echo esc_attr($data['plugins_icons'][$pluginDir]); ?>" />
					<?php } else { ?>
						<div><span class="dashicons dashicons-admin-plugins"></span></div>
					<?php } ?>
				</td>
				<td class="wpacu_plugin_details"
                    data-wpacu-plugin-path="<?php echo esc_attr($pluginData['path']); ?>"
                    data-wpacu-status-area="<?php echo $pluginAreaState; ?>"
                    id="wpacu-dash-manage-<?php echo esc_attr($pluginData['path']); ?>">
                    <div class="wpacu_plugin_details_top_area">
                        <div class="wpacu_plugin_expand_contract_area">
                            <button type="button" class="wpacu_wp_button wpacu_wp_button_secondary"><img class="wpacu_ajax_loader wpacu_hide" align="top" src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt="" /> <span class="dashicons"></span></button>
                        </div>
                        <span class="wpacu_plugin_title"><?php echo esc_html($pluginData['title']); ?></span>
                        <span class="wpacu_plugin_path">&nbsp;<?php echo esc_html($pluginData['path']); ?></span>
                    </div>
					<?php
					if ($pluginData['network_activated']) {
						echo '&nbsp;<span title="Network Activated" class="dashicons dashicons-admin-multisite wpacu-tooltip"></span>';
					}
					?>
                    <div class="wpacu_clearfix"></div>

                    <!-- [Start] Unload Rules -->
                    <?php
                    include '_dash-areas/_unloads.php';
                    ?>
                    <!-- [End] Unload Rules -->

                    <div class="wpacu_clearfix"></div>

                    <!-- [Start] Make exceptions: Load Rules -->
					<?php
					include '_dash-areas/_load-exceptions.php';
					?>
                    <!-- [End] Make exceptions: Load Rules -->

					<div class="wpacu_clearfix"></div>
				</td>
			</tr>
			<?php
			$trOutput = ob_get_clean();

			if (empty($pluginStatus)) {
				$pluginsRows['always_loaded'][] = $trOutput;
			} else {
				$pluginsRows['has_unload_rules'][] = $trOutput;
			}
		}

		if ( ! empty($pluginsRows['has_unload_rules']) ) {
			$totalWithUnloadRulesPlugins = count($pluginsRows['has_unload_rules']);
			?>
            <div class="wpacu_contract_expand_plugins_area">
                <div class="wpacu_col_left">
                    <h3><span style="color: #c00;" class="dashicons dashicons-admin-plugins"></span> <span style="color: #c00;"><?php echo (int)$totalWithUnloadRulesPlugins; ?></span> plugin<?php echo ($totalWithUnloadRulesPlugins > 1) ? 's' : ''; ?> with active unload rules</h3>
                </div>
                <div class="wpacu_plugins_groups_change_state_area wpacu_col_right">
                    <button type="button" title="Contract all from this area" data-wpacu-for-area="plugins-with-unload-rules" class="wpacu_plugins_contract_expand_all wpacu_plugins_contract_all wpacu_wp_button wpacu_wp_button_secondary"><img class="wpacu_hide wpacu_ajax_loader" align="top" src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt="" /> <span style="vertical-align: middle;" class="dashicons dashicons-plus-alt2"></span></button>&nbsp;
                    <button type="button" title="Expand all from this area" data-wpacu-for-area="plugins-with-unload-rules" class="wpacu_plugins_contract_expand_all wpacu_plugins_expand_all wpacu_wp_button wpacu_wp_button_secondary"><img class="wpacu_hide wpacu_ajax_loader" align="top" src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt="" /> <span style="vertical-align: middle;" class="dashicons dashicons-minus"></span></button>
                </div>
                <div class="wpacu_clearfix"></div>
            </div>

            <table data-wpacu-area="plugins-with-unload-rules"
                   class="wp-list-table wpacu-list-table widefat plugins striped">
				<?php
				foreach ( $pluginsRows['has_unload_rules'] as $pluginRowOutput ) {
					echo Misc::stripIrrelevantHtmlTags($pluginRowOutput) . "\n";
				}
				?>
			</table>
            <div style="margin: 0 0 25px;"></div>
            <hr style="width: 98%;" />
			<?php
		}

		if ( ! empty($pluginsRows['always_loaded'] ) )  {
			if (isset($pluginsRows['has_unload_rules']) && count($pluginsRows['has_unload_rules']) > 0) {
				?>
				<div style="margin-top: 35px;"></div>
				<?php
			}

			$totalAlwaysLoadedPlugins = count($pluginsRows['always_loaded']);
			?>
            <div class="wpacu_contract_expand_plugins_area">
                <div class="wpacu_col_left">
                    <h3><span style="color: green;" class="dashicons dashicons-admin-plugins"></span> <span style="color: green;"><?php echo (int)$totalAlwaysLoadedPlugins; ?></span> plugin<?php echo ($totalAlwaysLoadedPlugins > 1) ? 's' : ''; ?> with no active unload rules (loaded by default)</h3>
                </div>
                <div class="wpacu_plugins_groups_change_state_area wpacu_col_right">
                    <button title="Contract all from this area" data-wpacu-for-area="plugins-loaded-by-default" class="wpacu_plugins_contract_expand_all wpacu_plugins_contract_all wpacu_wp_button wpacu_wp_button_secondary"><img class="wpacu_ajax_loader wpacu_hide" align="top" src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt="" /> <span style="vertical-align: middle;" class="dashicons dashicons-plus-alt2"></span></button>&nbsp;
                    <button title="Expand all from this area" data-wpacu-for-area="plugins-loaded-by-default" class="wpacu_plugins_contract_expand_all wpacu_plugins_expand_all wpacu_wp_button wpacu_wp_button_secondary"><img class="wpacu_ajax_loader wpacu_hide" align="top" src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt="" /> <span style="vertical-align: middle;" class="dashicons dashicons-minus"></span></button>
                </div>
                <div class="wpacu_clearfix"></div>
            </div>

            <table data-wpacu-area="plugins-loaded-by-default"
                   class="wp-list-table wpacu-list-table widefat plugins striped">
				<?php
				foreach ( $pluginsRows['always_loaded'] as $pluginRowOutput ) {
					echo Misc::stripIrrelevantHtmlTags($pluginRowOutput) . "\n";
				}
				?>
			</table>
			<?php
		}
		?>
		<div id="wpacu-update-button-area" style="margin-left: 0;">
			<?php
			wp_nonce_field('wpacu_plugin_manager_update', 'wpacu_plugin_manager_nonce');
			submit_button('Apply changes within /wp-admin/');
			?>
			<div id="wpacu-updating-settings" style="margin-left: 278px; top: 28px;">
				<img src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" align="top" width="20" height="20" alt="" />
			</div>
			<input type="hidden" name="wpacu_plugins_manager_submit" value="1" />
		</div>
	</form>
</div>