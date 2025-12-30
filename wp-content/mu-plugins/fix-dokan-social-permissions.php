<?php
/**
 * Plugin Name: Fix Dokan Social Permissions
 * Description: Fixes permission issues preventing vendors and admins from accessing the Dokan Social settings page
 * Version: 1.0.0
 * Author: Karim Mostafa
 */

if (!defined('ABSPATH')) {
    exit;
}

// Immediate log to confirm plugin loads
error_log('[Dokan Social Fix] ===== PLUGIN LOADED =====');

// Add HTML comment to confirm loading
add_action('wp_head', function() {
    echo "\n<!-- DOKAN SOCIAL FIX PLUGIN ACTIVE -->\n";
}, 1);

/**
 * Grant access to Dokan Social settings page for vendors and admins
 */
add_filter('dokan_get_dashboard_settings_nav', 'pl_enable_social_settings_for_all', 10, 1);
function pl_enable_social_settings_for_all($settings_nav)
{
    // Make sure social settings are visible
    if (isset($settings_nav['social'])) {
        // Remove any permission restrictions
        $settings_nav['social']['permission'] = true;
        error_log('[Dokan Social] Social settings enabled in nav');
    }

    return $settings_nav;
}

/**
 * Override Dokan's permission check for social settings page
 */
add_filter('dokan_query_var_filter', 'pl_allow_social_settings_access', 99, 1);
function pl_allow_social_settings_access($query_vars)
{
    // Check if we're trying to access social settings
    if (isset($query_vars['settings']) && $query_vars['settings'] === 'social') {
        // Log the access attempt
        error_log('[Dokan Social] Attempting to access social settings page');

        // Check user capabilities
        $current_user = wp_get_current_user();
        error_log('[Dokan Social] User ID: ' . $current_user->ID . ', Roles: ' . implode(', ', $current_user->roles));
    }

    return $query_vars;
}

/**
 * Force enable social settings capability for vendors and admins
 */
add_action('init', 'pl_grant_social_settings_capability');
function pl_grant_social_settings_capability()
{
    // Grant to administrators
    if ($admin = get_role('administrator')) {
        $admin->add_cap('dokan_view_store_social_setting');
        $admin->add_cap('dokan_view_social_settings');
    }

    // Grant to sellers/vendors
    if ($seller = get_role('seller')) {
        $seller->add_cap('dokan_view_store_social_setting');
        $seller->add_cap('dokan_view_social_settings');
    }

    // Grant to dokan sellers
    if ($dokan_seller = get_role('dokan_seller')) {
        $dokan_seller->add_cap('dokan_view_store_social_setting');
        $dokan_seller->add_cap('dokan_view_social_settings');
    }

    // Grant to shop managers
    if ($manager = get_role('shop_manager')) {
        $manager->add_cap('dokan_view_store_social_setting');
        $manager->add_cap('dokan_view_social_settings');
    }
}

/**
 * Remove permission check from social settings page load
 */
add_filter('dokan_settings_load_social_content', '__return_true', 999);

/**
 * Override the permission check directly
 */
add_filter('user_has_cap', 'pl_force_social_settings_permission', 999, 4);
function pl_force_social_settings_permission($allcaps, $caps, $args, $user)
{
    // Only run on frontend and for logged in users
    if (is_admin() || !is_user_logged_in()) {
        return $allcaps;
    }

    // Check if we're on Dokan dashboard
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return $allcaps;
    }

    // Check if we're trying to access social settings
    if (isset($_GET['settings']) && $_GET['settings'] === 'social') {
        error_log('[Dokan Social] Forcing social settings permission for user ID: ' . $user->ID);

        // Grant all social-related capabilities
        $allcaps['dokan_view_store_social_setting'] = true;
        $allcaps['dokan_view_social_settings'] = true;
        $allcaps['dokan_edit_store_social_setting'] = true;
        $allcaps['dokan_edit_social_settings'] = true;
    }

    return $allcaps;
}

/**
 * Debug: Log what's happening when accessing social page
 */
add_action('template_redirect', 'pl_debug_social_access');
function pl_debug_social_access()
{
    // Only on Dokan dashboard
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    // Check if accessing social settings
    if (isset($_GET['settings']) && $_GET['settings'] === 'social') {
        $current_user = wp_get_current_user();

        error_log('[Dokan Social Debug] === ACCESSING SOCIAL SETTINGS ===');
        error_log('[Dokan Social Debug] User ID: ' . $current_user->ID);
        error_log('[Dokan Social Debug] User Roles: ' . implode(', ', $current_user->roles));
        error_log('[Dokan Social Debug] URL: ' . $_SERVER['REQUEST_URI']);
        error_log('[Dokan Social Debug] User Caps: ' . print_r($current_user->allcaps, true));

        // Check specific capabilities
        error_log('[Dokan Social Debug] Can view social: ' . (current_user_can('dokan_view_store_social_setting') ? 'YES' : 'NO'));
        error_log('[Dokan Social Debug] Can edit social: ' . (current_user_can('dokan_edit_store_social_setting') ? 'YES' : 'NO'));
        error_log('[Dokan Social Debug] Is seller: ' . (dokan_is_user_seller($current_user->ID) ? 'YES' : 'NO'));
    }
}

/**
 * Alternative: Completely bypass Dokan's social settings permission check
 */
add_action('dokan_load_settings_content', 'pl_force_load_social_content', 1);
function pl_force_load_social_content()
{
    // Only for social settings page
    if (!isset($_GET['settings']) || $_GET['settings'] !== 'social') {
        return;
    }

    error_log('[Dokan Social] Force loading social content');

    // Remove any permission filters that Dokan might have added
    remove_all_filters('dokan_settings_load_social_content');

    // Force the content to load
    add_filter('dokan_settings_load_social_content', '__return_true', 999);
}
