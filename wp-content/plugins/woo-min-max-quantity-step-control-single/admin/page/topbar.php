<?php
$min_max_img = WC_MMQ_BASE_URL . 'assets/images/brand/social/min-max.png';

//If only found method for WPML
if(method_exists($this, 'redirect_wpml')){
    // $this->redirect_wpml();    
}

/**
 * This following part actually
 * for our both version
 * 
 * ekta en vato nd arekta amader mull site laisens er jonno.
 * code vato hole to WC_MMQ_PRO::$direct er value null thakbe
 * tokhon amra License menu ta ekhane dekhabo na.
 * 
 * ********************
 * arekta prosno jagte pare.
 * ei lai sense er jonno to ekta property achei amade Page_Loader class e
 * tarpor o keno amra ekhane notun kore check korchi.
 * 
 * asoel ei tobbar.php file ta onno class diye o , jemon \WC_MMQ_PRO\Admin\License\Init class eo 
 * load kora hoyeche. tokhon to $this->license pabe na.
 * tai notun kore check korechi.
 */
$license_direct = true;
if($this->is_pro && class_exists('\WC_MMQ_PRO')){
    $license_direct = \WC_MMQ_PRO::$direct;
}

$topbar_sub_title = __( 'Manage and Settings', 'woo-min-max-quantity-step-control-single' );
if( isset( $this->topbar_sub_title ) && ! empty( $this->topbar_sub_title ) ){
    $topbar_sub_title = $this->topbar_sub_title;
}
?>
<div class="wcmmq-header wcmmq-clearfix">
    <div class="container-flued">
        <div class="col-lg-7">
            <div class="wcmmq-logo-wrapper-area">
                <div class="wcmmq-logo-area">
                    <img src="<?php echo esc_url( $min_max_img ); ?>" class="wcmmq-brand-logo">
                </div>
                <div class="wcmmq-main-title">
                    <h2 class="wcmmq-ntitle"><?php esc_html__("Min Max Control", 'woo-min-max-quantity-step-control-single');?></h2>
                </div>
                
                <div class="wcmmq-main-title wcmmq-main-title-secondary">
                    <h2 class="wcmmq-ntitle"><?php echo esc_html( $topbar_sub_title );?></h2>
                </div>

            </div>
        </div>
        <div class="col-lg-5">
            <div class="header-button-wrapper">
                <?php if( ! wcmmq_is_premium_installed() && ! wcmmq_is_old_dir() ){ ?>
                    <a class="wcmmq-btn reverse wcmmq-btn-tiny wcmmq-get-premium" 
                        href="https://checkout.freemius.com/plugin/21522/" 
                        target="_blank">
                        <i class="wcmmq_icon-spin5 animate-spin"></i>
                        Freemius Checkout
                    </a>
                <?php }else if( $this->is_pro && $license_direct && wcmmq_is_old_dir() ){ ?>
                    <a class="wcmmq-btn wcmmq-has-icon wcmmq-btn-tiny" 
                        href="<?php esc_attr( admin_url() ) ?>admin.php?page=wcmmq-license">
                        <span><i class="wcmmq_icon-plug"></i></span>
                        License
                    </a>
                <?php }else if( ! wcmmq_is_old_dir() ){ ?>
                <a class="wcmmq-btn reverse wcmmq-btn-tiny wcmmq-get-premium" 
                    href="https://customers.freemius.com/store/9916/websites" 
                    target="_blank">
                    <i class="wcmmq_icon-user"></i>Login Store
                </a>    
                    <?php } ?>
                
                <a class="wcmmq-btn wcmmq-btn-tiny" 
                    href="<?php echo esc_url( admin_url('admin.php?page=wcmmq-min-max-control-contact') ) ?>" 
                    target="_blank">
                    <i class="wcmmq_icon-user"></i>Support
                </a>
                <a class="wcmmq-btn reset wcmmq-btn-tiny" 
                    href="https://codeastrology.com/min-max-quantity/documentation/" 
                    target="_blank">
                    <i class="wcmmq_icon-help-circled"></i>Doc
                </a>
                
                <!-- <button class="wcmmq-btn"><span><i class="wcmmq_icon-cart"></i></span> Save Chabnge</button> -->
            </div>
        </div>
    </div>
</div>