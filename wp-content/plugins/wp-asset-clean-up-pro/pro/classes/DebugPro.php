<?php
namespace WpAssetCleanUpPro;

use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\PluginsManager;

/**
 *
 */
class DebugPro
{
	/**
	 *
	 */
	public function __construct()
	{
		add_action('admin_init', array($this, 'showDebugOptionsDashPrepare'), PHP_INT_MAX);
		add_action('wp_loaded',  array($this, 'showDebugOptionsDashOutput'),  PHP_INT_MAX);
	}

	/**
	 *
	 */
	public function showDebugOptionsDashPrepare()
	{
		if (! Menu::userCanManageAssets()) {
			return;
		}

		ob_start();
		?>
		<style <?php echo Misc::getStyleTypeAttribute(); ?>>
			<?php echo file_get_contents(WPACU_PLUGIN_DIR.'/assets/wpacu-debug.css'); ?>
		</style>
		<script <?php echo Misc::getScriptTypeAttribute(); ?>>
			<?php echo file_get_contents(WPACU_PLUGIN_DIR.'/assets/wpacu-debug.js'); ?>
		</script>
		<?php
		$wpacuUnloadedPluginsStatus = false;

		if (isset($GLOBALS['wpacu_filtered_plugins']) && $wpacuFilteredPlugins = $GLOBALS['wpacu_filtered_plugins']) {
			$wpacuUnloadedPluginsStatus = true; // there are rules applied
		}
		?>
		<div id="wpacu-debug-admin-area">
			<h4><?php echo WPACU_PLUGIN_TITLE; ?>: Debug Notice</h4>
			<?php
			if ($wpacuUnloadedPluginsStatus) {
				sort($wpacuFilteredPlugins);

				// Get all plugins and their basic information
				$allPlugins = $allRemainingPlugins = get_plugins();
				$pluginsIcons = Misc::getAllActivePluginsIcons();

				?>
				<p style="margin: 20px 0 10px;">The following plugins are <strong style="color: darkred;">unloaded on this page</strong> using the rules that took effect from <em>"Plugins Manager" -&gt; "IN THE DASHBOARD /wp-admin/"</em> (within <?php echo WPACU_PLUGIN_TITLE; ?>'s menu):</p>
				<div id="wpacu-debug-plugins-list">
					<?php
					foreach ($wpacuFilteredPlugins as $pluginPath) {
						unset($allRemainingPlugins[$pluginPath]);

						$pluginTitle = '';
						if (isset($allPlugins[$pluginPath]['Name']) && $allPlugins[$pluginPath]['Name']) {
							$pluginTitle = $allPlugins[$pluginPath]['Name'];
						}

						list($pluginDir) = explode('/', $pluginPath);
						?>
						<div style="margin: 0 0 8px;">
							<div class="wpacu_plugin_icon" style="float: left;">
								<?php if (isset($pluginsIcons[$pluginDir])) { ?>
									<img width="20" height="20" alt="" src="<?php echo esc_attr($pluginsIcons[$pluginDir]); ?>" />
								<?php } else { ?>
									<div><span class="dashicons dashicons-admin-plugins"></span></div>
								<?php } ?>
							</div>

							<div style="float: left; margin-left: 8px;">
								<div><span><strong><?php echo esc_html($pluginTitle); ?></strong></span> * <em><?php echo esc_html($pluginPath); ?></em></div>
							</div>
							<div style="clear: both;"></div>
						</div>
						<?php
					}
					?>
				</div>
				<?php

				if ( ! empty($allRemainingPlugins) ) {
					?>
					<hr />
					<p style="margin: 20px 0 10px;">The following plugins remain <strong style="color: green;">loaded on this page</strong>:</p>
					<div id="wpacu-debug-plugins-list">
						<?php
						foreach (array_keys($allRemainingPlugins) as $pluginPath) {
							$pluginTitle = '';
							if (isset($allPlugins[$pluginPath]['Name']) && $allPlugins[$pluginPath]['Name']) {
								$pluginTitle = $allPlugins[$pluginPath]['Name'];
							}

							list($pluginDir) = explode('/', $pluginPath);
							?>
							<div style="margin: 0 0 8px;">
								<div class="wpacu_plugin_icon" style="float: left;">
									<?php if (isset($pluginsIcons[$pluginDir])) { ?>
										<img width="20" height="20" alt="" src="<?php echo esc_attr($pluginsIcons[$pluginDir]); ?>" />
									<?php } else { ?>
										<div><span class="dashicons dashicons-admin-plugins"></span></div>
									<?php } ?>
								</div>

								<div style="float: left; margin-left: 8px;">
									<div><span><strong><?php echo esc_html($pluginTitle); ?></strong></span> * <em><?php echo esc_html($pluginPath); ?></em></div>
								</div>
								<div style="clear: both;"></div>
							</div>
							<?php
						}
						?>
					</div>
					<?php
				}
			} else {
                if ((int)Main::instance()->settings['plugins_manager_dash_disable'] === 1) {
                ?>
                    <p style="margin: 10px 0 0;"><strong>Note:</strong> No plugin unload rules that might be set are taking effect because they are set to be ignored (turned "OFF" in <em><a target="_blank" href="<?php echo admin_url('admin.php?page='.WPACU_PLUGIN_ID.'_plugins_manager&wpacu_sub_page=manage_plugins_dash') ?>">"Plugins Manager" -&gt; "IN THE DASHBOARD /wp-admin/"</a></em>), perhaps for debugging purposes.</p>
                <?php
                } else {
				?>
				    <p>There are no rules (from any that might be set) that are taking effect for unloading any plugins on this page from <em>"Plugins Manager" -&gt; "IN THE DASHBOARD /wp-admin/"</em> (within <?php echo WPACU_PLUGIN_TITLE; ?>'s menu).</p>
				<?php
                }
			}
			?>
		</div>
		<?php
		$GLOBALS['wpacu_debug_output'] = ob_get_clean();
	}

	/**
	 *
	 */
	public function showDebugOptionsDashOutput()
	{
		ob_start(function($htmlSource) {
			return preg_replace(
				'#</body>(\s+|\n+)</html>#si',
				$GLOBALS['wpacu_debug_output'].'</body>'."\n".'</html>',
				$htmlSource);
		});
	}

	/**
	 * @return void
	 */
	public static function showDebugPluginsListToUnload()
    {
        ?>
        <hr />

        <strong>Preview page: Thick plugins for unloading</strong><br >
        <p><small>By default, any already unloaded plugins from "Plugins Manager" will be selected here (unless you already submitted the form with a different selection).</small></p>

	    <?php if ( ! empty($GLOBALS['wpacu_filtered_plugins']) ) { ?>
        <p><small>If you want ALL plugins to load back, just deselect everything from the list below and submit the form.</small></p>
    <?php } ?>

        <table>
		    <?php
		    $activePlugins = PluginsManager::getActivePlugins();
		    uasort($activePlugins, function($a, $b) {
			    return strcmp($a['title'], $b['title']);
		    });

		    $pluginsIcons = Misc::getAllActivePluginsIcons();

		    foreach ($activePlugins as $pluginData) {
			    $pluginTitle = $pluginData['title'];
			    $pluginPath = $pluginData['path'];
			    list($pluginDir) = explode('/', $pluginPath);
			    ?>
                <tr class="wpacu_plugin_row_debug_unload <?php if (isset($GLOBALS['wpacu_filtered_plugins']) && in_array($pluginPath, $GLOBALS['wpacu_filtered_plugins'])) { echo 'wpacu_plugin_row_debug_unload_marked'; } ?>">
                    <td style="padding: 0; width: 46px; text-align: center;">
                        <label style="cursor: pointer; margin: -12px 0 0 12px;" class="wpacu_plugin_unload_debug_container" for="wpacu_filter_plugin_<?php echo esc_attr($pluginPath); ?>">
                            <input type="checkbox"
                                   class="wpacu_plugin_unload_debug_checkbox"
                                   style="cursor: pointer; width: 20px; height: 20px;"
                                   id="wpacu_filter_plugin_<?php echo esc_attr($pluginPath); ?>"
                                   name="wpacu_filter_plugins[]"
                                   value="<?php echo esc_attr($pluginPath); ?>"
							    <?php if (isset($GLOBALS['wpacu_filtered_plugins']) && in_array($pluginPath, $GLOBALS['wpacu_filtered_plugins'])) { echo 'checked="checked"'; } ?> />
                            <span class="wpacu_plugin_unload_debug_checkbox_checkmark"></span>
                        </label>
                    </td>
                    <td style="padding: 5px; text-align: center; cursor: pointer;">
                        <label style="cursor: pointer;" for="wpacu_filter_plugin_<?php echo esc_attr($pluginPath); ?>">
						    <?php if(isset($pluginsIcons[$pluginDir])) { ?>
                                <img width="40" height="40" alt="" src="<?php echo esc_attr($pluginsIcons[$pluginDir]); ?>" />
						    <?php } else { ?>
                                <div><span style="font-size: 34px; width: 34px; height: 34px;" class="dashicons dashicons-admin-plugins"></span></div>
						    <?php } ?>
                        </label>
                    </td>
                    <td style="padding: 10px;">
                        <label for="wpacu_filter_plugin_<?php echo esc_attr($pluginPath); ?>" style="cursor: pointer;"><span class="wpacu_plugin_title" style="font-weight: 500;"><?php echo esc_html($pluginTitle); ?></span></label><br />
                        <span class="wpacu_plugin_path" style="font-style: italic;"><small><?php echo esc_attr($pluginPath); ?></small></span>
                    </td>
                </tr>
			    <?php
		    }
		    ?>
        </table>
        <?php
    }
}
