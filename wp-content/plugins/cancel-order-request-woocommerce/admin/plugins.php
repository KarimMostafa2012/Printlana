<?php

class pisol_corw_other_plugins{

    public $plugin_name;

    private $settings = array();

    private $active_tab;

    private $this_tab = 'pisol_corw_other_plugins';

    private $tab_name = "Other Plugins";

    private $setting_key = 'pisol_corw_other_plugins';

    public $tab;

    function __construct($plugin_name){
        $this->plugin_name = $plugin_name;

        
        $this->tab = sanitize_text_field(filter_input( INPUT_GET, 'tab'));
        $this->active_tab = $this->tab != "" ? $this->tab : 'default';

        $this->settings = array(
            
            
        );

        if($this->this_tab == $this->active_tab){
            add_action($this->plugin_name.'_tab_content', array($this,'tab_content'));
        }

        add_action($this->plugin_name.'_tab', array($this,'tab'),20);

        add_filter('install_plugins_nonmenu_tabs', array($this,'adding_tab_to_list'));

        add_filter('install_plugins_table_api_args_'.$this->this_tab, [$this, 'modify_tab_args']);

        add_action('install_plugins_'.$this->this_tab, [$this, 'plugin_list']);

        $this->register_settings();
        
    }

    
    function register_settings(){   

        foreach($this->settings as $setting){
            register_setting( $this->setting_key, $setting['field']);
        }
    
    }

    function tab(){
        ?>
        <a class=" px-3 text-light d-flex align-items-center  border-left border-right  <?php echo ($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page='.sanitize_text_field($_GET['page']).'&tab='.$this->this_tab ) ); ?>">
           <span class="dashicons dashicons-admin-plugins"></span> <?php echo esc_html( $this->tab_name ); ?> 
        </a>
        <?php
    }

    function tab_content(){

        do_action('install_plugins_'.$this->this_tab);
       
    }

    function adding_tab_to_list($tabs){
        $tabs[] = $this->this_tab;
        return $tabs;
    }

    function modify_tab_args($args){
        global $paged;
        return [
            'page' => $paged,
            'per_page' => 25,
            'locale' => get_user_locale(),
            'author' => 'rajeshsingh520',
        ];
    }

    function plugin_list(){

        require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';
        $table = new WP_Plugin_Install_List_Table();
        $table->prepare_items();
    
        wp_enqueue_script('plugin-install');
        add_thickbox();
        wp_enqueue_script('updates');
    
        echo '<div id="plugin-filter">';
        $table->display();
        echo '</div>';
    }


}

new pisol_corw_other_plugins($this->plugin_name);
