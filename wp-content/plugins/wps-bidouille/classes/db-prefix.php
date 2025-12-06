<?php

namespace WPS\WPS_Bidouille;

class DB_Prefix {

	use Singleton;

	protected function init() {
		add_action( 'admin_init', array( __CLASS__, 'change_db_prefix' ) );
	}

	public static function change_db_prefix() {

		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'change_db_prefix' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'change_db_prefix' ) {
			return false;
		}

		$wpdb_prefix     = Helpers::wps_db_prefix();
		$wpdb_new_prefix = self::wps_keygen_db_prefix();
		$tables          = self::wps_dbprefix_get_tables_to_alter();
		if ( empty( $tables ) ) {
			$notice = 4;
		} else {
			$result = self::wps_dbprefix_rename_tables( $tables, $wpdb_prefix, $wpdb_new_prefix );
			// check for errors
			if ( ! empty( $result ) ) {
				// try to rename the fields
				self::wps_dbprefix_rename_db_fields( $wpdb_prefix, $wpdb_new_prefix );

				$wp_config_file = ABSPATH . 'wp-config.php';

				if ( self::wps_dbprefix_update_wp_config_prefix( $wp_config_file, $wpdb_prefix, $wpdb_new_prefix ) ) {
					$notice = 1;
				} else {
					$notice = 2;
				}
			} else {
				$notice = 3;
			}
		}

		wp_redirect( add_query_arg( array( 'wps_notice_change_db_prefix' => $notice, 'new_wpdb_prefix' => $wpdb_new_prefix ), admin_url( 'admin.php?page=wps-bidouille' ) ) );
		exit;
	}

	/**
	 * @param $dbprefix_wpConfigFile
	 * @param $oldPrefix
	 * @param $newPrefix
	 *
	 * @return bool|int
	 */
	public static function wps_dbprefix_update_wp_config_prefix( $dbprefix_wpConfigFile, $oldPrefix, $newPrefix ) {
		// Check file' status's permissions
		if ( ! is_writable( $dbprefix_wpConfigFile ) ) {
			return - 1;
		}

		if ( ! function_exists( 'file' ) ) {
			return - 1;
		}

		// Try to update the wp-config file
		$lines    = file( $dbprefix_wpConfigFile );
		$fcontent = '';
		$result   = - 1;
		foreach ( $lines as $line ) {
			$line = ltrim( $line );
			if ( ! empty( $line ) ) {
				if ( strpos( $line, '$table_prefix' ) !== false ) {
					$line = preg_replace( "/=(.*)\;/", "= '" . $newPrefix . "';", $line );
				}
			}
			$fcontent .= $line;
		}
		if ( ! empty( $fcontent ) ) {
			$result = file_put_contents( $dbprefix_wpConfigFile, $fcontent );
		}

		return $result;
	}

	public static function wps_dbprefix_get_tables_to_alter() {
		global $wpdb;

		return $wpdb->get_results( "SHOW TABLES LIKE '" . $GLOBALS['table_prefix'] . "%'", ARRAY_N );
	}

	/**
	 * @param $tables
	 * @param $currentPrefix
	 * @param $newPrefix
	 *
	 * @return array
	 */
	public static function wps_dbprefix_rename_tables( $tables, $currentPrefix, $newPrefix ) {
		global $wpdb;
		$changedTables = array();
		foreach ( $tables as $k => $table ) {
			$tableOldName = $table[0];
			// Hide errors
			$wpdb->hide_errors();

			// To rename the table
			$tableNewName = substr_replace( $tableOldName, $newPrefix, 0, strlen( $currentPrefix ) );
			$wpdb->query( "RENAME TABLE `{$tableOldName}` TO `{$tableNewName}`" );
			array_push( $changedTables, $tableNewName );

		}

		return $changedTables;
	}

	/**
	 * @param $oldPrefix
	 * @param $newPrefix
	 *
	 * @return string
	 */
	public static function wps_dbprefix_rename_db_fields( $oldPrefix, $newPrefix ) {
		global $wpdb;

		/**
		 * usermeta
		 * options
		 * user_roles
		 */

		$str = '';
		if ( false === $wpdb->query( "UPDATE {$newPrefix}options SET option_name='{$newPrefix}user_roles' WHERE option_name='{$oldPrefix}user_roles';" ) ) {
			$str .= '<br/>Changing value: ' . $newPrefix . 'user_roles in table <strong>' . $newPrefix . 'options</strong>: <font color="#ff0000">Failed</font>';
		}
		$query = 'update ' . $newPrefix . 'usermeta set meta_key = CONCAT(replace(left(meta_key, ' . strlen( $oldPrefix ) . "), '{$oldPrefix}', '{$newPrefix}'), SUBSTR(meta_key, " . ( strlen( $oldPrefix ) + 1 ) . ")) where meta_key in ('{$oldPrefix}autosave_draft_ids', '{$oldPrefix}capabilities', '{$oldPrefix}metaboxorder_post', '{$oldPrefix}user_level', '{$oldPrefix}usersettings','{$oldPrefix}usersettingstime', '{$oldPrefix}user-settings', '{$oldPrefix}user-settings-time', '{$oldPrefix}dashboard_quick_press_last_post_id')";
		if ( false === $wpdb->query( $query ) ) {
			$str .= '<br/>Changing values in table <strong>' . $newPrefix . 'usermeta</strong>: <font color="#ff0000">Failed</font>';
		}
		if ( ! empty( $str ) ) {
			$str = '<br/><p>Changing database prefix:</p><p>' . $str . '</p>';
		}

		return $str;
	}

	public static function wps_keygen_db_prefix() {

		$length = rand( 3, 6 );
		$key    = '';
		list( $usec, $sec ) = explode( ' ', microtime() );
		mt_srand( (float) $sec + ( (float) $usec * 100000 ) );

		$inputs = array_merge( range( 'z', 'a' ) );

		for ( $i = 0; $i < $length; $i ++ ) {
			$key .= $inputs[mt_rand( 0, 37 )];
		}

		if ( strlen( $key ) < 3 ) {
			$add = array_rand( range( 'z', 'a' ) );
			$key = $key . $add;
		}

		return $key . '_';
	}
}