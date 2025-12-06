<?php

/**
 * Handles plugin quick information page
 * 
 * @package DSFPS_Free_Product_Sample_Pro
 * @since   1.0.0
 */
// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
require_once plugin_dir_path( __FILE__ ) . 'header/plugin-header.php';
$version_label = '';
$version_label = __( 'Free Version', 'free-product-sample' );
$plugin_name = DSFPS_PLUGIN_NAME;
$plugin_version = DSFPS_PLUGIN_VERSION;
?>

<div class="fps-section-left">
    <div class="fps-main-table res-cl">
        <h2><?php 
esc_html_e( 'Quick info', 'free-product-sample' );
?></h2>
        <table class="form-table table-outer">
            <tbody>
                <tr>
                    <td class="fr-1"><?php 
esc_html_e( 'Product Type', 'free-product-sample' );
?></td>
                    <td class="fr-2"><?php 
esc_html_e( 'WooCommerce Plugin', 'free-product-sample' );
?></td>
                </tr>
                <tr>
                    <td class="fr-1"><?php 
esc_html_e( 'Product Name', 'free-product-sample' );
?></td>
                    <td class="fr-2"><?php 
esc_html_e( $plugin_name, 'free-product-sample' );
?></td>
                </tr>
                <tr>
                    <td class="fr-1"><?php 
esc_html_e( 'Installed Version', 'free-product-sample' );
?></td>
                    <td class="fr-2"><?php 
esc_html_e( $version_label, 'free-product-sample' );
?> <?php 
esc_html_e( $plugin_version, 'free-product-sample' );
?></td>
                </tr>
                <tr>
                    <td class="fr-1"><?php 
esc_html_e( 'License & Terms of use', 'free-product-sample' );
?></td>
                    <td class="fr-2"><a target="_blank"  href="<?php 
echo esc_url( 'www.thedotstore.com/terms-and-conditions' );
?>"><?php 
esc_html_e( 'Click here', 'free-product-sample' );
?></a><?php 
esc_html_e( ' to view license and terms of use.', 'free-product-sample' );
?></td>
                </tr>
                <tr>
                    <td class="fr-1"><?php 
esc_html_e( 'Help & Support', 'free-product-sample' );
?></td>
                    <td class="fr-2">
                        <ul>
                            <li><a href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'fps-get-started',
), admin_url( 'admin.php' ) ) );
?>"><?php 
esc_html_e( 'Quick Start', 'free-product-sample' );
?></a></li>
                            <li><a target="_blank" href="<?php 
echo esc_url( 'https://docs.thedotstore.com/collection/470-product-sample' );
?>"><?php 
esc_html_e( 'Guide Documentation', 'free-product-sample' );
?></a></li>
                            <li><a target="_blank" href="<?php 
echo esc_url( 'www.thedotstore.com/support' );
?>"><?php 
esc_html_e( 'Support Forum', 'free-product-sample' );
?></a></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td class="fr-1"><?php 
esc_html_e( 'Localization', 'free-product-sample' );
?></td>
                    <td class="fr-2"><?php 
esc_html_e( 'German, French, Polish, Spanish', 'free-product-sample' );
?></td>
                </tr>

            </tbody>
        </table>
    </div>
</div>
</div>
</div>
</div>
