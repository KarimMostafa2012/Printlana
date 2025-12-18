<?php
namespace WpAssetCleanUpPro;

use WpAssetCleanUp\Menu;
use WpAssetCleanUp\Settings;

/**
 * Class MainAdminPro
 *
 * This class has functions that are only for the admin's concern
 *
 * @package WpAssetCleanUp
 */
class MainAdminPro
{
    /**
     * @var MainAdminPro|null
     */
    private static $singleton;

    /**
     * @return null|MainAdminPro
     */
    public static function instance()
    {
        if (self::$singleton === null) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * @return void
     */
    public function init()
    {
        add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_update_plugin_setting', array( $this, 'ajaxUpdatePluginSetting' ) );
    }

    /**
     * @return void
     */
    public function ajaxUpdatePluginSetting()
    {
        if ( ! isset($_POST['wpacu_setting_key']) || ! isset($_POST['wpacu_setting_value']) || ! isset($_POST['action']) ) {
            echo 'Error: The essential elements are missing. Location: '.__METHOD__;
            exit();
        }

        if ( ! isset($_POST['wpacu_nonce']) ) {
            echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
            exit();
        }

        if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_update_plugin_setting_nonce') ) {
            echo 'Error: The security check has failed. Location: '.__METHOD__;
            exit();
        }

        if ( ! Menu::userCanManageAssets() ) {
            echo 'Error: User does not have permission to perform this action. Location: '.__METHOD__;
            exit();
        }

        $settingKey   = sanitize_text_field($_POST['wpacu_setting_key']);
        $settingValue = sanitize_text_field($_POST['wpacu_setting_value']);

        $settingsClass = new Settings();
        $settingsClass->updateOption($settingKey, $settingValue);

        echo 'DONE';
        exit();
    }
}
