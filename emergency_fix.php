<?php
/**
 * Emergency script to deactivate WPML
 */
require_once('wp-load.php');
include_once('wp-admin/includes/plugin.php');

$plugin = 'sitepress-multilingual-cms/sitepress.php';
if (is_plugin_active($plugin)) {
    deactivate_plugins($plugin);
    echo "WPML deactivated successfully.";
} else {
    echo "WPML is not active or not found.";
}
