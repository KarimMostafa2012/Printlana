<?php
namespace WpAssetCleanUpPro;


/**
 * Class ImportExportPro
 * @package WpAssetCleanUpPro
 */
class ImportExportPro
{
	/**
	 * @return array
	 */
	public static function getCriticalCssOptionsArray()
	{
		global $wpdb;

		$criticalCssOptionsArray = array();

		$likeCssQuery = WPACU_PLUGIN_ID . '_critical_css_%';
		$sqlFetchAnyCriticalCssOptionNames = <<<SQL
SELECT option_name FROM `{$wpdb->prefix}options` WHERE option_name LIKE '{$likeCssQuery}'
SQL;
		$allCriticalCssOptionNames = $wpdb->get_col($sqlFetchAnyCriticalCssOptionNames);

		if (! empty($allCriticalCssOptionNames)) {
			foreach ($allCriticalCssOptionNames as $criticalCssOptionName) {
				$criticalCssOptionsArray[$criticalCssOptionName] = get_option($criticalCssOptionName);
			}
		}

		return $criticalCssOptionsArray;
	}
}
