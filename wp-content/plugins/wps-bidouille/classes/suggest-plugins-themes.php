<?php

namespace WPS\WPS_Bidouille;

class Suggest_Plugins_Themes {

    use Singleton;

	protected function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_page', array( __CLASS__, 'admin_page' ) );

		add_filter( 'install_plugins_tabs', array( __CLASS__, 'install_plugins_tabs' ) );
		add_filter( 'install_themes_tabs', array( __CLASS__, 'install_plugins_tabs' ) );
		add_filter( 'install_plugins_table_api_args_wps_bidouille', array(
			__CLASS__,
			'install_plugins_table_api_args_wps_bidouille'
		) );

		add_action( 'wp_ajax_download_plugins_premium', array( __CLASS__, 'download_plugins_premium' ) );
		add_action( 'wp_ajax_download_themes_premium', array( __CLASS__, 'download_themes_premium' ) );
		add_action( 'wp_ajax_update_plugin_premium', array( __CLASS__, 'update_plugin_premium' ) );
		add_action( 'wp_ajax_update_theme_premium', array( __CLASS__, 'update_theme_premium' ) );
		add_action( 'wp_ajax_delete_transient_premium', array( __CLASS__, 'delete_transient_premium' ) );

		//add_action( 'after_plugin_row', array( __CLASS__, 'after_plugin_row' ), 10, 3 );
		//add_action( 'admin_footer', array(  __CLASS__, 'admin_footer_plugins' ) );
	}

	/**
	 * Admin menu WPServeur
	 */
	public static function admin_menu() {

		add_submenu_page(
			'wps-bidouille',
			__( 'Recommended themes and extensions', 'wps-bidouille' ),
			__( 'Recommended themes and extensions', 'wps-bidouille' ),
			'manage_options',
			'wps-bidouille-suggest-plugins',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function admin_page() {
		include( WPS_BIDOUILLE_DIR . '/admin_page/suggest_plugins_themes.php' );
	}

	/**
	 * @param $response
	 *
	 * @return array
	 */
	public static function get_json_array( $response ) {
		if ( empty( $response ) ) {
			return array();
		}

		$datas = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $datas ) ) {
			return array();
		}

		if ( isset( $datas['code'] ) && is_null( $datas['data'] ) ) {
			return array();
		}

		$premiums = array();
		foreach ( $datas as $data ) {

			$post_metas = $data['post-meta-fields'];
			$name       = '';
			if ( isset( $post_metas['Name'] ) ) {
				$name = $post_metas['Name'];
				$name = reset( $name );
			}

			if ( empty( $name ) ) {
				continue;
			}

			$version = '';
			if ( isset( $post_metas['Version'] ) ) {
				$version = $post_metas['Version'];
				$version = reset( $version );
			}

			$author = '';
			if ( isset( $post_metas['Author'] ) ) {
				$author = $post_metas['Author'];
				$author = reset( $author );
			}

			$description = '';
			if ( isset( $post_metas['description_override'] ) ) {
				$description = $post_metas['description_override'];
				$description = reset( $description );
			}

			$wps_link_more_details = '';
			if ( isset( $post_metas['wps_link_more_details'] ) ) {
				$wps_link_more_details = $post_metas['wps_link_more_details'];
				$wps_link_more_details = reset( $wps_link_more_details );
			}

			$icon = '';
			if ( isset( $post_metas['wps_icon'] ) ) {
				$icon = $post_metas['wps_icon'];
				$icon = reset( $icon );
			}

			$wps_slug_plugin = '';
			if ( isset( $post_metas['wps_slug_plugin'] ) ) {
				$wps_slug_plugin = $post_metas['wps_slug_plugin'];
				$wps_slug_plugin = reset( $wps_slug_plugin );
			}

			$wps_url = '';
			if ( isset( $post_metas['wps_url'] ) ) {
				$wps_url = $post_metas['wps_url'];
				$wps_url = reset( $wps_url );
			}

			$modified_gmt = $data['modified_gmt'];

			$time        = strtotime( $modified_gmt );
			$dateInLocal = date_i18n( "d/m/Y", $time );

			$current_theme = get_stylesheet();
			$slug          = sanitize_title( $name );

			$premiums[] = array(
				'id'                => $slug,
				'slug'              => $slug,
				'name'              => $name,
				'wps_slug_plugin'   => $wps_slug_plugin,
				'version'           => $version,
				'author'            => $author,
				'icon'              => $icon,
				'short_description' => $description,
				'description'       => $description,
				'download_link'     => $wps_url,
				'last_updated'      => $dateInLocal,
				'more_details'      => $wps_link_more_details,
				'active'            => $name === $current_theme,
			);
		}

		return $premiums;
	}

	/**
	 * @param $plugin
	 *
	 * @return array
	 */
	public static function get_action_links_plugin_premium( $plugin ) {
		$action_links        = array();
		$is_plugin_installed = Helpers::is_plugin_installed( $plugin['wps_slug_plugin'] );
		$wps_plugin_version  = Helpers::plugin_version( $plugin['wps_slug_plugin'] );
		if ( ! $is_plugin_installed ) {
			$action_links[] = '<a class="button wps_bidouill_install_premiums" id="' . sanitize_title( $plugin['slug'] ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" data-nonce="' . wp_create_nonce( md5( $plugin['download_link'] ) ) . '" data-href="' . $plugin['download_link'] . '" href="#" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $plugin['name'] ) ) . '" data-name="' . esc_attr( $plugin['name'] ) . '">' . __( 'Install Now' ) . '</a>';
			$button_text    = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'plugin' );
			$activate_url = add_query_arg( array(
				'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $plugin['wps_slug_plugin'] ),
				'action'   => 'activate',
				'plugin'   => $plugin['wps_slug_plugin'],
			), network_admin_url( 'plugins.php' ) );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'plugin' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" class="button activate-now" style="display:none;" id="' . sanitize_title( $plugin['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $plugin['name'] ) ),
				$button_text
			);
		} elseif ( version_compare( $plugin['version'], $wps_plugin_version, '>' ) ) {
			$active         = ( is_plugin_active_for_network( $plugin['wps_slug_plugin'] ) || is_plugin_active( $plugin['wps_slug_plugin'] ) ) ? 'installed' : 'activate';
			$action_links[] = '<a class="button aria-button-if-js wps_bidouille_update_plugin_premiums" data-plugin="' . esc_attr( $plugin['wps_slug_plugin'] ) . '" data-wps="' . $active . '" data-nonce="' . wp_create_nonce( md5( $plugin['download_link'] ) ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" id="' . sanitize_title( $plugin['slug'] ) . '" data-href="' . esc_url( $plugin['download_link'] ) . '" href="#" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $plugin['name'] ) ) . '" data-name="' . esc_attr( $plugin['name'] ) . '">' . __( 'Update Now' ) . '</a>';

			$action_links[] = '<button style="display:none;" type="button" class="button button-disabled" id="' . sanitize_title( $plugin['slug'] ) . '-installed" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
			$button_text    = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'plugin' );
			$activate_url = add_query_arg( array(
				'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $plugin['wps_slug_plugin'] ),
				'action'   => 'activate',
				'plugin'   => $plugin['wps_slug_plugin'],
			), network_admin_url( 'plugins.php' ) );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'plugin' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" style="display:none;" class="button activate-now" id="' . sanitize_title( $plugin['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $plugin['name'] ) ),
				$button_text
			);
		} elseif ( is_plugin_active_for_network( $plugin['wps_slug_plugin'] ) || is_plugin_active( $plugin['wps_slug_plugin'] ) ) {
			$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
		} elseif ( ! is_plugin_active( $plugin['wps_slug_plugin'] ) ) {
			$button_text = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'plugin' );
			$activate_url = add_query_arg( array(
				'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $plugin['wps_slug_plugin'] ),
				'action'   => 'activate',
				'plugin'   => $plugin['wps_slug_plugin'],
			), network_admin_url( 'plugins.php' ) );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'plugin' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" class="button activate-now" id="' . sanitize_title( $plugin['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $plugin['name'] ) ),
				$button_text
			);
		}
		if ( ! empty( $plugin['more_details'] ) ) {
			$action_links[] = '<a href="' . esc_url( $plugin['more_details'] ) . '" target="_blank">' . __( 'More Details' ) . '</a>';
		}

		return $action_links;
	}

	/**
	 * @param $theme
	 *
	 * @return array
	 */
	public static function get_action_links_theme_premium( $theme ) {
		$action_links       = array();
		$is_theme_installed = Helpers::is_theme_installed( $theme['name'] );
		$wps_theme_version  = Helpers::theme_version( $theme['wps_slug_plugin'] );
		if ( ! $is_theme_installed ) {
			$action_links[] = '<a class="button wps_bidouille_install_theme_premiums" id="' . sanitize_title( $theme['slug'] ) . '" data-slug="' . esc_attr( $theme['slug'] ) . '" data-nonce="' . wp_create_nonce( md5( $theme['download_link'] ) ) . '" data-href="' . $theme['download_link'] . '" href="#" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $theme['name'] ) ) . '" data-name="' . esc_attr( $theme['name'] ) . '">' . __( 'Install Now' ) . '</a>';
			$button_text    = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'theme' );
			$activate_url = wp_nonce_url( admin_url( 'themes.php?action=activate&amp;stylesheet=' . urlencode( $theme['name'] ) ), 'switch-theme_' . $theme['name'] );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'theme' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" class="button activate-now" style="display:none;" id="' . sanitize_title( $theme['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $theme['name'] ) ),
				$button_text
			);
		} elseif ( version_compare( $theme['version'], $wps_theme_version, '>' ) ) {
			$active         = ( $theme['active'] === true ) ? 'installed' : 'activate';
			$action_links[] = '<a class="button aria-button-if-js wps_bidouille_update_theme_premiums" data-wps="' . $active . '" data-nonce="' . wp_create_nonce( md5( $theme['download_link'] ) ) . '" data-slug="' . esc_attr( $theme['slug'] ) . '" id="' . sanitize_title( $theme['slug'] ) . '" data-href="' . esc_url( $theme['download_link'] ) . '" href="#" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $theme['name'] ) ) . '" data-name="' . esc_attr( $theme['name'] ) . '">' . __( 'Update Now' ) . '</a>';

			$action_links[] = '<button style="display:none;" type="button" class="button button-disabled" id="' . sanitize_title( $theme['slug'] ) . '-installed" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';

			$button_text = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'theme' );
			$activate_url = wp_nonce_url( admin_url( 'themes.php?action=activate&amp;stylesheet=' . urlencode( $theme['name'] ) ), 'switch-theme_' . $theme['name'] );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'theme' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" style="display:none;" class="button activate-now" id="' . sanitize_title( $theme['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $theme['name'] ) ),
				$button_text
			);
		} elseif ( $theme['active'] === true ) {
			$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
		} elseif ( $theme['active'] !== true ) {
			$button_text = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'theme' );
			$activate_url = wp_nonce_url( admin_url( 'themes.php?action=activate&amp;stylesheet=' . urlencode( $theme['name'] ) ), 'switch-theme_' . $theme['name'] );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'theme' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" class="button activate-now" id="' . sanitize_title( $theme['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $theme['name'] ) ),
				$button_text
			);
		}
		if ( ! empty( $theme['more_details'] ) ) {
			$action_links[] = '<a href="' . esc_url( $theme['more_details'] ) . '" target="_blank">' . __( 'More Details' ) . '</a>';
		}

		return $action_links;
	}

	/**
	 * @param $theme
	 *
	 * @return array
	 */
	public static function get_action_links_theme( $theme ) {
		$action_links       = array();
		$theme_slug         = $theme['slug'];
		$current_theme      = get_stylesheet();
		$theme['active']    = ( $theme_slug === $current_theme );
		$encoded_slug       = urlencode( $theme_slug );
		$is_theme_installed = Helpers::is_theme_installed( $theme_slug );

		$is_update = get_theme_update_available( wp_get_theme( $theme_slug ) );

		if ( ! $is_theme_installed ) {
			$action_links[] = '<a class="button wps-install-theme" id="' . sanitize_title( $theme['slug'] ) . '" data-slug="' . esc_attr( $theme['slug'] ) . '" href="' . esc_url( admin_url( 'update.php?action=install-theme&theme=' . $theme_slug . '&_wpnonce=' . wp_create_nonce( 'install-theme_' . $theme_slug ) ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $theme['name'] ) ) . '" data-name="' . esc_attr( $theme['name'] ) . '">' . __( 'Install Now' ) . '</a>';
		} elseif ( $is_update ) {
			$update_url     = wp_nonce_url( admin_url( 'update.php?action=upgrade-theme&amp;theme=' . urlencode( $theme_slug ) ), 'upgrade-theme_' . $theme_slug );
			$action_links[] = '<a class="button aria-button-if-js wps-update-theme" data-slug="' . esc_attr( $theme['slug'] ) . '" id="' . sanitize_title( $theme['slug'] ) . '" href="' . esc_url( $update_url ) . '" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $theme['name'] ) ) . '" data-name="' . esc_attr( $theme['name'] ) . '">' . __( 'Update Now' ) . '</a>';
		} elseif ( $theme['active'] === true ) {
			$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
		} elseif ( $theme['active'] !== true ) {
			$button_text = __( 'Activate' );
			/* translators: %s: Plugin name */
			$button_label = _x( 'Activate %s', 'theme' );
			$activate_url = wp_nonce_url( admin_url( 'themes.php?action=activate&amp;stylesheet=' . $encoded_slug ), 'switch-theme_' . $theme_slug );

			if ( is_network_admin() ) {
				$button_text = __( 'Network Activate' );
				/* translators: %s: Plugin name */
				$button_label = _x( 'Network Activate %s', 'theme' );
				$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
			}

			$action_links[] = sprintf(
				'<a href="%1$s" class="button activate-now" id="' . sanitize_title( $theme['slug'] ) . '-activate" aria-label="%2$s">%3$s</a>',
				esc_url( $activate_url ),
				esc_attr( sprintf( $button_label, $theme['name'] ) ),
				$button_text
			);
		}
		if ( ! empty( $theme['more_details'] ) ) {
			$action_links[] = '<a href="' . esc_url( $theme['more_details'] ) . '" target="_blank">' . __( 'More Details' ) . '</a>';
		}

		return $action_links;
	}

	/**
	 * @param int $category_premium
	 *
	 * @return array|string|\WP_Error
	 */
	public static function get_api_result( $category_premium = WPS_BIDOUILLE_API_CAT_PLUGINS ) {
		// TODO : a supprimer
		//delete_transient( 'wps_api_category_premium_' . $category_premium );

		if ( false === ( $response = get_transient( 'wps_api_category_premium_' . $category_premium ) ) ) {
			$response = wp_remote_get( WPS_BIDOUILLE_API_URL . 'wp/v2/wps_p_t_premium/?per_page=100&category_premium=' . $category_premium );
			set_transient( 'wps_api_category_premium_' . $category_premium, $response, 24 * HOUR_IN_SECONDS );
		}

		$datas = '';
		if ( is_array( $response ) ) {
			$datas = $response;
		}

		return $datas;
	}

	/**
	 * @param $tabs
	 *
	 * @return array
	 */
	public static function install_plugins_tabs( $tabs ) {
		$screen = get_current_screen();

		if ( ! in_array( $screen->id, array( 'wps-bidouille_page_wps-bidouille-suggest-plugins' ) ) ) {
			return $tabs;
		}

		$tabs = array_merge( $tabs, array( 'wps_bidouille' => 'WPS Bidouille' ) );

		return $tabs;
	}

	public static function wps_bidouille_get_installed_plugins() {
		$plugins = array();

		$plugin_info = get_site_transient( 'update_plugins' );
		if ( isset( $plugin_info->no_update ) ) {
			foreach ( $plugin_info->no_update as $plugin ) {
				$plugin->upgrade          = false;
				$plugins[ $plugin->slug ] = $plugin;
			}
		}

		if ( isset( $plugin_info->response ) ) {
			foreach ( $plugin_info->response as $plugin ) {
				$plugin->upgrade          = true;
				$plugins[ $plugin->slug ] = $plugin;
			}
		}

		return $plugins;
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	public static function install_plugins_table_api_args_wps_bidouille( $args ) {
		global $paged;

		$args = array(
			'page'              => $paged,
			'per_page'          => 30,
			'fields'            => array(
				'last_updated'    => true,
				'icons'           => true,
				'active_installs' => true
			),
			'installed_plugins' => array_keys( self::wps_bidouille_get_installed_plugins() ),
			'user'              => 'WPServeur'
		);

		if ( function_exists( 'get_user_locale' ) ) {
			$args['locale'] = get_user_locale();
		}

		return $args;
	}

	public static function download_plugins_premium() {
		check_ajax_referer( md5( $_POST['url'] ) );

		$download_url = ( isset( $_POST['url'] ) ) ? $_POST['url'] : '';
		Helpers::download_package( $download_url );
		wp_die();
	}

	public static function download_themes_premium() {
		check_ajax_referer( md5( $_POST['url'] ) );

		$download_url = ( isset( $_POST['url'] ) ) ? $_POST['url'] : '';
		Helpers::download_theme_package( $download_url );
		wp_die();
	}

	public static function update_plugin_premium() {
		check_ajax_referer( md5( $_POST['url'] ) );

		$download_url = ( isset( $_POST['url'] ) ) ? $_POST['url'] : '';
		Helpers::download_package( $download_url );
		wp_die();
	}

	public static function update_theme_premium() {
		check_ajax_referer( md5( $_POST['url'] ) );

		$download_url = ( isset( $_POST['url'] ) ) ? $_POST['url'] : '';
		Helpers::download_theme_package( $download_url );
		wp_die();
	}

	public static function delete_transient_premium() {
		check_ajax_referer( 'delete-transient-premiums', 'nonce' );
		delete_transient( 'wps_api_category_premium_' . WPS_BIDOUILLE_API_CAT_PLUGINS );
		delete_transient( 'wps_api_category_premium_' . WPS_BIDOUILLE_API_CAT_THEMES );
		wp_die();
	}

	public static function after_plugin_row( $plugin_file, $plugin_data, $status ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		if ( ! isset( $plugin_data['slug'] ) ) {
			return false;
		}

		$api = plugins_api( 'plugin_information', array(
			'slug'   => $plugin_data['slug'],
			'fields' => array(
				'short_description' => false,
				'description'       => false,
				'sections'          => false,
				'tested'            => true,
				'requires'          => true,
				'rating'            => false,
				'ratings'           => false,
				'downloaded'        => false,
				'downloadlink'      => false,
				'last_updated'      => true,
				'added'             => true,
				'tags'              => false,
				'compatibility'     => true,
				'homepage'          => true,
				'versions'          => false,
				'donate_link'       => false,
				'reviews'           => false,
				'banners'           => false,
				'icons'             => false,
				'active_installs'   => false,
				'group'             => false,
				'contributors'      => false,
			),
		) );

		if ( ! is_wp_error( $api ) ) {
			return false;
		}

		$response = wp_remote_get( WPS_BIDOUILLE_API_URL . 'wps_premium/v1/val=' . $plugin_file );

		$datas = '';
		if ( is_array( $response ) ) {
			$datas = $response; // use the content
		}

		if ( ! empty( $datas ) ) {
			return false;
		}

		$datas = json_decode( wp_remote_retrieve_body( $datas ), true );

		if ( ! is_array( $datas ) ) {
			return array();
		}

		$plugin_premium = array(
			'Version'       => reset( $datas['Version'] ),
			'download_link' => reset( $datas['wps_url'] )
		);

		if ( version_compare( $plugin_premium['Version'], Helpers::plugin_version( $plugin_file ), '>' ) ) {
			$shortMsg = sprintf( __( 'A new version for %s is available.', 'wps-bidouille' ), $plugin_data['Name'] ) . ' ';
			$shortMsg .= '<a class="wps_bidouille_update_plugin_premiums" data-plugin="' . esc_attr( $plugin_file ) . '" data-nonce="' . wp_create_nonce( md5( $plugin_premium['download_link'] ) ) . '" data-slug="' . esc_attr( $plugin_data['slug'] ) . '" id="' . sanitize_title( $plugin_data['slug'] ) . '" data-href="' . esc_url( $plugin_premium['download_link'] ) . '" href="#" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $plugin_data['Name'] ) ) . '" data-name="' . esc_attr( $plugin_data['Name'] ) . '">' . __( 'Update Now' ) . '</a>';
			if ( is_network_admin() ) {
				$active_class = is_plugin_active_for_network( $plugin_file ) ? 'active' : '';
			} else {
				$active_class = is_plugin_active( $plugin_file ) ? 'active' : '';
			} ?>
            <script language="javascript" type="text/javascript">
                var api_slug = '<?php echo $plugin_data['slug']; ?>';
                jQuery('[data-slug=' + api_slug + ']').find('th').css('box-shadow', 'none');
                jQuery('[data-slug=' + api_slug + ']').find('td').css('box-shadow', 'none');
            </script>
			<?php
			echo '<tr class="plugin-update-tr wps-bidouille-alert-maj-plugin-premium ' . $active_class . '"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>' . $shortMsg . '</p></div></td></tr>';

		}
	}

	public static function admin_footer_plugins() {
		global $pagenow, $screen;

		if ( 'plugins.php' !== $pagenow ) {
			return false;
		} ?>

        <script language="javascript" type="text/javascript">

            var el = jQuery('.wps-bidouille-alert-maj-plugin-premium').next();
            if (el.attr('id').indexOf('-update') > 1) {
                el.hide();
            }

            jQuery('a.wps_bidouille_update_plugin_premiums').click(function (event) {
                event.preventDefault();

                var url = jQuery(this).attr('data-href');
                var nonce = jQuery(this).attr('data-nonce');
                var id = jQuery(this).attr('id');

                data = {
                    'action': 'update_plugin_premium',
                    'url': url,
                    'nonce': nonce
                };

                // We can also pass the url value separately from ajaxurl for front end AJAX implementations
                var t = jQuery.post(ajaxurl, data);

                t.done(function () {
                    jQuery('#' + id).text(_wpUpdatesSettings.l10n.updating);
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                });
            });
        </script>
		<?php
	}

}
