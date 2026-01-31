<?php
/**
 * Plugin Name: Fix Analytics Overview Hooks Error
 * Description: Redirects broken Analytics Overview to Revenue page
 * Version: 1.0.2
 */

defined('ABSPATH') || exit;

/**
 * Redirect from broken Overview page to Revenue analytics
 */
add_action('template_redirect', 'redirect_broken_analytics_overview', 1);

function redirect_broken_analytics_overview() {
    // Check if we're on the problematic Overview page
    if (isset($_GET['path']) &&
        ($_GET['path'] === '/analytics/Overview' || $_GET['path'] === '%2Fanalytics%2FOverview')) {

        // Redirect to Revenue analytics instead
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $new_url = str_replace(
            array('/analytics/Overview', '%2Fanalytics%2FOverview'),
            array('/analytics/revenue', '%2Fanalytics%2Frevenue'),
            $current_url
        );

        wp_safe_redirect($new_url, 302);
        exit;
    }
}

/**
 * Update dashboard menu to point to revenue instead of overview
 */
add_filter('dokan_get_dashboard_nav', 'fix_dashboard_analytics_link', 999);

function fix_dashboard_analytics_link($menus) {
    if (isset($menus['dashboard']['url'])) {
        $menus['dashboard']['url'] = str_replace(
            array('/analytics/Overview', '%2Fanalytics%2FOverview'),
            array('/analytics/revenue', '%2Fanalytics%2Frevenue'),
            $menus['dashboard']['url']
        );
    }

    return $menus;
}
