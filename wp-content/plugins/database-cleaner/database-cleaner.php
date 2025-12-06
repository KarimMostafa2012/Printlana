<?php
/*
Plugin Name: Database Cleaner
Plugin URI: https://meowapps.com
Description: User-friendly tool to clean and optimize databases. Efficiently manages large databases, simplifying repair and ensuring peak performance.
Version: 1.3.1
Author: Jordy Meow
Author URI: https://meowapps.com
Text Domain: database-cleaner
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'DBCLNR_VERSION', '1.3.1' );
define( 'DBCLNR_PREFIX', 'dbclnr' );
define( 'DBCLNR_DOMAIN', 'database-cleaner' );
define( 'DBCLNR_ENTRY', __FILE__ );
define( 'DBCLNR_PATH', dirname( __FILE__ ) );
define( 'DBCLNR_URL', plugin_dir_url( __FILE__ ) );
define( 'DBCLNR_ITEM_ID', 16156087 );

require_once( 'classes/init.php' );

?>
