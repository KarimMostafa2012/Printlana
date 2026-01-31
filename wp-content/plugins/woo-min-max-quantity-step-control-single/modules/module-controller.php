<?php
namespace WC_MMQ\Modules;

use WC_MMQ\Core\Base;
use WC_MMQ\Admin\Page_Loader;

class Module_Controller extends Base
{

    public $prefix = 'wcmmq_';

    // public $parent_menu;
    private $option_key = 'disable_modules';
    public $options;

    public $menu_title;
    private $modules = array();
    private $active_module_key = 'active_modules';
    private $active_module = array();

    private $folder_name = 'module';


    /**
     * Getting information from 
     */
    private $page_loader;
    public $dir = __DIR__;

    /**
     * For Instance
     *
     * @var Object 
     * 
     * @since 2.7.1
     */
    private static $_instance;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 2.7.1
     *
     * @access public
     * @static
     *
     * @return WC_MMQ An instance of the class.
     */
    public static function instance() {
        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {

        $this->menu_title = __( 'Module On/Off', 'woo-min-max-quantity-step-control-single' );
        
        $module_item = array(
            'guttenberg-block' => array(
                'key'   => 'guttenberg-block',
                'name'  =>  __( 'Min Max Step in Guttenberg Block', 'woo-min-max-quantity-step-control-single' ),
                'desc'  =>  __( 'For Gutenberg block shop page loop, Enable disable.', 'woo-min-max-quantity-step-control-single' ),
                'status'=>  'on',
                'dir'   =>  __DIR__,
            ),

        );
        $module_item = apply_filters( 'wcmmq_module_item', $module_item );

        
        
        $this->modules = apply_filters( 'wcmmq_module_arr', array(
            'data'      => array(
                'default' => 'on',
            ),
            'items'     => $module_item
        ) );
        

        $this->option_key = $this->prefix . $this->option_key;
        $this->active_module_key = $this->prefix . $this->active_module_key;

       foreach( $this->get_active_modules() as $key_modl=>$modl ){
           $file_dir = ! empty( $modl['dir'] ) ? $modl['dir'] : $this->dir;
           $file_dir = trailingslashit($file_dir);
           $file_name = $key_modl;
           $file = $file_dir . $this->folder_name . '/' . $file_name .'.php'; // '/'. $file_name . 
           
           if( is_file( $file ) ){
            include_once $file; 
           }
           
       }
    }


    public function update( $values = array() )
    {
        $off_values = array_map( function($arr){
            return 'off';
        },$this->get_default_option() );

        if( ! empty( $values ) && is_array( $values ) && is_array( $off_values ) ){
            $values = array_merge( $off_values, $values ); 
        }else{
            $values = $off_values;
        }

        update_option($this->option_key,$values);
        return $this;

    }

    public function get_option()
    {

        $option = get_option( $this->option_key );
        if( ! empty( $option ) && is_array( $option ) ) return $option;
        
        return $this->get_default_option();

    }
    
    public function get_default_option()
    {
        $def_option = array_map(function($arr){
            return $arr['status'];
        }, $this->modules['items']);
        return $def_option;

    }


    public function purefy_module()
    {
        $this->options = $this->get_option();
        
        if( empty( $this->modules['items'] ) || ! is_array( $this->modules['items'] ) ) return;
        $deflt_option = $this->get_default_option();
        foreach( $this->modules['items'] as $key=>$val ){
        $deflt_option = $this->get_default_option();
            $def_status = $deflt_option[$key] ?? 'off';

            $this->modules['items'][$key]['status'] = $this->options[$key] ?? $def_status;
        }

        return $this;
    }


    public function module_page()
    {
        // include $this->page_loader->page_folder_dir . 'topbar.php';
        include_once __DIR__ . '/module-page.php';
    }


    public function get_module_list()
    {
        $this->purefy_module();
        return $this->modules['items'] ?? array();
    }


    public function get_active_modules()
    {
        $active = array_filter($this->get_module_list(),function($arr){
            if( $arr['status'] !== 'off' ) return $arr;
        });

        return is_array( $active ) ? $active : array();
    }
    
    public function get_module_info()
    {
        return $this->modules['data'] ?? array();
    }
    

}
