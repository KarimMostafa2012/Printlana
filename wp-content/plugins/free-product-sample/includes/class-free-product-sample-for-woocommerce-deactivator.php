<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    DSFPS_Free_Product_Sample_Pro
 * @subpackage DSFPS_Free_Product_Sample_Pro/includes
 * @author     Multidots <inquiry@multidots.in>
 */

if ( !class_exists( 'DSFPS_Free_Product_Sample_Pro_Deactivator' ) ) {
	class DSFPS_Free_Product_Sample_Pro_Deactivator {

		/**
		 * Short Description. (use period)
		 *
		 * Long Description.
		 *
		 * @since    1.0.0
		 */
		public static function deactivate() {

		}

	}
}