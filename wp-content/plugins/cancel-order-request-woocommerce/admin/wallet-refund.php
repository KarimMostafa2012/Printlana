<?php

class pisol_corw_wallet_refund_setting{

    public $plugin_name;

    private $settings = array();

    private $active_tab;

    private $this_tab = 'wallet-refund-setting';

    private $tab_name = "Auto refund as store credit";

    private $setting_key = 'pisol_corw_wallet_refund_setting';

    public $tab;


    function __construct($plugin_name){
        $this->plugin_name = $plugin_name;

        
        $this->tab = sanitize_text_field(filter_input( INPUT_GET, 'tab'));
        $this->active_tab = $this->tab != "" ? $this->tab : 'default';

        $this->settings = array(
            
            array('field'=>'pi_corw_enable_wallet_refund', 'label'=>__('Enable store credit refund','cancel-order-request-woocommerce'),'type'=>'switch', 'default'=> 0, 'desc'=>__('When user will place a cancellation request the order will be directly accepted and the refund will be added in the wallet','cancel-order-request-woocommerce')),

            array('field'=>'pi_corw_user_choice_for_wallet_refund', 'label'=>__('Store credit refund choice given to customer','cancel-order-request-woocommerce'),'type'=>'select', 'default'=>'wallet-refund', 'desc'=>'You can either give user a choice to get instant refund in wallet or don\'t give then the choice and force all refund in the wallet only', 'value'=>['wallet-refund'=>'No choice is given all refund are store credit', 'give-option'=>'Users are given a choice to select store credit refund or not']),

            array('field'=>'pi_corw_give_refund_in_wallet', 'label'=>__('Credit refund amount in the wallet','cancel-order-request-woocommerce'),'type'=>'text', 'default'=>__('Credit refund amount in the wallet','cancel-order-request-woocommerce'), 'desc'=>'This text is shown in the cancel request form when user is given a choice to select wallet refund or not'),
            
        );

        if($this->this_tab == $this->active_tab){
            add_action($this->plugin_name.'_tab_content', array($this,'tab_content'));
        }

        add_action($this->plugin_name.'_tab', array($this,'tab'),10);

        $this->register_settings();
        
    }

    function register_settings(){   

        foreach($this->settings as $setting){
            register_setting( $this->setting_key, $setting['field']);
        }
    
    }

    function tab(){
        ?>
        <a class=" px-3 text-light d-flex align-items-center  border-left border-right  <?php echo ($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo admin_url( 'admin.php?page='.sanitize_text_field($_GET['page']).'&tab='.$this->this_tab ); ?>">
           <span class="dashicons dashicons-money-alt"></span> <?php _e( $this->tab_name, 'cancel-order-request-woocommerce' ); ?> 
        </a>
        <a class=" px-3 text-light d-flex align-items-center  border-left border-right  bg-secondary" href="https://www.piwebsolution.com/user-documentation-cancel-order-request-for-woocommerce/" target="_blank" title="Documentation">
           <span class="dashicons dashicons-book"></span> <?php _e( 'Documentation', 'cancel-order-request-woocommerce' ); ?> 
        </a>
        <?php
    }

    function tab_content(){
        if(!pisol_corw_wallet_refund::has_supported_wallet()){
            echo '<div class="alert alert-success mt-3">This feature requires another plugin (<a href="https://wordpress.org/plugins/add-coupon-by-link-for-woocommerce/" target="_blank">Add coupon by url / Store credit</a>) from wordpress.org You can install it using below button<br> <button id="pisol-corw-install-dependency-plugin" class="button-primary mt-3">Click to Install / Activate module</button></div> ';
            
            return;
        }
       ?>
        <form method="post" action="options.php"  class="pisol-setting-form">
        <?php settings_fields( $this->setting_key ); ?>
        <?php
            foreach($this->settings as $setting){
                new pisol_class_form_corw($setting, $this->setting_key);
            }
        ?>
        <input type="submit" class="my-3 btn btn-primary btn-md" value="Save Option" />
        </form>
       <?php
    }

}

add_action('wp_loaded',function(){
    new pisol_corw_wallet_refund_setting($this->plugin_name);
});