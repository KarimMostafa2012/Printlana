<?php

namespace WPS\WPS_Bidouille;

class Plugin {

	use Singleton;

	static $website_url = 'https://www.wpserveur.net';

	protected function init() {
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'wps_login_enqueue_scripts' ) );
		add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// Hide menu WPS
		//remove_action( 'wp_before_admin_bar_render', 'menu_wps_admin_bar_render' );
		//add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'wp_before_admin_bar_render' ) );

		//add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widgets' ) );

		// add_action( 'admin_init', array( __CLASS__, 'download_log_template_redirect' ) );
		add_action( 'admin_init', array( __CLASS__, 'download_report_system_template_redirect' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_unuse_themes' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_unuse_plugins' ) );

		add_action( 'wp_ajax_dismiss_admin_notice', array( __CLASS__, 'dismiss_admin_notice' ) );
		add_action( 'wp_ajax_count_notif', array( __CLASS__, 'count_notif' ) );

		add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'wps_env' ), 999 );

		add_action( 'wp_ajax_add_option_wps_display', array( __CLASS__, 'add_option_wps_display' ) );
		add_action( 'wp_ajax_delete_option_wps_display', array( __CLASS__, 'delete_option_wps_display' ) );

		add_action( 'wp_ajax_add_allow_repair_wp_config', array( __CLASS__, 'add_allow_repair_wp_config' ) );
		add_action( 'init', array( __CLASS__, 'remove_allow_repair_wp_config' ) );

		add_action( 'after_plugin_row', array( __CLASS__, 'after_plugin_row' ), 10, 3 );
		add_filter( 'all_plugins', array( __CLASS__, 'all_plugins' ) );

		add_action( 'admin_init', array( __CLASS__, 'hide_notice_autoupdates' ) );

		add_action( 'deleted_plugin', array( __CLASS__, 'deleted_plugin' ), 10, 2 );

		add_filter( 'plugin_action_links_' . WPS_BIDOUILLE_BASENAME, array( __CLASS__, 'plugin_action_links' ) );

		add_filter( 'admin_footer', array( __CLASS__, 'admin_footer' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'admin_footer_text' ), 1 );
		add_action( 'wp_ajax_wpsbidouille_rated', array( __CLASS__, 'wpsbidouille_rated' ) );
	}

	public static function add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'wps_bidouille_dashboard_widget',
			'WPS Bidouille',
			array( __CLASS__, 'example_dashboard_widget_function' )
		);
	}

	public static function example_dashboard_widget_function() {

		echo '<style>
        #wps_bidouille_dashboard_widget {
            background: #33414e;
            color: #fff;
        }
        #wps_bidouille_dashboard_widget h2 {
            color: #95be22;
        }
        #wps_bidouille_dashboard_widget .inside a.button.button-primary {
            width: 100%;
            text-align:center;
            background: #95be22;
            border-color: #729314;
            box-shadow: 0 1px 0 #617e0f !important;
            color: #fff;
            text-decoration: none;
            text-shadow:none;
        }
        #wps_bidouille_dashboard_widget.postbox .hndle {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        </style>';

		$notifs = (int) get_option( 'wps_notifs_ajax' );
		if ( $notifs ) {
			echo sprintf( __( 'Vous avez %s notifications.', 'wps-bidouille' ), $notifs ) . '<br><br>';
		}

		echo '<a href="' . admin_url( 'admin.php?page=wps-bidouille' ) . '" class="button button-primary button-hero">' . __( 'Voir les notifications', 'wps-bidouille' ) . '</a>';
	}

	public static function hide_notice_autoupdates() {
		if ( empty( $_GET ) ) {
			return false;
		}

		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}

		if ( 'wps-bidouille' !== $_GET['page'] ) {
			return false;
		}

		remove_action( 'admin_notices', array( 'WP_Formation_Auto_Update_Config', 'show_admin_warning' ), 20 );
	}

	public static function wps_login_enqueue_scripts() {
		$wps_white_list = get_option( 'wps_white_list' );
		if ( 'true' == $wps_white_list ) : ?>
            <style type="text/css">
                #backtoblog:after {
                    display: none !important;
                }
            </style>
		<?php
		endif;
	}

	public static function admin_head() { ?>
        <style>
            #toplevel_page_wps-bidouille ul {
                display: none;
            }

            #toplevel_page_wps-bidouille > a > .wp-menu-image > img {
                width: 24px;
                height: auto;
                margin-top: -3px;
                margin-left: 6px;
            }
        </style>
		<?php
		$is_user_white_label = Helpers::is_user_white_label();
		if ( $is_user_white_label ) { ?>
            <style>
                [data-slug^="wps-"] .column-description:after, [data-slug="plugin-security-scanner"] .column-description:after, [data-slug="block-bad-queries"] .column-description:after, [data-slug^="wps-"].active .column-description:after, [data-slug="plugin-security-scanner"].active .column-description:after, [data-slug="block-bad-queries"].active .column-description:after {
                    content: "" !important;
                }

                [data-slug^="wps-"] .column-primary, [data-slug="block-bad-queries"] .column-primary, [data-slug="plugin-security-scanner"] .column-primary, [data-slug^="wps-"].active .column-primary, [data-slug="block-bad-queries"].active .column-primary, [data-slug="plugin-security-scanner"].active .column-primary {
                    background-image: none !important;
                }
                #wp-admin-bar-wps-link {
                    display: none;
                }
            </style>
			<?php
		}
	}

	/**
	 * Admin menu WPServeur
	 */
	public static function admin_menu() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		add_menu_page(
			__( 'WPS Bidouille', 'wps-bidouille' ),
			sprintf( __( 'WPS Bidouille %s', 'wps-bidouille' ), '<span class="update-plugins wps-notifs"><span class="pending-count"></span></span>' ),
			'manage_options',
			'wps-bidouille',
			array( __CLASS__, 'admin_page' ),
			'https://www.wpserveur.net/muwps/WPServeur-logo-notext.svg'
		);
	}

	/**
	 * Display a custom menu page
	 */
	public static function admin_page() {
		include( WPS_BIDOUILLE_DIR . '/admin_page/plugin.php' );
	}

	/**
	 * Load scripts
	 */
	public static function admin_enqueue_scripts( $hook ) {

		wp_enqueue_script( 'wps-notifs', WPS_BIDOUILLE_URL . 'assets/js/notifs.js', array(
			'jquery'
		), false, true );

		$notifs = (int) get_option( 'wps_notifs_ajax' );
		wp_localize_script(
			'wps-notifs',
			'count_notifs',
			array(
				'notifs' => $notifs,
				'text'   => __( 'All is well, so go take a small coffee ;) We will notify you in case of new notifications.', 'wps-bidouille' ),
                'nonce'  => wp_create_nonce( 'count-notifs' )
			)
		);

		if ( false === strpos( $hook, 'wps-bidouille' ) ) {
			return false;
		}

		wp_enqueue_style( 'wps-bidouille-fa', WPS_BIDOUILLE_URL . 'assets/fontawesome/web-fonts-with-css/fontawesome-all.min.css' );
		wp_enqueue_style( 'wps-bidouille-style', WPS_BIDOUILLE_URL . 'assets/css/style.css', array(), WPS_BIDOUILLE_VERSION );
		wp_enqueue_script( 'wps-bidouille-fa', WPS_BIDOUILLE_URL . 'assets/fontawesome/fontawesome-all.min.js', array(), false, true );

		wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );

		wp_enqueue_script( 'wps-bidouille-tip', WPS_BIDOUILLE_URL . 'assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'select2-fr', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/i18n/fr.js', array( 'select2' ) );
		wp_enqueue_script( 'wps-bidouille-functions', WPS_BIDOUILLE_URL . 'assets/js/functions.js', array(
			'jquery',
			'select2'
		), false, true );

		wp_localize_script(
			'wps-bidouille-functions',
			'dismissible_notice',
			array(
				'nonce' => wp_create_nonce( 'wps-bidouille-dismissible-notice' ),
			)
		);

		wp_enqueue_style( 'plugin-install' );

		wp_enqueue_script( 'plugin-install' );
		wp_enqueue_script( 'updates' );
		add_thickbox();
	}

	/**
	 * Downloads log access and error log
	 */
	public static function download_log_template_redirect() {

		if ( empty( $_GET ) || ! isset( $_GET['action'] ) ) {
			return false;
		}

		$wps_site_details = Helpers::_get_wps_user();
		$log_folder       = '/home/clients/' . $wps_site_details['login'] . '/logs/' . $wps_site_details['wp_id'] . '/';

		$error_filename = 'php-fpm-errors.log';
		$error_log      = $log_folder . $error_filename;

		$access_filename = 'access.log';
		$access_log      = $log_folder . $access_filename;

		if ( 'downloads_error_log' === $_GET['action'] ) {
			header( "Content-type: application/x-msdownload", true, 200 );
			header( "Content-Disposition: attachment; filename=error_log" );
			header( "Pragma: no-cache" );
			header( "Expires: 0" );
			readfile( $error_log );
			exit();
		}

		if ( 'downloads_access_log' === $_GET['action'] ) {
			header( "Content-type: application/x-msdownload", true, 200 );
			header( "Content-Disposition: attachment; filename=access_log" );
			header( "Pragma: no-cache" );
			header( "Expires: 0" );
			readfile( $access_log );
			exit();
		}
	}

	/**
	 * Download report system
	 */
	public static function download_report_system_template_redirect() {
		if ( ! empty( $_GET ) && isset( $_GET['action'] ) && 'downloads_system_report' === $_GET['action'] ) {
			check_admin_referer( 'download-system-report', 'nonce' );
			header( "Content-type: application/x-msdownload", true, 200 );
			header( "Content-Disposition: attachment; filename=system_report.txt" );
			header( "Pragma: no-cache" );
			header( "Expires: 0" );
			SystemReport::write_report();
			exit();
		}
	}

	/**
	 *
	 * Delete inactivate themes
	 *
	 * @return bool
	 */
	public static function delete_unuse_themes() {
		if ( ! current_user_can( 'delete_themes' ) ) {
			return false;
		}

		if ( empty( $_GET ) ) {
			return false;
		}

		global $pagenow;
		if ( $pagenow == 'themes.php' && isset( $_GET['wps-delete-themes-unuse'] ) && $_GET['wps-delete-themes-unuse'] == true ) {
			check_admin_referer( 'delete-theme-unuse', 'nonce' );
			Helpers::delete_unuse_themes();
			delete_site_transient( 'disable-notice-themes-inactive' );
			wp_redirect( wp_get_referer() );
			exit();
		}
	}

	/**
	 *
	 * Delete inactivate plugins
	 *
	 * @return bool
	 */
	public static function delete_unuse_plugins() {
		if ( ! current_user_can( 'delete_plugins' ) ) {
			return false;
		}

		if ( empty( $_GET ) ) {
			return false;
		}

		global $pagenow;
		if ( $pagenow == 'plugins.php' && isset( $_GET['wps-delete-plugins-unuse'] ) && $_GET['wps-delete-plugins-unuse'] == true ) {
			check_admin_referer( 'delete-plugin-unuse', 'nonce' );
			Helpers::delete_unuse_plugins();
			delete_site_transient( 'disable-notice-plugins-inactive' );
			wp_redirect( wp_get_referer() );
			exit();
		}
	}

	/**
	 * Handles Ajax request to persist notices dismissal.
	 */
	public static function dismiss_admin_notice() {
		check_ajax_referer( 'wps-bidouille-dismissible-notice' );

		if ( $_POST['number'] != 'false' ) {
			$count = ( $_POST['number'] - 1 );
			update_option( 'wps_notifs_ajax', (int) $count );
		}

		$option_name        = sanitize_text_field( $_POST['option_name'] );
		$dismissible_length = sanitize_text_field( $_POST['dismissible_length'] );
		$transient          = 0;
		if ( 'forever' != $dismissible_length ) {
			// If $dismissible_length is not an integer default to 1
			$dismissible_length = ( 0 == absint( $dismissible_length ) ) ? 1 : $dismissible_length;
			$transient          = absint( $dismissible_length ) * DAY_IN_SECONDS;
			$dismissible_length = strtotime( absint( $dismissible_length ) . ' days' );
		}
		set_site_transient( $option_name, $dismissible_length, $transient );
		wp_die();
	}

	/**
	 * Handles Ajax request count notice.
	 */
	public static function count_notif() {
	    check_ajax_referer( 'count-notifs' );

		update_option( 'wps_notifs_ajax', (int) $_POST['number'] );
		wp_die();
	}

	/**
	 *
	 * Add environment WPS in admin bar
	 *
	 * @return bool
	 */
	public static function wps_env() {
		$pf = Helpers::wps_ip_check_return_pf();
		if ( empty( $pf ) ) {
			return false;
		}

		global $wp_admin_bar;

		$wps_site_details = Helpers::_get_wps_user();

		$data = ( $wps_site_details['env'] === 'prod' ) ? 'Production' : 'Clone';

		$args = array(
			'id'    => 'wps-env',
			'title' => $data,
		);
		$wp_admin_bar->add_menu( $args );
	}

	public static function add_option_wps_display() {
		$option_name = sanitize_text_field( $_POST['option_name'] );
		if ( is_multisite() ) {
			$wps_display = get_blog_option( get_current_blog_id(), 'wps_display' );
		} else {
			$wps_display = get_option( 'wps_display' );
		}
		if ( empty( $wps_display ) ) {
			$wps_display = array();
		}
		if ( is_multisite() ) {
			update_blog_option( get_current_blog_id(), 'wps_display', array_merge( $wps_display, array( $option_name ) ) );
		} else {
			update_option( 'wps_display', array_merge( $wps_display, array( $option_name ) ) );
		}
		wp_die();
	}

	public static function delete_option_wps_display() {
		$option_name = sanitize_text_field( $_POST['option_name'] );
		if ( is_multisite() ) {
			$wps_display = get_blog_option( get_current_blog_id(), 'wps_display' );
		} else {
			$wps_display = get_option( 'wps_display' );
		}
		if ( empty( $wps_display ) ) {
			$wps_display = array();
		}
		if ( is_multisite() ) {
			update_blog_option( get_current_blog_id(), 'wps_display', array_diff( $wps_display, array( $option_name ) ) );
		} else {
			update_option( 'wps_display', array_diff( $wps_display, array( $option_name ) ) );
		}
		wp_die();
	}

	public static function wp_before_admin_bar_render() {
		global $wp_admin_bar;
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$wp_admin_bar->add_node( array(
			'parent' => false,
			'id'     => 'link-wps',
			'title'  => __( 'WPS Bidouille' ),
			'href'   => admin_url( 'admin.php?page=wps-bidouille' )
		) );

	}

	public static function install() {
		if ( is_multisite() ) {
			update_blog_option( get_current_blog_id(), 'wps_display', array(
				'wps-dashboard-server-info',
				'wps-dashboard-tools',
				'wps-dashboard-user-infos',
				'wps-dashboard-report-system',
				'wps-dashboard-mysql',
			) );
		} else {
			update_option( 'wps_display', array(
				'wps-dashboard-server-info',
				'wps-dashboard-tools',
				'wps-dashboard-user-infos',
				'wps-dashboard-report-system',
				'wps-dashboard-mysql',
			) );
		}

		update_option( 'wps_white_list', 'false' );
		update_option( 'wps_save_settings', 'true' );
	}

	public static function uninstall() {
		$wps_save_settings = get_option( 'wps_save_settings' );
		if ( $wps_save_settings == 'true' ) {
			return false;
		}

		if ( is_multisite() ) {
			delete_blog_option( get_current_blog_id(), 'wps_display' );
		} else {
			delete_option( 'wps_display' );
		}
		delete_option( 'select2_wps_posts' );
		delete_option( 'wps_archive_cpt_remove_from_cache' );
		delete_option( 'wps_cpt_remove_from_cache' );
		delete_option( 'list_post_without_cache' );
		delete_option( 'select2_wps_users' );
		delete_option( 'wps_options_tools' );
		delete_option( 'wps_white_list' );
		delete_option( 'wps_notifs_ajax' );

		delete_site_transient( 'disable-notice-db-prefix' );
		delete_site_transient( 'disable-notice-ssl' );
		delete_site_transient( 'disable-notice-check-old-plugins' );
		delete_site_transient( 'disable-notice-plugins-inactive' );
		delete_site_transient( 'disable-notice-themes-inactive' );
		delete_site_transient( 'disable-notice-wordpress-update' );
		delete_site_transient( 'disable-notice-plugins-update' );
		delete_site_transient( 'disable-notice-themes-update' );
		delete_site_transient( 'disable-notice-update-traduction' );
		delete_site_transient( 'disable-notice-wps-hide-login' );
		delete_site_transient( 'disable-notice-wps-limit-login' );
		delete_site_transient( 'disable-notice-autoupdates' );
	}

	public static function add_allow_repair_wp_config() {
	    check_ajax_referer( 'repair-db' );

		Helpers::set_wps_wp_repair_define( true );
	}

	public static function remove_allow_repair_wp_config() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! isset( $_GET['repair'] ) ) {
			return false;
		}

		if ( 1 == $_GET['repair'] || 2 == $_GET['repair'] ) {
			Helpers::set_wps_wp_repair_define( false );
		}
	}

	public static function after_plugin_row( $plugin_file, $plugin_data, $status ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		if ( ! isset( $plugin_data['slug'] ) ) {
			return false;
		}

		if ( false === ( $wps_api_plugin_information = get_transient( 'wps_api_plugin_information_' . $plugin_file ) ) ) {
			$wps_api_plugin_information = plugins_api( 'plugin_information', array(
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
			set_transient( 'wps_api_plugin_information_' . $plugin_file, $wps_api_plugin_information, MONTH_IN_SECONDS );
		}

		if ( is_wp_error( $wps_api_plugin_information ) ) {
			return false;
		}

		if ( isset( $wps_api_plugin_information->last_updated ) ) {
			$last_updated_timestamp = strtotime( $wps_api_plugin_information->last_updated );
			if ( $last_updated_timestamp > 0 && ( $last_updated_timestamp < ( time() - ( 730 * 24 * 60 * 60 ) ) ) ) {
				$shortMsg = sprintf( __( 'This plugin appears to be abandoned (updated %s).', 'wps-bidouille' ), date_i18n( get_option( 'date_format' ), $last_updated_timestamp ) );
				if ( is_network_admin() ) {
					$active_class = is_plugin_active_for_network( $plugin_file ) ? ' active' : '';
				} else {
					$active_class = is_plugin_active( $plugin_file ) ? ' active' : '';
				} ?>
                <script language="javascript" type="text/javascript">
                    var api_slug = '<?php echo esc_js( $wps_api_plugin_information->slug ); ?>';
                    jQuery('[data-slug=' + api_slug + ']').find('th').css('box-shadow', 'none');
                    jQuery('[data-slug=' + api_slug + ']').find('td').css('box-shadow', 'none');
                </script>
				<?php
				echo '<tr class="plugin-update-tr wps-bidouille-alert-old-plugin ' . $active_class . '"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>' . $shortMsg . '</p></div></td></tr>';
			}
		}
	}

	public static function all_plugins( $plugins ) {
		if ( empty( $plugins ) ) {
			return $plugins;
		}

		if ( isset( $_GET ) && isset( $_GET['plugin_status'] ) && $_GET['plugin_status'] === 'all' && isset( $_GET['wps_bidouille_old_plugin'] ) && $_GET['wps_bidouille_old_plugin'] == 'true' ) {

			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			foreach ( $plugins as $plugin_file => $data ) {

				$plugin = explode( '/', $plugin_file );

				$api = plugins_api( 'plugin_information', array(
					'slug'   => reset( $plugin ),
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

				if ( is_wp_error( $api ) ) {
					unset( $plugins[ $plugin_file ] );
					continue;
				}

				$last_updated_timestamp = strtotime( $api->last_updated );
				if ( $last_updated_timestamp > 0 && ( $last_updated_timestamp > ( time() - ( 730 * 24 * 60 * 60 ) ) ) ) {
					unset( $plugins[ $plugin_file ] );
				}
			}
		}

		return $plugins;
	}

	/**
	 * @param $plugin_file
	 * @param $deleted
	 */
	public static function deleted_plugin( $plugin_file, $deleted ) {
		delete_transient( 'wps_old_plugin' );
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public static function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=wps-bidouille' ) . '">' . __( 'Settings' ) . '</a>' );

		return $links;
	}

	public static function admin_footer() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( false === strpos( $current_screen->base, 'wps-bidouille' ) ) {
			return false;
		}

		echo "<script>
            jQuery( 'a.wc-rating-link' ).click( function() {
                jQuery.post( '" . admin_url( 'admin-ajax.php', 'relative' ) . "', { action: 'wpsbidouille_rated', _ajax_nonce: jQuery( this ).data('nonce') } );
                jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
            });</script>";
	}

	public static function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $footer_text;
		}

		$current_screen = get_current_screen();

		if ( false === strpos( $current_screen->base, 'wps-bidouille' ) ) {
			return $footer_text;
		}

		if ( ! get_option( 'wpsbidouille_admin_footer_text_rated' ) ) {
			$footer_text = sprintf(
				__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'wps-bidouille' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'WPS Bidouille', 'wps-bidouille' ) ),
				'<a href="https://wordpress.org/support/plugin/wps-bidouille/reviews?rate=5#new-post" target="_blank" class="wc-rating-link" data-nonce="' . wp_create_nonce( 'wpsbidouillerated' ) . '" data-rated="' . esc_attr__( 'Thanks :)', 'wps-bidouille' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}

		return $footer_text;
	}

	/**
	 * Triggered when clicking the rating footer.
	 */
	public static function wpsbidouille_rated() {
	    check_ajax_referer( 'wpsbidouillerated' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( - 1 );
		}
		update_option( 'wpsbidouille_admin_footer_text_rated', 1 );
		wp_die();
	}
}
