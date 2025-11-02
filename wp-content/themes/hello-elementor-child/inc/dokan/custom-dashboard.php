<?php
// Customize main dashboard menu
add_filter('dokan_get_dashboard_nav', 'simplify_dokan_menu_icons');
function simplify_dokan_menu_icons($menus) {
    // Add custom icons
    $menus['dashboard']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/dashboard.svg" alt="Dashboard" class="dokan-custom-icon" style="width:20px; height:20px;">';
    $menus['products']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/products.svg" alt="Products" class="dokan-custom-icon" style="width:20px; height:20px;">';
    $menus['orders']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/orders.svg" alt="Orders" class="dokan-custom-icon" style="width:20px; height:20px;">';
    $menus['withdraw']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/withdraw.svg" alt="Withdraw" class="dokan-custom-icon" style="width:20px; height:20px;">';
    $menus['settings']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/settings.svg" alt="Settings" class="dokan-custom-icon" style="width:20px; height:20px;">';

    return $menus;
}

// Customize settings submenu
add_filter('dokan_get_dashboard_settings_nav', 'customize_settings_submenu_icons');
function customize_settings_submenu_icons($settings_sub) {
    // Add custom icons
    $settings_sub['store']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/settings-store.svg" alt="Store" class="dokan-custom-icon" style="width:20px; height:20px;">';
    $settings_sub['payment']['icon'] = '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/settings-payment.svg" alt="Payment" class="dokan-custom-icon" style="width:20px; height:20px;">';
    
    return $settings_sub;
}

// Add Profile and Logout as separate menu items
add_filter('dokan_get_dashboard_nav', 'add_custom_menu_items');
function add_custom_menu_items($menus) {
    // Add custom menu items
    $menus['profile'] = [
        'title'      => __('Profile', 'dokan-lite'),
        'icon'       => '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/user.svg" alt="Profile" class="dokan-custom-icon" style="width:20px;height:20px;">',
        'url'        => dokan_get_navigation_url('edit-account'),
        'pos'        => 210,
        'permission' => 'dokan_view_overview_menu'
    ];
    
    $menus['logout'] = [
        'title'      => __('Log Out', 'dokan-lite'),
        'icon'       => '<img src="' . get_stylesheet_directory_uri() . '/assets/img/icons/logout.svg" alt="Logout" class="dokan-custom-icon" style="width:20px;height:20px;">',
        'url'        => wp_logout_url(home_url()),
        'pos'        => 220,
        'permission' => 'dokan_view_overview_menu'
    ];
    
    return $menus;
}

// Remove standard shared links
add_filter('dokan_dashboard_nav_common_link', '__return_empty_string');