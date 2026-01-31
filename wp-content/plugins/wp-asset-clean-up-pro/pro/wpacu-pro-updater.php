<?php
// Exit if accessed directly
if (! defined('WPACU_PRO_DIR')) {
	exit;
}

// This is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define('WPACU_PRO_PLUGIN_STORE_URL', 'https://www.gabelivan.com');
define('WPACU_PRO_PLUGIN_STORE_LICENSE_ACTION_URL', WPACU_PRO_PLUGIN_STORE_URL . '/?wpacu_action_type=license');

// The name of your product. This should match the download name in EDD exactly
define('WPACU_PRO_PLUGIN_STORE_ITEM_NAME', 'Asset CleanUp Pro: Performance WordPress Plugin');

// The ID of the product from the store
define('WPACU_PRO_PLUGIN_STORE_ITEM_ID', 17193);

/**
 * Initialize the updater. Hooked into `init` to work with the
 * wp_version_check cron job, which allows auto-updates.
 * As of Sep 8, 2022, this has been properly tested via WP CLI
 * and the plugin can be updated there as well (not just through the Dashboard)
 */
function wpassetcleanup_pro_plugin_updater()
{
	// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
	$doingCron = defined( 'DOING_CRON' ) && DOING_CRON;
	if ( ! current_user_can( 'manage_options' ) && ! $doingCron ) {
		return;
	}

	// retrieve the license key from the DB
	$licenseKey = trim(get_option( WPACU_PLUGIN_ID . '_pro_license_key'));

	if ( ! $licenseKey ) {
		// Without a license, no notice of a possible new version will be shown
		return;
	}

	// Any plugin update?
	new \WpAssetCleanUpPro\PluginUpdaterPro(WPACU_PRO_PLUGIN_STORE_LICENSE_ACTION_URL, WPACU_PLUGIN_FILE, array(
			'version' 	=> WPACU_PRO_PLUGIN_VERSION,         // current version number
			'license' 	=> $licenseKey, 		             // license key
			'item_id'   => WPACU_PRO_PLUGIN_STORE_ITEM_ID,   // item ID from the store
			'author' 	=> 'Gabriel Livan',                  // author of this plugin
			'url'       => home_url(),
			'beta'		=> false
		)
	);
}

add_action('init', 'wpassetcleanup_pro_plugin_updater', 0);
