<?php 
namespace WC_MMQ\Admin;


use WC_MMQ\Core\Base;
use WC_MMQ\Admin\Page_Loader;

class Admin_Loader extends Base{
    public function __construct(){

        $main_page = new Page_Loader();
        $main_page->run();

    }

}