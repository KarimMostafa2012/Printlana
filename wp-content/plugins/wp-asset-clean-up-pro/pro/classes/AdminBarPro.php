<?php
namespace WpAssetCleanUpPro;

use WpAssetCleanUp\Misc;

/**
 * Class AdminBarPro
 * @package WpAssetCleanUpPro
 */
class AdminBarPro
{
	/**
	 * @param $wpacuFilteredPlugins
	 *
	 * @return void
	 */
	public static function addMenuForAnyUnloadedPlugins($wpacuFilteredPlugins)
	{
		if ( empty($wpacuFilteredPlugins) ) {
			return;
		}

		global $wp_admin_bar;

		$allPlugins = function_exists('get_plugins') ? \get_plugins() : Misc::getActivePlugins();

		$pluginsIcons = Misc::getAllActivePluginsIcons();

		if (is_admin()) { // Dashboard view
			$titleUnloadText = sprintf( _n( '%d unloaded plugin on this admin page',
				'%d unload plugin rules took effect on this admin page', count( $wpacuFilteredPlugins ), 'wp-asset-clean-up-pro' ),
				count( $wpacuFilteredPlugins ) );
		} else { // Frontend view
			$titleUnloadText = sprintf( _n( '%d unloaded plugin on this frontend page',
				'%d unload plugin rules took effect on this frontend page', count( $wpacuFilteredPlugins ), 'wp-asset-clean-up-pro' ),
				count( $wpacuFilteredPlugins ) );
		}

		$wp_admin_bar->add_menu( array(
			'parent' => 'assetcleanup-parent',
			'id'     => 'assetcleanup-plugin-unload-rules-notice',
			'title'  => '<span style="margin: -10px 0 0;" class="wpacu-alert-sign-top-admin-bar dashicons dashicons-filter"></span> &nbsp; '.$titleUnloadText,
			'href'   => '#'
		) );

		$wpacuFilteredPluginsToPrint = array();

		foreach ($wpacuFilteredPlugins as $pluginPath) {
			if ( isset( $allPlugins[ $pluginPath ]['Name'] ) && $allPlugins[ $pluginPath ]['Name'] ) {
				$pluginTitle = $allPlugins[ $pluginPath ]['Name'];
			} else {
				$pluginTitle = $pluginPath;
			}

			$wpacuFilteredPluginsToPrint[] = array('title' => $pluginTitle, 'path' => $pluginPath);
		}

		uasort($wpacuFilteredPluginsToPrint, function($a, $b) {
			return strcmp($a['title'], $b['title']);
		});

		foreach ($wpacuFilteredPluginsToPrint as $pluginData) {
			$pluginTitle = $pluginData['title'];
			$pluginPath = $pluginData['path'];

			list($pluginDir) = explode('/', $pluginPath);

			if (isset($pluginsIcons[$pluginDir])) {
				$pluginIcon = '<img style="width: 20px; height: 20px; vertical-align: middle; display: inline-block;" width="20" height="20" alt="" src="'.$pluginsIcons[$pluginDir].'" />';
			} else {
				$pluginIcon = '<span class="dashicons dashicons-admin-plugins"></span>';
			}

			if (is_admin()) {
				$wpacuHref = esc_url(admin_url('admin.php?page=wpassetcleanup_plugins_manager&wpacu_sub_page=manage_plugins_dash#wpacu-dash-manage-'.$pluginPath));
			} else {
				$wpacuHref = esc_url(admin_url('admin.php?page=wpassetcleanup_plugins_manager&wpacu_sub_page=manage_plugins_front#wpacu-front-manage-'.$pluginPath));
			}

			$wp_admin_bar->add_menu(array(
				'parent' => 'assetcleanup-plugin-unload-rules-notice',
				'id'     => 'assetcleanup-plugin-unload-rules-list-'.$pluginPath,
				'title'  => $pluginIcon . ' &nbsp;' . $pluginTitle,
				'href'   => $wpacuHref
			));
		}

		}
}
