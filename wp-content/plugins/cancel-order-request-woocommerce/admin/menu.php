<?php

class pisol_corw_menu{

    public $plugin_name;
    public $version;
    public $menu;
    
    function __construct($plugin_name , $version){
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action( 'admin_menu', array($this,'plugin_menu') );
        add_action($this->plugin_name.'_promotion', array($this,'promotion'));

        add_action( 'wp_ajax_pisol_bogo_search_product', array( $this, 'search_product' ) );
    }

    function plugin_menu(){
        
        $this->menu = add_submenu_page(
            'woocommerce',
            __( 'Cancel order request', 'cancel-order-request-woocommerce'),
            __( 'Cancel order request', 'cancel-order-request-woocommerce'),
            'manage_options',
            'pisol-cancel-order-request',
            array($this, 'menu_option_page')
        );

        add_action("load-".$this->menu, array($this,"bootstrap_style"));

 
    }

    public function bootstrap_style() {
        wp_enqueue_script( $this->plugin_name."_quick_save", plugin_dir_url( __FILE__ ) . 'js/pisol-quick-save.js', array('jquery'), $this->version, 'all' );
    }


    function menu_option_page(){
        if(function_exists('settings_errors')){
            settings_errors();
        }
        ?>
        <div class="bootstrap-wrapper clear">
        <div class="pisol-container-fluid mt-2">
            <div class="pisol-row">
                    <div class="col-12">
                        <div class='bg-dark'>
                        <div class="pisol-row">
                            <div class="col-12 col-sm-2 py-2 d-flex align-items-center justify-content-center">
                                    <a href="https://www.piwebsolution.com/" target="_blank"><img id="pi-logo" class="img-fluid ml-2" src="<?php echo plugin_dir_url( __FILE__ ); ?>img/pi-web-solution.svg"></a>
                            </div>
                            <div class="col-12 col-sm-10 d-flex text-center small">
                                <nav id="pisol-navbar" class="navbar navbar-expand-lg navbar-light mr-0 ml-auto">
                                    <div>
                                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                            <?php do_action($this->plugin_name.'_tab'); ?>
                                        </ul>
                                    </div>
                                </nav>
                            </div>
                        </div>
                        </div>
                    </div>
            </div>
            <div class="pisol-row">
                <div class="col-12">
                <div class="bg-light border pl-3 pr-3 pt-0">
                    <div class="pisol-row">
                        <div class="col">
                        <?php do_action($this->plugin_name.'_tab_content'); ?>
                        </div>
                        <?php do_action($this->plugin_name.'_promotion'); ?>
                    </div>
                </div>
                </div>
            </div>
        </div>
        </div>
        <?php
        $this->support();
    }

    function promotion(){
        ?>
        <div class="col-12 col-sm-4 pt-3 border-left">

            
            <div class="pi-shadow rounded px-2 py-3 mb-3 pi-sticky">
                
                    <h2 id="pi-banner-tagline" class="mb-0" style="color:#ccc !important;">
                        <span class="d-block mb-4">⭐️⭐️⭐️⭐️⭐️</span>
                        <span class="d-block mb-2">🚀 Trusted by <span style="color:#fff;">3,000+</span> WooCommerce Stores</span>
                        <span class="d-block mb-2">Rated <span style="color:#fff;">4.9/5</span> – Users love it</span>
                    </h2>
                <div class="inside">
                    <ul class="pisol-pro-feature-list">
                        <li><span style="color:white;">&#10003;</span> Partial order cancellation</li>
                        <li><span style="color:white;">&#10003;</span> Disable cancel for specific product</li>
                        <li><span style="color:white;">&#10003;</span> Upload image with cancel request</li>
                        <li><span style="color:white;">&#10003;</span> Withdraw cancellation request</li>
                        <li><span style="color:white;">&#10003;</span> Disable cancel by payment method</li>
                        <li><span style="color:white;">&#10003;</span> Disable cancel by customer group</li>
                        <li><span style="color:white;">&#10003;</span> Set default action on repeat order</li>
                        <li><span style="color:white;">&#10003;</span> Redirect to cart/checkout on repeat</li>
                        <li><span style="color:white;">&#10003;</span> Auto refund to Wallet (TerraWallet)</li>
                    </ul>
                    <h4 class="pi-bottom-banner">💰 Just <?php echo esc_html(PISOL_CORW_PRICE); ?></h4>
                    <h4 class="pi-bottom-banner">🔥 Unlock all features and grow your sales!</h4>
                    <div class="text-center pb-3 pt-2">
                        <a class="btn btn-primary btn-md" href="<?php echo PISOL_CORW_BUY_URL; ?>&utm_ref=bottom_link" target="_blank">🔓 Unlock Pro Now – Limited Time Price!</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
    }

    function support(){
        $website_url = home_url();
        $plugin_name = $this->plugin_name;
        ?>
        <form action="https://www.piwebsolution.com/quick-support/" method="post" target="_blank" style="display:inline; position:fixed; bottom:30px; right:35px; z-index:9999;" >
            <input type="hidden" name="website_url" value="<?php echo esc_attr( $website_url ); ?>">
            <input type="hidden" name="plugin_name" value="<?php echo esc_attr( $plugin_name ); ?>">
            <button type="submit" style="background:none;border:none;cursor:pointer;padding:0;">
                <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>img/chat.png" 
                    alt="Live Support" title="Quick Support" style="width:60px;height:60px;">
            </button>
        </form>
        <?php
    }

}       