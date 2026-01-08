<?php

namespace WPS\WPS_Bidouille;

class SystemReport {

	use Singleton;

	/**
	 * Get array of environment information.
	 *
	 * @return array
	 */
	public static function get_environment_info() {
		global $wpdb;

		// Figure out cURL version, if installed.
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		}

		// WP memory limit
		$wp_memory_limit = WP_MEMORY_LIMIT;
		if ( WP_MEMORY_LIMIT == '40M' ) {
			global $wps_memory;
			$wp_memory_limit = $wps_memory['limit'];
		}

		return array(
			'home_url'           => get_option( 'home' ),
			'site_url'           => get_option( 'siteurl' ),
			'wp_version'         => get_bloginfo( 'version' ),
			'wp_multisite'       => is_multisite(),
			'wp_memory_limit'    => $wp_memory_limit,
			'wp_debug_mode'      => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'wp_cron'            => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'           => get_locale(),
			'server_info'        => $_SERVER['SERVER_SOFTWARE'],
			'php_version'        => phpversion(),
			'php_post_max_size'  => ini_get( 'post_max_size' ),
			'php_max_input_vars' => ini_get( 'max_input_vars' ),
			'curl_version'       => $curl_version,
			'suhosin_installed'  => extension_loaded( 'suhosin' ),
			'max_upload_size'    => wp_max_upload_size(),
			'mysql_version'      => ( ! empty( $wpdb->is_mysql ) ? $wpdb->db_version() : '' ),
		);
	}

	/**
	 * Add prefix to table.
	 *
	 * @param string $table table name
	 *
	 * @return string
	 */
	protected function add_db_table_prefix( $table ) {
		global $wpdb;

		return $wpdb->prefix . $table;
	}

	/**
	 * Get array of database information. Version, prefix, and table existence.
	 *
	 * @return array
	 */
	public static function get_database_info() {
		global $wpdb;

		$database_table_sizes = $wpdb->get_results( $wpdb->prepare( "
			SELECT
			    table_name AS 'name',
			    round( ( data_length / 1024 / 1024 ), 2 ) 'data',
			    round( ( index_length / 1024 / 1024 ), 2 ) 'index'
			FROM information_schema.TABLES
			WHERE table_schema = %s
			ORDER BY name ASC;
		", DB_NAME ) );


		$database_size = array(
			'data'  => 0,
			'index' => 0
		);

		foreach ( $database_table_sizes as $table ) {
			$database_size['data']  += $table->data;
			$database_size['index'] += $table->index;
		}

		// Return all database info. Described by JSON Schema.
		return array(
			'database_prefix' => $wpdb->prefix,
			'database_size'   => $database_size,
		);
	}

	/**
	 * Get array of counts of objects.
	 *
	 * @return array
	 */
	public static function get_post_type_counts() {
		global $wpdb;

		$post_type_counts = $wpdb->get_results( "SELECT post_type AS 'type', count(1) AS 'count' FROM {$wpdb->posts} GROUP BY post_type;" );

		return is_array( $post_type_counts ) ? $post_type_counts : array();
	}

	/**
	 * Get a list of plugins active on the site.
	 *
	 * @return array
	 */
	public static function get_active_plugins() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		require_once( ABSPATH . 'wp-admin/includes/update.php' );

		if ( ! function_exists( 'get_plugin_updates' ) ) {
			return array();
		}

		// Get both site plugins and network plugins
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		$active_plugins_data = array();
		$available_updates   = get_plugin_updates();

		foreach ( $active_plugins as $plugin ) {
			$data           = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$version_latest = '';

			if ( isset( $available_updates[ $plugin ]->update->new_version ) ) {
				$version_latest = $available_updates[ $plugin ]->update->new_version;
			}

			// convert plugin data to json response format.
			$active_plugins_data[] = array(
				'plugin'            => $plugin,
				'name'              => $data['Name'],
				'version'           => $data['Version'],
				'version_latest'    => $version_latest,
				'url'               => $data['PluginURI'],
				'author_name'       => $data['AuthorName'],
				'author_url'        => esc_url_raw( $data['AuthorURI'] ),
				'network_activated' => $data['Network'],
			);
		}

		return $active_plugins_data;
	}

	/**
	 * Get info on the current active theme, info on parent theme (if present)
	 * and a list of template overrides.
	 *
	 * @return array
	 */
	public static function get_theme_info() {
		$active_theme = wp_get_theme();

		// Get parent theme info if this theme is a child theme, otherwise
		// pass empty info in the response.
		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->Template );
			$parent_theme_info = array(
				'parent_name'       => $parent_theme->Name,
				'parent_version'    => $parent_theme->Version,
				'parent_author_url' => $parent_theme->{'Author URI'},
			);
		} else {
			$parent_theme_info = array(
				'parent_name'       => '',
				'parent_version'    => '',
				'parent_author_url' => ''
			);
		}


		$active_theme_info = array(
			'name'           => $active_theme->Name,
			'version'        => $active_theme->Version,
			'author_url'     => esc_url_raw( $active_theme->{'Author URI'} ),
			'is_child_theme' => is_child_theme(),
		);

		return array_merge( $active_theme_info, $parent_theme_info );
	}

	public static function write_report() {
		$environment        = self::get_environment_info();
		$database           = self::get_database_info();
		$post_type_counts   = self::get_post_type_counts();
		$active_plugins     = self::get_active_plugins();
		$theme              = self::get_theme_info();
		$mu_plugins         = get_mu_plugins();
		$plugins_updates    = get_plugin_updates();
		$themes_updates     = get_theme_updates();

		$sizes = $post_types = $mu_texte = $plugins_texte = $output = '';
		if ( function_exists( 'ini_get' ) ) :
			$sizes = 'PHP Post Max Size: ' . esc_html( size_format( $environment['php_post_max_size'] ) ) . '
PHP Max Input Vars: ' . esc_html( $environment['php_max_input_vars'] ) . '
cURL Version: ' . esc_html( $environment['curl_version'] );
		endif;

		if ( ! empty( $post_type_counts ) ) {
			foreach ( $post_type_counts as $post_type ) {
				$post_types .= esc_html( $post_type->type ) . ' : ' . absint( $post_type->count ) . "\r\n";
			}
		}

		if ( ! empty( $mu_plugins ) ) {
			foreach ( $mu_plugins as $mu_plugin ) {
				if ( ! empty( $mu_plugin['Name'] ) ) {
					$mu_plugin_name = esc_html( $mu_plugin['Name'] );
				}

				$mu_texte .= $mu_plugin_name . ' ' . sprintf( __( 'by %s', 'wps-bidouille' ), $mu_plugin['Author'] ) . ' - ' . esc_html( $mu_plugin['Version'] ) . "\r\n";
			}
		}

		if ( ! empty( $active_plugins ) ) {
			foreach ( $active_plugins as $plugin ) {
				if ( ! empty( $plugin['name'] ) ) {
					$plugin_name = esc_html( $plugin['name'] );
				}

				$network_string = '';
				if ( false != $plugin['network_activated'] ) {
					$network_string = ' - ' . __( 'Network enabled', 'wps-bidouille' );
				}

				$plugins_texte .= $plugin_name . ' ' . sprintf( __( 'by %s', 'wps-bidouille' ), $plugin['author_name'] ) . ' - ' . esc_html( $plugin['version'] ) . $network_string . "\r\n";
			}
		}

		$updates = '';
		if ( empty( $plugins_updates ) ) {
			$updates .= 'Plugins à jour.' . "\r\n";
		} else {
			$arr = array();
			foreach ( $plugins_updates as $plugin ) {
				$arr[] = $plugin->Name;
			}
			$updates .= 'Plugins non à jour : ' . implode( ', ', $arr ) . "\r\n";
		}

		if ( empty( $themes_updates ) ) {
			$updates .= 'Thèmes à jour.' . "\r\n";
		} else {
			$arr = array();
			foreach ( $themes_updates as $theme ) {
				$arr[] = $theme->Name;
			}
			$updates .= 'Thèmes non à jour : ' . implode( ', ', $arr ) . "\r\n";
		}

		$user_account = Helpers::_get_wps_user();

		$pf = Helpers::wps_ip_check_return_pf();
		if ( ! empty( $pf ) ) {

			$output .= '### WPServeur ###
        
Login: ' . $user_account['login'] . '
Environment: ' . ( $user_account['env'] == 'prod' ? 'Production' : 'Clone' ) . '
WordPress #: ' . $user_account['wp_id'] . '
Serveur: ' . strtoupper( $pf ) . "\r\n\r\n";

        }

        $output .= '### Plugins and themes ###

' . $updates;

        $output .= '
### WordPress Environment ###

Home URL: ' . esc_html( $environment['home_url'] ) . '
Site URL: ' . esc_html( $environment['site_url'] ) . '
WP Version: ' . esc_html( $environment['wp_version'] ) . '
WP Multisite: ' . ( ( $environment['wp_multisite'] ) ? 'oui' : 'non' ) . '
WP Memory Limit: ' . $environment['wp_memory_limit'] . '
WP Debug Mode: ' . ( ( $environment['wp_debug_mode'] ) ? 'oui' : 'non' ) . '
WP Cron: ' . ( ( $environment['wp_cron'] ) ? 'oui' : 'non' ) . '
Language: ' . esc_html( $environment['language'] ) . '

### Server Environment ###

Server Info: ' . esc_html( $environment['server_info'] ) . '
PHP Version: ' . esc_html( $environment['php_version'] ) . '
' . $sizes . '

MySQL Version: ' . esc_html( $environment['mysql_version'] ) . '
Max Upload Size: ' . size_format( $environment['max_upload_size'] ) . '

### Database ###

Database Prefix: ' . esc_html( $database['database_prefix'] ) . '

Total Database Size: ' . sprintf( '%.2fMB', $database['database_size']['data'] + $database['database_size']['index'] ) . '
Database Data Size: ' . sprintf( '%.2fMB', $database['database_size']['data'] ) . '
Database Index Size: ' . sprintf( '%.2fMB', $database['database_size']['index'] ) . '

### Post Type Counts ###

' . $post_types . '
### Mu-plugins ###

' . $mu_texte . '
### Active Plugins (' . count( $active_plugins ) .') ###

' . $plugins_texte . '
### Theme ###

Name: ' . esc_html( $theme['name'] ) . '
Version: ' . esc_html( $theme['version'] ) . '
Author URL: ' . esc_html( $theme['author_url'] ) . '
Child Theme:  ' . ( ( $theme['is_child_theme'] ) ? 'oui' : 'non' );

		if ( $theme['is_child_theme'] ) :
            $output .= "\r\n";
			$output .= 'Parent theme name : ' . esc_html( $theme['parent_name'] ) . "\r\n";
			$output .= 'Parent theme version : ' . esc_html( $theme['parent_version'] ) . "\r\n";
			$output .= 'Parent theme author URL : ' . esc_html( $theme['parent_author_url'] ) . "\r\n";
		endif;

		echo $output;
	}
}