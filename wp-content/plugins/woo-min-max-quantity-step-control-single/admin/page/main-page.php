<?php



$saved_data = WC_MMQ::getOptions();

if( ! empty( $_POST ) && ( isset( $_POST['configure_submit'] ) || isset( $_POST['reset_button'] ) ) ){
    $submit_form = wcmmq_form_submit();
    if( is_array( $submit_form ) && ! empty( $submit_form ) ){
        $saved_data = $submit_form;
    }

}




//TOPBAR INCLUDE HERE
include $this->topbar_file;
$is_pro = $this->is_pro;

?>





<div class="wrap wcmmq_wrap wcmmq-content">

    <h1 class="wp-heading "></h1>
    <div class="fieldwrap">
        <?php
        $randN = wp_rand(1,2);
        $wcmmq_recomm = get_option('wcmmq_recommsss', 1);
        $wcmmq_recomm++;
        update_option('wcmmq_recommsss', $wcmmq_recomm);
        if($wcmmq_recomm <= 30 && $randN == 1){
        ?>
        <div id="wcmmq-recomendation-area" class="wcmmq-section-panel">
            <?php do_action( 'wcmmq_plugin_recommend_top' ); ?>
        </div>
        <?php } ?>
        
        <form class="" action="" method="POST" id="wcmmq-main-configuration-form">
           <?php wp_nonce_field( WC_MMQ_PLUGIN_BASE_FOLDER, 'nonce' ) ?>
            <div class="wcmmq-configure-form-header">
                <div class="wcmmq-configure-tab-wrapper wcmmq-section-panel no-background"></div>
                <input type="text" id="wcmmq-setting-search-input" class="wcmmq-setting-search-input" placeholder="🔍 Search settings by label/value/anything">
            </div>
            
        
            <div class="wcmmq-section-panel universal-settings" id="wcmmq-universal-settings">
                <?php include 'main-page/universal-settings.php'; ?>
            </div>
        
            <?php 
            
            /**
             * @Hook Action: wcmmq_form_panel
             * To add new panel in Forms
             * @since 1.8.6
             */
            do_action( 'wcmmq_form_panel', $saved_data );
            ?>
            

        
            <div class="wcmmq-section-panel supported-terms" id="wcmmq-supported-terms">
                <?php include 'main-page/supported-terms.php'; ?>
                <?php include 'main-page/edit-terms.php'; ?>
            
            </div>
            
            
            
            
            <?php 
            
            /**
             * @Hook Action: wcmmq_form_panel
             * To add new panel in Forms
             * @since 1.8.6
             */
            do_action( 'wcmmq_form_panel_before_message', $saved_data );

            $fields_arr = [
                'msg_min_limit' => [
                    'title' => __('Minimum Quantity Validation Message','woo-min-max-quantity-step-control-single' ),
                    'desc'  => __('Available shortcode [min_quantity],[max_quantity],[product_name],[step_quantity],[inputed_quantity],[variation_name]','woo-min-max-quantity-step-control-single' ),
                ],
                
                'msg_max_limit' => [
                    'title' => __('Maximum Quantity Validation Message','woo-min-max-quantity-step-control-single' ),
                    'desc'  => __('Available shortcode [current_quantity][min_quantity],[max_quantity],[product_name],[step_quantity],[inputed_quantity],[variation_name]','woo-min-max-quantity-step-control-single' ),
                ],
                'msg_max_limit_with_already' => [
                    'title' => __('Already Quantity Validation Message','woo-min-max-quantity-step-control-single' ),
                    'desc'  => __('Available shortcode [current_quantity][min_quantity],[max_quantity],[product_name],[step_quantity],[variation_name]','woo-min-max-quantity-step-control-single' ),
                ],
                'min_qty_msg_in_loop' => [
                    'title' => __('Minimum Quantity message for shop page','woo-min-max-quantity-step-control-single' ),
                    'desc'  => __('Available shortcode [min_quantity],[max_quantity],[product_name],[step_quantity],[variation_name]','woo-min-max-quantity-step-control-single' ),
                ],
                'step_error_valiation' => [
                    'title' => __('Step validation error message','woo-min-max-quantity-step-control-single' ),
                    'desc'  => __('Available shortcode [should_min],[should_next],[product_name],[variation_name],[quantity],[min_quantity],[step_quantity]','woo-min-max-quantity-step-control-single' ),
                ],
        
            ];
        
            wcmmq_message_field_generator($fields_arr, $saved_data);
            
            /**
             * @Hook Action: wcmmq_form_panel
             * To add new panel in Forms
             * @since 1.8.6
             */
            do_action( 'wcmmq_form_panel_bottom', $saved_data );
            ?>
            

            <?php 
            if( ! $this->is_pro ){
                include 'main-page/premium-placeholder.php';
            }
            ?>

            <div class="wcmmq-section-panel no-background wcmmq-full-form-submit-wrapper">
                
                <button name="configure_submit" type="submit"
                    class="wcmmq-btn wcmmq-has-icon configure_submit">
                    <span><i class="wcmmq_icon-floppy"></i></span>
                    <strong class="form-submit-text">
                    <?php echo esc_html__('Save Change','woo-min-max-quantity-step-control-single');?>
                    </strong>
                </button>
                <button name="reset_button" 
                    class="wcmmq-btn reset wcmmq-has-icon reset_button"
                    onclick="return confirm('If you continue with this action, you will reset all options in this page.\nAre you sure?');">
                    <span><i class="wcmmq_icon-arrows-cw "></i></span>
                    <?php echo esc_html__( 'Reset Settings', 'woo-min-max-quantity-step-control-single' ); ?>
                </button>
                
            </div>

            

                    
        </form>
        <div class="wcmmq-section-panel supported-terms wcmmq-recomendation-area" id="wcmmq-recomendation-area">
            <table class="wcmmq-table universal-setting">
                <thead>
                    <tr>
                        <th class="wcmmq-inside">
                            <div class="wcmmq-table-header-inside">
                                <h3><?php echo esc_html__('Recommendation Area', 'woo-min-max-quantity-step-control-single'); ?> <small class="wcmmq-small-title">To increase Sale</small></h3>
                            </div>

                        </th>
                        <th>
                            <div class="wcmmq-table-header-right-side"></div>
                        </th>
                    </tr>
                </thead>

                <tbody>
                <tr>
                    <td>
                        <div class="wcmmq-form-control">
                            <div class="form-label col-lg-12">
                            <?php do_action( 'wcmmq_plugin_recommend_here' ); ?>
                            </div>
                            <div class="form-label col-lg-12">
                                <?php wcmmq_submit_issue_link(); ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="wqpmb-form-info">
                            
                            <?php wcmmq_social_links(); ?>
                            <p>Highly Recommeded these plugin. Which will help you to increase your WooCommerce sale.</p>
                        </div> 
                    </td>
                </tr>
                </tbody>
            </table>

        </div> <!--/.wcmmq-recomendation-area -->
    </div>
</div> 