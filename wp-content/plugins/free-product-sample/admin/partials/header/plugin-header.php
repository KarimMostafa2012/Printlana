<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
global $fps_fs;
$version_label = '';
$plugin_slug = '';
$version_label = __( 'Free', 'free-product-sample' );
$plugin_slug = 'basic_product_sample';
$plugin_name = 'Product Sample';
$plugin_version = 'v' . DSFPS_PLUGIN_VERSION;
$current_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$fps_settings_page = ( isset( $current_page ) && 'fps-sample-settings' === $current_page ? 'active' : '' );
$fps_getting_started = ( isset( $current_page ) && 'fps-get-started' === $current_page ? 'active' : '' );
$fps_account_page = ( isset( $current_page ) && 'fps-sample-settings-account' === $current_page ? 'active' : '' );
$fps_free_dashboard = ( isset( $current_page ) && 'fps-upgrade-dashboard' === $current_page ? 'active' : '' );
$admin_object = new DSFPS_Free_Product_Sample_Pro_Admin('', '');
?>
<div id="dotsstoremain">
    <div class="all-pad">
        <?php 
$admin_object->dsfps_get_promotional_bar( $plugin_slug );
?>
        <header class="dots-header">
            <div class="dots-plugin-details">
                <div class="dots-header-left">
                    <div class="dots-logo-main">
                        <img src="<?php 
echo esc_url( DSFPS_PLUGIN_URL . 'admin/images/icon-woocommerce-free-product-samples.png' );
?>">
                    </div>
                    <div class="plugin-name">
                        <div class="title"><?php 
esc_html_e( $plugin_name, 'free-product-sample' );
?></div>
                    </div>
                    <span class="version-label <?php 
echo esc_attr( $plugin_slug );
?>"><?php 
esc_html_e( $version_label, 'free-product-sample' );
?></span>
                    <span class="version-number"><?php 
echo esc_html__( $plugin_version, 'free-product-sample' );
?></span>
                </div>
                <div class="dots-header-right">
                    <div class="button-dots">
                        <a target="_blank" href="<?php 
echo esc_url( 'http://www.thedotstore.com/support/?utm_source=plugin_header_menu_link&utm_medium=header_menu&utm_campaign=plugin&utm_id=menu_link_product_sample' );
?>"><?php 
esc_html_e( 'Support', 'free-product-sample' );
?></a>
                    </div>
                    <div class="button-dots">
                        <a target="_blank" href="<?php 
echo esc_url( 'https://www.thedotstore.com/feature-requests/?utm_source=plugin_header_menu_link&utm_medium=header_menu&utm_campaign=plugin&utm_id=menu_link_product_sample' );
?>"><?php 
esc_html_e( 'Suggest', 'free-product-sample' );
?></a>
                    </div>
                    <div class="button-dots <?php 
echo ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ? '' : 'last-link-button' );
?>">
                        <a target="_blank" href="<?php 
echo esc_url( 'https://docs.thedotstore.com/collection/470-product-sample' );
?>"><?php 
esc_html_e( 'Help', 'free-product-sample' );
?></a>
                    </div>
                    <div class="button-dots">
                        <?php 
?>
                            <a class="dots-upgrade-btn" target="_blank" href="javascript:void(0);"><?php 
esc_html_e( 'Upgrade Now', 'free-product-sample' );
?></a>
                            <?php 
?>
                    </div>
                </div>
            </div>
            <div class="dots-bottom-menu-main">
                <div class="dots-menu-main">
                    <nav>
                        <ul>
                            <li>
                                <a class="dotstore_plugin <?php 
echo esc_attr( $fps_settings_page );
?>" href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'fps-sample-settings',
), admin_url( 'admin.php' ) ) );
?>"><?php 
esc_html_e( 'Manage Sample', 'free-product-sample' );
?></a>
                            </li>
                            <?php 
if ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ) {
    ?>
                                <li>
                                    <a class="dotstore_plugin <?php 
    echo esc_attr( $fps_account_page );
    ?>" href="<?php 
    echo esc_url( $fps_fs->get_account_url() );
    ?>"><?php 
    esc_html_e( 'Licenses', 'free-product-sample' );
    ?></a>
                                </li>
                                <?php 
}
?>
                            <?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?>
                                <li>
                                    <a class="dotstore_plugin dots_get_premium <?php 
    echo esc_attr( $fps_free_dashboard );
    ?>" href="<?php 
    echo esc_url( add_query_arg( array(
        'page' => 'fps-upgrade-dashboard',
    ), admin_url( 'admin.php' ) ) );
    ?>"><?php 
    esc_html_e( 'Get Premium', 'free-product-sample' );
    ?></a>
                                </li>
                                <?php 
}
?>
                        </ul>
                    </nav>
                </div>
                <div class="dots-getting-started">
                    <a href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'fps-get-started',
), admin_url( 'admin.php' ) ) );
?>" class="<?php 
echo esc_attr( $fps_getting_started );
?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z" fill="#a0a0a0"></path></svg></a>
                </div>
            </div>
        </header>
        <div class="dots-settings-inner-main">