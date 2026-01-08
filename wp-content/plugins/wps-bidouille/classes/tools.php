<?php

namespace WPS\WPS_Bidouille;

class Tools {

	use Singleton;

	protected function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_page', array( __CLASS__, 'admin_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'clear_all_sessions' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_expired_transients' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_spam_comments' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_trashed_comments' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_revisions' ) );
		add_action( 'admin_init', array( __CLASS__, 'reinitialize' ) );

		add_action( 'admin_init', array( __CLASS__, 'register_wps_settings_tools' ) );
		add_action( 'init', array( __CLASS__, 'wpinit' ) );

		add_filter( 'wp_sitemaps_add_provider', array(
			__CLASS__,
			'remove_author_category_pages_from_sitemap'
		), 10, 2 );

		add_action( 'wp_ajax_save_settings_wps', array( __CLASS__, 'wp_ajax_save_settings_wps' ) );
	}

	/**
	 * Admin menu WPServeur
	 */
	public static function admin_menu() {
		add_submenu_page(
			'wps-bidouille',
			__( 'Tools', 'wps-bidouille' ),
			__( 'Tools', 'wps-bidouille' ),
			'manage_options',
			'wps-bidouille-tools',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function admin_page() {
		include( WPS_BIDOUILLE_DIR . 'admin_page/tools.php' );
	}

	public static function clear_all_sessions() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'clear_sessions' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'clear_all_sessions' ) {
			return false;
		}

		$users = get_users( array( 'exclude' => array( get_current_user_id() ) ) );

		if ( $users ) {
			foreach ( $users as $user ) {
				$sessions = \WP_Session_Tokens::get_instance( $user->ID );
				$sessions->destroy_all();
			}
		}

		wp_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Delete expired transients.
	 *
	 * Deletes all expired transients. The multi-table delete syntax is used.
	 * to delete the transient record from table a, and the corresponding.
	 * transient_timeout record from table b.
	 *
	 * Based on code inside core's upgrade_network() function.
	 *
	 */
	public static function delete_expired_transients() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'clear_expired_transient' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'clear_expired_transient' ) {
			return false;
		}

		global $wpdb;

		$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
		$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

		$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
		AND b.option_value < %d";
		$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) );

		$nb_expired_transients = absint( $rows + $rows2 );

		wp_redirect( wp_get_referer() );
		exit;
	}

	public static function delete_spam_comments() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'spam_comments' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'spam_comments' ) {
			return false;
		}

		global $wpdb;

		$query = $wpdb->get_col( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = 'spam'" );
		if ( $query ) {
			$number = 0;
			foreach ( $query as $id ) {
				$number += (int) wp_delete_comment( intval( $id ), true );
			}

			$nb_spam_comments = $number;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}

	public static function delete_trashed_comments() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'trashed_comments' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'trashed_comments' ) {
			return false;
		}

		global $wpdb;

		$query = $wpdb->get_col( "SELECT comment_ID FROM $wpdb->comments WHERE (comment_approved = 'trash' OR comment_approved = 'post-trashed')" );
		if ( $query ) {
			$number = 0;
			foreach ( $query as $id ) {
				$number += (int) wp_delete_comment( intval( $id ), true );
			}

			$nb_trashed_comments = $number;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}

	public static function delete_revisions() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'revisions' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'revisions' ) {
			return false;
		}

		global $wpdb;

		$query = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'revision'" );
		if ( $query ) {
			$number = 0;
			foreach ( $query as $id ) {
				$number += (int) wp_delete_post_revision( intval( $id ) );
			}

			$nb_revisions = $number;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}

	public static function reinitialize() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'reinitialize' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'reinitialize' ) {
			return false;
		}

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

		delete_option( 'select2_wps_posts' );
		delete_option( 'wps_cpt_remove_from_cache' );
		delete_option( 'list_post_without_cache' );
		delete_option( 'select2_wps_users' );
		delete_option( 'wps_options_tools' );
		delete_option( 'wps_notifs_ajax' );
		update_option( 'wps_white_list', false );

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

		wp_redirect( admin_url( 'admin.php?page=wps-bidouille' ) );
		exit;
	}

	public static function register_wps_settings_tools() {
		register_setting( 'wps-settings-tools', 'wps_options_tools' );
	}

	public static function wpinit() {
		$options_tools = get_option( 'wps_options_tools' );

		if ( empty( $options_tools ) ) {
			return false;
		}

		foreach ( $options_tools as $option => $value ) {
			if ( 'checked_users_remove_sitemap' === $option ) {
				continue;
			}
			$function = 'wps_' . $option;
			self::$function();
		}
	}


	public static function remove_author_category_pages_from_sitemap( $provider, $name ) {
		$options_tools = get_option( 'wps_options_tools' );

		if ( empty( $options_tools ) ) {
			return false;
		}

		foreach ( $options_tools as $option => $value ) {
			if ( 'checked_users_remove_sitemap' === $option ) {
				if ( 'users' === $name ) {
					return false;
				}
				break;
			}
		}

		return $provider;
	}

	/**
	 * Deactivate WordPress version
	 */
	public static function wps_wp_version() {
		remove_action( 'wp_head', 'wp_generator' );
	}

	/**
	 * Deactivate Windows Live Writer Manifest Link
	 */
	public static function wps_link_manifest() {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}

	/**
	 * Disable the emoji's
	 * @see https://www.keycdn.com/blog/website-performance-optimization/
	 * @see https://fr.wordpress.org/plugins/disable-emojis/
	 * @return void
	 */
	public static function wps_disable_emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( __CLASS__, 'disable_emojis_tinymce' ) );
		add_filter( 'wp_resource_hints', array( __CLASS__, 'disable_emojis_remove_dns_prefetch' ), 10, 2 );
	}

	/**
	 * Filter function used to remove the tinymce emoji plugin.
	 *
	 * @param array $plugins
	 *
	 * @return   array  Difference betwen the two arrays
	 */
	public static function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		} else {
			return array();
		}
	}

	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @param array $urls URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for.
	 *
	 * @return array                 Difference betwen the two arrays.
	 */
	public static function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {

		if ( 'dns-prefetch' != $relation_type ) {
			return $urls;
		}

		// Strip out any URLs referencing the WordPress.org emoji location
		$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
		foreach ( $urls as $key => $url ) {
			if ( strpos( $url, $emoji_svg_url_bit ) !== false ) {
				unset( $urls[ $key ] );
			}
		}

		return $urls;
	}

	public static function wps_deactivate_revisions() {
		add_filter( 'wp_revisions_to_keep', array( __CLASS__, 'wps_restrict_number_revision' ), 10, 2 );
	}

	public static function wps_restrict_number_revision( $num, $post ) {
		$num = 5;

		return $num;
	}

	public static function wps_delete_h1_editor() {
		add_filter( 'tiny_mce_before_init', array( __CLASS__, 'modify_editor_buttons' ) );
	}

	public static function wps_last_jquery() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'replace_core_jquery_version' ) );
	}

	public static function replace_core_jquery_version() {
		if ( is_admin() ) {
			return false;
		}

		global $wp_version;
		if ( version_compare( $wp_version, '5.3', '>' ) ) {
			return false;
		}

		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', WPS_BIDOUILLE_URL . 'assets/js/jquery/jquery.min.js', array(), '3.4.1' );
	}

	public static function wps_fast_woocommerce() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'manage_woocommerce_styles' ), 99 );
	}

	public static function manage_woocommerce_styles() {
		//remove generator meta tag
		remove_action( 'wp_head', array( $GLOBALS['woocommerce'], 'generator' ) );

		//first check that woo exists to prevent fatal errors
		if ( function_exists( 'is_woocommerce' ) ) {
			//dequeue scripts and styles
			if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
				wp_dequeue_style( 'woocommerce_frontend_styles' );
				wp_dequeue_style( 'woocommerce_fancybox_styles' );
				wp_dequeue_style( 'woocommerce_chosen_styles' );
				wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
				wp_dequeue_script( 'wc_price_slider' );
				wp_dequeue_script( 'wc-single-product' );
				wp_dequeue_script( 'wc-add-to-cart' );
				wp_dequeue_script( 'wc-cart-fragments' );
				wp_dequeue_script( 'wc-checkout' );
				wp_dequeue_script( 'wc-add-to-cart-variation' );
				wp_dequeue_script( 'wc-single-product' );
				wp_dequeue_script( 'wc-cart' );
				wp_dequeue_script( 'wc-chosen' );
				wp_dequeue_script( 'woocommerce' );
				wp_dequeue_script( 'prettyPhoto' );
				wp_dequeue_script( 'prettyPhoto-init' );
				wp_dequeue_script( 'jquery-blockui' );
				wp_dequeue_script( 'jquery-placeholder' );
				wp_dequeue_script( 'fancybox' );
				wp_dequeue_script( 'jqueryui' );
			}
		}
	}

	public static function wps_fast_contactform7() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'contact-form-7' ) ) {
			add_action( 'wp_print_styles', array( __CLASS__, 'remove_cf7_css' ) );
			add_action( 'wp_print_scripts', array( __CLASS__, 'remove_cf7_js' ) );
		}
	}

	public static function remove_cf7_css() {
		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wp_deregister_style( 'contact-form-7' );
		}
	}

	public static function remove_cf7_js() {
		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wp_deregister_script( 'contact-form-7' );
		}
	}

	/**
	 * Remove H1 from the WordPress editor.
	 * H1 is only for page titles
	 *
	 * @param array $init The array of editor settings
	 *
	 * @return  array            The modified edit settings
	 */
	public static function modify_editor_buttons( $init ) {
		$init['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre;';

		return $init;
	}

	/**
	 * Hide connections errors in wp-login.php
	 */
	public static function wps_error_connexion() {
		add_filter( 'login_errors', '__return_empty_string' );
	}

	/**
	 * Deactivate REST-API
	 * @return void
	 */
	public static function wps_deactivate_rest_api() {
		if ( is_user_logged_in() ) {
			return false;
		}

		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		// WordPress 4.7+ disables the REST API via authentication short-circuit.
		// For versions of WordPress < 4.7, disable the REST API via filters
		if ( version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) {
			// Load the primary Disable_REST_API class
			require_once( plugin_dir_path( __FILE__ ) . 'disable-rest-api/disable-rest-api.php' );
			new \WPS_Disable_REST_API();
		} else {

			remove_action( 'init', 'rest_api_init' );
			remove_action( 'parse_request', 'rest_api_loaded' );

			remove_action( 'auth_cookie_malformed', 'rest_cookie_collect_status' );
			remove_action( 'auth_cookie_expired', 'rest_cookie_collect_status' );
			remove_action( 'auth_cookie_bad_username', 'rest_cookie_collect_status' );
			remove_action( 'auth_cookie_bad_hash', 'rest_cookie_collect_status' );
			remove_action( 'auth_cookie_valid', 'rest_cookie_collect_status' );

			// Filters for WP-API version 1.x
			add_filter( 'json_enabled', '__return_false' );
			add_filter( 'json_jsonp_enabled', '__return_false' );

			// Filters for WP-API version 2.x
			add_filter( 'rest_enabled', '__return_false' );
			add_filter( 'rest_jsonp_enabled', '__return_false' );
		}

	}

	/**
	 * Disable REST API user endpoints
	 * @see https://fr.wordpress.org/plugins/smntcs-disable-rest-api-user-endpoints/
	 * @return void
	 */
	public static function wps_disable_rest_api_users() {
		if ( ! is_user_logged_in() ) {
			add_filter( 'rest_endpoints', function ( $endpoints ) {
				if ( isset( $endpoints['/wp/v2/users'] ) ) {
					unset( $endpoints['/wp/v2/users'] );
				}
				if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
					unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
				}

				return $endpoints;
			} );
		}
	}

	/**
	 * Supprime le flux RSS général
	 */
	public static function wps_feed_link() {
		add_filter( 'feed_links_show_posts_feed', '__return_false' );
	}

	/**
	 * Supprime le flux RSS des catégories
	 */
	public static function wps_comments_feed() {
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
	}

	public static function wps_add_medium_large() {
		add_filter( 'image_size_names_choose', array( __CLASS__, 'add_medium_large' ) );
	}

	/**
	 * Add medium format `medium_large` to media in admin
	 * This format is by default since version 4.4 but not appear in media
	 *
	 * @param array $format Format list
	 *
	 * @return array $format
	 */
	public static function add_medium_large( $format ) {
		$format['medium_large'] = __( 'Medium Large' );

		return $format;
	}

	/**
	 * Deactivate author page and link page
	 * @see https://blog.futtta.be/2017/08/28/no-author-pages/
	 * @return void
	 */
	public static function wps_deactivate_author_page_and_link() {
		add_action( 'template_redirect', array( __CLASS__, 'remove_author_pages_page' ) );
		add_filter( 'author_link', array( __CLASS__, 'remove_author_pages_link' ) );
	}

	/**
	 * Remove Author Pages and Links
	 */
	public static function remove_author_pages_page() {
		if ( is_author() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	}

	/**
	 * @param $content
	 *
	 * @return mixed|void
	 */
	public static function remove_author_pages_link( $content ) {
		return get_option( 'home' );
	}

	public static function wps_tools_sanitize_file_name() {
		add_filter( 'sanitize_file_name_chars', array( __CLASS__, 'wps_sanitize_file_name_chars' ), 10, 1 );
		add_filter( 'sanitize_file_name', array( __CLASS__, 'wps_sanitize_file_name' ), 10, 1 );
	}

	public static function wps_sanitize_file_name_chars( $special_chars = array() ) {
		$special_chars = array_merge( array( '’', '‘', '“', '”', '«', '»', '‹', '›', '—', '€' ), $special_chars );

		return $special_chars;
	}

	public static function wps_sanitize_file_name( $file_name = '' ) {
		// Empty filename
		if ( empty( $file_name ) ) {
			return $file_name;
		}
		// get extension
		preg_match( '/\.[^\.]+$/i', $file_name, $ext );
		// No extension, go out ?
		if ( ! isset( $ext[0] ) ) {
			return $file_name;
		}
		// Get only first part
		$ext = $ext[0];
		// work only on the filename without extension
		$file_name = str_replace( $ext, '', $file_name );
		// only lowercase
		// replace _ by -
		$file_name = sanitize_title( $file_name );
		// remove accents
		$file_name = str_replace( '_', '-', $file_name );

		return $file_name . $ext;
	}

	public static function wp_ajax_save_settings_wps() {
		check_ajax_referer( 'save-settings' );

		$checked = sanitize_text_field( $_POST['checked'] );
		update_option( 'wps_save_settings', $checked );
		wp_die();
	}
}