<?php
/**
 * Handles plugin about page
 * 
 * @package DSFPS_Free_Product_Sample_Pro
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_dir_path( __FILE__ ) . 'header/plugin-header.php' );
?>	
<div class="fps-section-left">
	<div class="thedotstore-main-table res-cl">
		<div class="dots-getting-started-main">
            <div class="getting-started-content">
                <span><?php esc_html_e( 'How to Get Started', 'free-product-sample' ); ?></span>
                <h3><?php esc_html_e( 'Welcome to Product Sample Plugin', 'free-product-sample' ); ?></h3>
                <p><?php esc_html_e( 'Thank you for choosing our top-rated WooCommerce Product Sample plugin. Our user-friendly interface makes enabling samples for any products simple. Sample, sell, succeed.', 'free-product-sample' ); ?></p>
                <p>
                    <?php 
                    echo sprintf(
                        esc_html__('To help you get started, watch the quick tour video on the right. For more help, explore our help documents or visit our %s for detailed video tutorials.', 'free-product-sample'),
                        '<a href="' . esc_url('https://www.youtube.com/@DotStore16?sub_confirmation=1') . '" target="_blank">' . esc_html__('YouTube channel', 'free-product-sample') . '</a>',
                    );
                    ?>
                </p>
                <div class="getting-started-actions">
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'fps-sample-settings'), admin_url('admin.php'))); ?>" class="quick-start"><?php esc_html_e( 'Manage Product Sample', 'free-product-sample' ); ?><span class="dashicons dashicons-arrow-right-alt"></span></a>
                    <a href="https://docs.thedotstore.com/article/505-getting-started" target="_blank" class="setup-guide"><span class="dashicons dashicons-book-alt"></span><?php esc_html_e( 'Read the Setup Guide', 'free-product-sample' ); ?></a>
                </div>
            </div>
            <div class="getting-started-video">
                <iframe width="960" height="600" src="<?php echo esc_url('https://www.youtube.com/embed/d5BEqFvWXXA'); ?>" title="<?php esc_attr_e( 'Plugin Tour', 'free-product-sample' ); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
	</div>
</div>
</div>
</div>
</div>
<?php
