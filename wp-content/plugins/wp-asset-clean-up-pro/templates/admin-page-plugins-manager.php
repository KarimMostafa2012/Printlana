<?php
/*
 * No direct access to this file
 */

use WpAssetCleanUp\AssetsManager;
use WpAssetCleanUp\Misc;
use WpAssetCleanUpPro\PluginsManagerPro;

if (! isset($data)) {
	exit;
}

include_once '_top-area.php';

do_action('wpacu_admin_notices');

if ( ! AssetsManager::instance()->currentUserCanViewAssetsList() ) {
	?>
    <div class="wpacu-error" style="padding: 10px;">
		<?php echo sprintf(esc_html__('Only the administrators listed here can manage plugins: %s"Settings" &#10141; "Plugin Usage Preferences" &#10141; "Allow managing assets to:"%s. If you believe you should have access to managing plugins, you can add yourself to that list.', 'wp-asset-clean-up'), '<a target="_blank" href="'.esc_url(admin_url('admin.php?page=wpassetcleanup_settings&wpacu_selected_tab_area=wpacu-setting-plugin-usage-settings')).'">', '</a>'); ?></div>
	<?php
	return;
}

// [wpacu_pro]
if ($data['mu_file_missing']) {
    ?>
    <div style="border-radius: 5px; line-height: 20px; background: white; padding: 8px; margin-bottom: 16px; width: 95%; border-left: 4px solid #CC0000; border-top: 1px solid #e7e7e7; border-right: 1px solid #e7e7e7; border-bottom: 1px solid #e7e7e7;">
        The MU plugin file that is filtering the plugins' rules wasn't copied successfully to <code><?php echo esc_html($data['mu_file_rel_path']); ?></code>. Please make sure the MU plugin directory is writeable or copy the file manually from <code>/<?php echo Misc::getPluginsDir(); ?>/wp-asset-clean-up-pro/pro/mu-plugins/to-copy/wpacu-plugins-filter.php</code> to <code><?php echo esc_html($data['mu_file_rel_path']); ?></code>.
    </div>
    <?php
    return;
}
// [/wpacu_pro]
?>
<div class="wpacu-sub-page-tabs-wrap"> <!-- Sub-tabs wrap -->
    <!-- Sub-nav menu -->
    <label id="wpacu-sub-page-nav-plugins-manager-front"
           class="wpacu-sub-page-nav-label <?php if ( ! $data['plugins_manager_front_enable'] ) { ?>wpacu-disabled<?php } ?> <?php if ( $data['wpacu_sub_page'] === 'manage_plugins_front' ) { ?>wpacu-selected<?php } ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpassetcleanup_plugins_manager&wpacu_sub_page=manage_plugins_front')); ?>"><span class="dashicons dashicons-admin-home"></span> IN FRONTEND VIEW (your visitors)</a>
    </label>
    <label id="wpacu-sub-page-nav-plugins-manager-dash"
           <?php if ( defined('WPACU_ALLOW_DASH_PLUGIN_FILTER') && WPACU_ALLOW_DASH_PLUGIN_FILTER ) { ?>data-wpacu-activated-via-code="1"<?php } ?>
           class="wpacu-sub-page-nav-label <?php if ( ! $data['plugins_manager_dash_enable'] ) { ?>wpacu-disabled<?php } ?> <?php if ( $data['wpacu_sub_page'] === 'manage_plugins_dash' ) { ?>wpacu-selected<?php } ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpassetcleanup_plugins_manager&wpacu_sub_page=manage_plugins_dash')); ?>"><span class="dashicons dashicons-dashboard"></span> IN THE DASHBOARD /wp-admin/</a>
    </label>
    <!-- /Sub-nav menu -->
</div> <!-- /Sub-tabs wrap -->

<?php if ($data['wpacu_sub_page'] === 'manage_plugins_front') { ?>
    <div id="wpacu-plugins-manage-front-notice-top">
        <p style="margin-top: 0;"><strong>Remember:</strong> Please be careful when using this feature as it would not only unload all the CSS/JS that is loading from a plugin, but everything else (e.g. its backend PHP code, HTML output printed via <code>wp_head()</code> or <code>wp_footer()</code> action hooks, any cookies that are set, .etc). It would be like the plugin is deactivated for the pages where it's chosen to be unloaded. Consider enabling "Test Mode" in plugin's "Settings" if you're unsure about anything. All the rules set below are applied in the front-end view only. They are not taking effect within the Dashboard (the function <code style="font-size: inherit;">is_admin()</code> is used to verify that) to make sure nothing will get broken while you're configuring any plugins' settings. <a style="text-decoration: none; color: #004567;" target="_blank" href="https://www.assetcleanup.com/docs/?p=372"><span class="dashicons dashicons-info"></span>&nbsp;Read more</a></p>
        <p style="margin-bottom: 0;">If you wish to completely stop using a plugin in both admin/frontend pages, the most effective way would be to deactivate it from the "Plugins" -&gt; "Installed Plugins" area.</p>
    </div>
<?php
    include_once __DIR__.'/_admin-page-plugins-manager/_front.php';
} elseif ($data['wpacu_sub_page'] === 'manage_plugins_dash') {
    // [wpacu_pro]
	$wpacuShowPluginManagerForDash = true;

    $wpacuIsDashConstantSetToTrue   = defined('WPACU_ALLOW_DASH_PLUGIN_FILTER') && WPACU_ALLOW_DASH_PLUGIN_FILTER;
    $wpacuIsDashConstantNotInEffect = defined('WPACU_ALLOW_DASH_PLUGIN_FILTER_NOT_IN_EFFECT') && WPACU_ALLOW_DASH_PLUGIN_FILTER_NOT_IN_EFFECT;

    if ($wpacuIsDashConstantSetToTrue && $wpacuIsDashConstantNotInEffect) {
	    $wpacuShowPluginManagerForDash = false;
        ?>
        <div style="border-radius: 5px; line-height: 20px; background: white; padding: 8px; margin-bottom: 16px; width: 95%; border-left: 4px solid #cc0000; border-top: 1px solid #e7e7e7; border-right: 1px solid #e7e7e7; border-bottom: 1px solid #e7e7e7;">
            <p style="margin-top: 0;"><strong><span class="dashicons dashicons-warning" style="color: #cc0000;"></span> It looks like the required change was not done correctly within <em>wp-config.php</em></strong>. <span class="dashicons dashicons-info"></span> <a target="_blank" href="https://www.assetcleanup.com/docs/?p=1128">Read more</a>. The snippet below, should be added, <strong>BEFORE</strong> the line with the following text: <em style="color: grey;">/** Sets up WordPress vars and included files. */</em>:</p>
            <p style="margin-top: 0;"><code>define('WPACU_ALLOW_DASH_PLUGIN_FILTER', true);</code></p>
            <p style="margin-top: 0;">This is how it should look like:</p>
            <div style="margin-top: 0;">
            <pre style="margin: 0;"><code>define('WPACU_ALLOW_DASH_PLUGIN_FILTER', true);

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');</code>
                </pre>
            </div>
            <p style="margin: 0;">Once the snippet is added correctly, this notice will disappear and the list of plugins will show up for management.</p>
        </div>
        <?php
    } elseif (! $wpacuIsDashConstantSetToTrue) {
	    $wpacuShowPluginManagerForDash = false;
    ?>
        <div style="border-radius: 5px; line-height: 20px; background: white; padding: 8px; margin-bottom: 16px; width: 95%; border-left: 4px solid #cc0000; border-top: 1px solid #e7e7e7; border-right: 1px solid #e7e7e7; border-bottom: 1px solid #e7e7e7;">
            <p style="margin-top: 0;">Due to the nature of this feature which requires extra care when unloading plugins within the Dashboard, a code snippet (PHP constant) has to be enabled in <a target="_blank" href="https://wordpress.org/support/article/editing-wp-config-php/">the file <strong>wp-config.php</strong></a>.</p>

            <p style="margin-top: 0;">
                <?php if (defined('WPACU_ALLOW_DASH_PLUGIN_FILTER') && WPACU_ALLOW_DASH_PLUGIN_FILTER === false) { ?>
                    Currently, the constant is set to <strong>false</strong>.
                <?php } elseif ( ! defined('WPACU_ALLOW_DASH_PLUGIN_FILTER') ) { ?>
                    Currently, the constant is not set at all.
                <?php } ?>

                The snippet below, should be added, ideally before the line with the following text: <em style="color: grey;">/** Sets up WordPress vars and included files. */</em>:
            </p>

            <p style="margin-top: 0;"><code>define('WPACU_ALLOW_DASH_PLUGIN_FILTER', true);</code></p>
            <p style="margin-top: 0;">This is how it should look like:</p>
            <div style="margin-top: 0;">
            <pre style="margin: 0;"><code>define('WPACU_ALLOW_DASH_PLUGIN_FILTER', true);

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');</code>
                </pre>
            </div>
            <p style="margin: 0;">Once the snippet is added correctly, this notice will disappear and the list of plugins will show up for management.</p>
        </div>
    <?php
    }
	// [/wpacu_pro]
    ?>
    <div id="wpacu-plugins-manage-dash-notice-top">
        <p style="margin-top: 0;"><strong>Remember:</strong> Using this feature is only recommended for advanced users (e.g. developers/admins that know very well their website and the consequences of having plugins unloaded for certain pages) &amp; who really need it. A set rule would not only unload all the CSS/JS that is loading from a plugin, but everything else (e.g. its backend PHP code, HTML output printed via <code>admin_head()</code> or <code>admin_footer()</code> action hooks, any cookies that are set, .etc).</p>
        <p style="margin-top: 0;">Reasons for using this feature include: some admin pages are very slow, you want to avoid a conflict between two plugins, etc. It would be like the plugin is deactivated within the Dashboard for the pages where it's chosen to be unloaded. The function <code style="font-size: inherit;">is_admin()</code> is used to perform the verification to determine if the user is inside a Dashboard page. If you make a mistake and set a rule that doesn't allow you to access a page anymore, you can cancel it by appending the following query string to the URL: <code>&amp;wpacu_no_dash_plugin_unload</code>, thus allowing you to change/remove the rule in this management page. <a style="text-decoration: none; color: #004567;" target="_blank" href="https://www.assetcleanup.com/docs/?p=1128"><span class="dashicons dashicons-info"></span>&nbsp;Read more</a></p>
        <p style="margin-bottom: 0;">If you wish to completely stop using a plugin in both admin/frontend pages, the most effective way would be to deactivate it from the "Plugins" -&gt; "Installed Plugins" area.</p>
    </div>
<?php
    // [wpacu_pro]
    if (! $wpacuShowPluginManagerForDash) {
        return; // stop here as the option is not enabled
    }
    // [/wpacu_pro]

	include_once __DIR__.'/_admin-page-plugins-manager/_dash.php';
}
