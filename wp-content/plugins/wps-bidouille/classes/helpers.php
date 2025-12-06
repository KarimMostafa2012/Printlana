<?php

namespace WPS\WPS_Bidouille;

class Helpers {

    use Singleton;

	protected function init() {
		add_action( 'init', array( __CLASS__, 'check_limit' ) );
	}

	public static function check_limit() {
		global $wps_memory;
		$wps_memory['limit'] = ini_get( 'memory_limit' );
	}

	/**
	 *
	 * Return memory infos server
	 *
	 * @return mixed
	 */
	public static function _get_memory() {
		global $wps_memory;

		return array(
			'server_info'       => $_SERVER['SERVER_SOFTWARE'],
			'memory_limit'      => $wps_memory['limit'],
			'memory_usage'      => function_exists( 'memory_get_usage' ) ? round( memory_get_usage(), 2 ) : 0,
			'max_input_vars'    => ini_get( 'max_input_vars' ),
			'post_max_size'    => ini_get('post_max_size'),
			'upload_max_filesize'    => ini_get('upload_max_filesize'),
			'allow_url_fopen'   => ini_get( 'allow_url_fopen' ),
			'allow_url_include' => ini_get( 'allow_url_include' ),
		);
	}

	/**
	 *
	 * Check if user is white label
	 *
	 * @return bool
	 */
	public static function is_user_white_label() {
		$wps_white_list = get_option( 'wps_white_list' );
		if ( empty( $wps_white_list ) || 'false' == $wps_white_list ) {
			return false;
		}

		if ( $wps_white_list == false ) {
			return false;
		}

		$wps_users = get_option( 'select2_wps_users' );
		if ( empty( $wps_users ) ) {
			return true;
		}

		if ( in_array( get_current_user_id(), $wps_users ) ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 * Check if domain is temporary url
	 *
	 * @return string
	 */
	public static function wps_check_temporary_url() {
		$tempoUrl        = parse_url( get_site_url() );
		$tempoUrlMessage = '<div class="wps-tempo-url">' . __( 'Warning, you are currently using a temporary URL, know that the cache is not active on it.', 'wps-bidouille' ) . ' <a href="https://www.wpserveur.net/wpserveur-domaines-et-adresses-email/?refwps=14&campaign=wpsbidouille" target="_blank" class="wps-tempo-url-btn">' . __( 'How to obtain a domain name', 'wps-bidouille' ) . '</a></div>';
		if ( preg_match( '#[.]pf[0-9][.]wpserveur\.net$#', $tempoUrl['host'] ) ) {
			return $tempoUrlMessage;
		}
	}

	/**
	 *
	 * Check if WordPress core, themes and plugins use last version
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function wps_update( $name = 'wordpress' ) {
		global $wp_version;
		$update_wp  = wp_get_update_data();
		$basic_name = $name;

		$datas   = $update_wp['counts'][ $name ];
		$class_a = $href = $is_dismiss = '';

		if ( $name === 'wordpress' ) {
			if ( $datas < 1 ) {

				if ( ! self::is_admin_notice_active( 'disable-notice-' . $basic_name . '-update' ) ) {
					return false;
				}

				$color      = 'green';
				$dashicon   = 'thumbs-up';
				$msg_1      = __( 'Congratulations, your <strong>WordPress</strong> is up to date!', 'wps-bidouille' ) . ' <span class="wps-info-ok">' . $wp_version . '</span>';
				$class_a    = 'wps-h-btn-disabled';
				$fa_icon    = 'shield-check';
				$is_dismiss = 'is-dismissible';
				$msg_2      = __( 'No update available', 'wps-bidouille' );
			} else {
				delete_site_transient( 'disable-notice-' . $basic_name . '-update' );
				$color    = 'red';
				$dashicon = 'thumbs-down';
				$msg_1    = __( 'Your <strong>WordPress</strong> requires an update!', 'wps-bidouille' ) . ' <span class="wps-info-error">' . $wp_version . '</span>';
				$class_a  = 'wps-btn-delete';
				$href     = 'href="' . network_admin_url( 'update-core.php' ) . '"';
				$fa_icon  = 'info';
				$msg_2    = __( 'Update available', 'wps-bidouille' );
			}
		} else {
			if ( $name !== 'plugins' ) {
				$url  = network_admin_url( 'update-core.php' );
				$name = __( 'themes', 'wps-bidouille' );
			} else {
				$name = __( 'plugins', 'wps-bidouille' );
				$url  = network_admin_url( 'plugins.php?plugin_status=upgrade' );
			}

			if ( $datas < 1 ) {

				if ( ! \WPS\WPS_Bidouille\Helpers::is_admin_notice_active( 'disable-notice-' . $basic_name . '-update' ) ) {
					return false;
				}

				$color      = 'green';
				$dashicon   = 'thumbs-up';
				$msg_1      = sprintf( __( 'Congratulations, all you <strong>%s</strong> are up to date!', 'wps-bidouille' ), $name );
				$class_a    = 'wps-h-btn-disabled';
				$fa_icon    = 'shield-check';
				$is_dismiss = 'is-dismissible';
				$msg_2      = __( 'No update available', 'wps-bidouille' );
			} else {
				delete_site_transient( 'disable-notice-' . $basic_name . '-update' );
				$color    = 'red';
				$dashicon = 'thumbs-down';
				$msg_1    = sprintf( __( 'One or more of your <strong>%s</strong> require an update!', 'wps-bidouille' ), $name );
				$class_a  = 'wps-btn-delete';
				$href     = 'href="' . $url . '"';
				$fa_icon  = 'info';
				$msg_2    = __( 'Update available', 'wps-bidouille' );
			}
		}

		return '<div id="' . $basic_name . '" class="wps-updates wpsnotice wps-border-' . $color . ' ' . $is_dismiss . '" data-dismissible="disable-notice-' . $basic_name . '-update-forever"><div class="wps-up-left"><i class="fas fa-' . $dashicon . ' wps-icon-' . $color . '" data-fa-transform="grow-8"></i> ' . $msg_1 . '</div><div class="wps-up-right"><a class="' . esc_attr( $class_a ) . '" ' . esc_url( $href ) . '><i class="fal fa-' . $fa_icon . '" data-fa-transform="grow-6"></i> ' . $msg_2 . '</a></div></div>';
	}

	/**
	 * Check plugins
	 */
	public static function wps_numbers_plugins() {
		if ( is_multisite() ) {
			return false;
		}

		$plugins        = get_plugins();
		$active_plugins = 0;
		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $key => $plugin ) {
				if ( is_plugin_active_for_network( $key ) || is_plugin_active( $key ) ) {
					$active_plugins ++;
				}
			}
		}
		$result           = count( $plugins );
		$inactive_plugins = ( $result - $active_plugins );

		$color         = 'green';
		$class_dismiss = '';
		$class         = 'shield-check wps-icon-green';
		$msg_1         = __( 'No currently installed plugin', 'wps-bidouille' );
		$msg_2         = '<a class="wps-h-btn-disabled"><i class="fal fa-shield-check" data-fa-transform="grow-4"></i>' . __( 'No plugin', 'wps-bidouille' ) . '</a>';

		if ( $result == 1 ) {
			if ( $active_plugins == 1 ) {

				if ( ! self::is_admin_notice_active( 'disable-notice-plugins-inactive' ) ) {
					return false;
				}

				$class_dismiss = 'is-dismissible';
				$msg_1         = __( 'You have <strong>1 plugin</strong> installed and activated!', 'wps-bidouille' );
				$msg_2         = '<a class="wps-h-btn-disabled"><i class="fal fa-shield-check" data-fa-transform="grow-4"></i>' . __( 'No plugin to delete', 'wps-bidouille' ) . '</a>';
			} else {
				$color = 'yellow';
				$class = 'exclamation-triangle wps-icon-yellow';
				$msg_1 = __( 'You have <strong>1 plugin</strong> installed including <strong>1 plugin</strong> inactive!', 'wps-bidouille' );
				$msg_2 = '<a href="' . admin_url( 'plugins.php?wps-delete-plugins-unuse=true&nonce=' . wp_create_nonce( 'delete-plugin-unuse' ) ) . '" class="wps-btn-delete"><i class ="fal fa-trash-alt" data-fa-transform="grow-4"></i>' . __( 'Delete plugin', 'wps-bidouille' ) . '</a>';
			}
		} else {
			if ( $inactive_plugins == 0 ) {

				if ( ! self::is_admin_notice_active( 'disable-notice-plugins-inactive' ) ) {
					return false;
				}

				$class_dismiss = 'is-dismissible';
				$msg_1         = sprintf( __( 'You have <strong>%s plugins</strong> installed and activated!', 'wps-bidouille' ), $result );
				$msg_2         = '<a class="wps-h-btn-disabled"><i class="fal fa-shield-check" data-fa-transform="grow-4"></i>' . __( 'No plugin to delete', 'wps-bidouille' ) . '</a>';
			} else {
				$color = 'yellow';
				$class = 'exclamation-triangle wps-icon-yellow';

				if ( $inactive_plugins == 1 ) {
					$msg_1 = sprintf( __( 'You have <strong>%s plugins</strong> installed including <strong>%s plugin</strong> inactive!', 'wps-bidouille' ), $result, $inactive_plugins );
				} else {
					$msg_1 = sprintf( __( 'You have <strong>%s plugins</strong> installed including <strong>%s plugins</strong> inactive!', 'wps-bidouille' ), $result, $inactive_plugins );
				}

				$msg_2 = '<a href="' . admin_url( 'plugins.php?wps-delete-plugins-unuse=true&nonce=' . wp_create_nonce( 'delete-plugin-unuse' ) ) . '" class="wps-btn-delete"><i class ="fal fa-trash-alt"   data-fa-transform="grow-4"></i>';
				if ( $inactive_plugins == 1 ) {
					$msg_2 .= __( 'Delete plugin', 'wps-bidouille' );
				} else {
					$msg_2 .= __( 'Delete plugins', 'wps-bidouille' );
				}
				$msg_2 .= '</a>';
			}
		} ?>

        <div class="wps-updates wpsnotice wps-border-<?php echo esc_attr( $color ); ?> <?php echo esc_attr( $class_dismiss ); ?>"
             data-dismissible="disable-notice-plugins-inactive-forever">
            <div class="wps-up-left">
                <i class="fas fa-<?php echo esc_attr( $class ); ?>"
                   data-fa-transform="grow-8"></i> <?php echo $msg_1; ?>
            </div>
            <div class="wps-up-right"><?php echo $msg_2; ?></div>
        </div>
		<?php
	}

	/**
	 * Check themes
	 */
	public static function wps_number_themes() {
		if ( is_multisite() ) {
			return false;
		}

		$themes              = wp_get_themes();
		$activeTheme         = wp_get_theme();
		$result              = count( $themes );
		$themesInactive      = $result - 1;
		$themesInactiveChild = $result - 2;

		$msg_1 = $class_dismiss = '';
		$color = 'green';
		$class = 'shield-check wps-icon-green';

		if ( $result == 1 ) {

			if ( ! self::is_admin_notice_active( 'disable-notice-themes-inactive' ) ) {
				return false;
			}

			$class_dismiss = 'is-dismissible';
			$msg_1         = __( 'You have <strong>1 theme</strong> installed and activated!', 'wps-bidouille' );
			$msg_2         = '<a class="wps-h-btn-disabled"><i class="fal fa-shield-check" data-fa-transform="grow-4"></i> ' . __( 'No themes to delete', 'wps-bidouille' ) . '</a>';
		} elseif ( $result == 2 ) {
			if ( $activeTheme->get( 'Template' ) != "" ) {

				if ( ! self::is_admin_notice_active( 'disable-notice-themes-inactive' ) ) {
					return false;
				}

				$class_dismiss = 'is-dismissible';
				$msg_1         = __( 'You have <strong>2 themes</strong> installed and activated!', 'wps-bidouille' );
				$msg_2         = '<a class="wps-h-btn-disabled"><i class="fal fa-shield-check" data-fa-transform="grow-4"></i> ' . __( 'No themes to delete', 'wps-bidouille' ) . '</a>';
			} else {
				$class = 'exclamation-triangle wps-icon-yellow';
				$color = 'yellow';
				$msg_1 = __( 'You have <strong>2 themes</strong> installed including <strong>1 theme</strong> inactive!', 'wps-bidouille' );
				$msg_2 = '<a href="' . admin_url( 'themes.php?wps-delete-themes-unuse=true&nonce=' . wp_create_nonce( 'delete-theme-unuse' ) ) . '" class="wps-btn-delete"><i class ="fal fa-trash-alt" data-fa-transform="grow-4"></i>' . __( 'Delete theme', 'wps-bidouille' ) . '</a>';
			}
		} else {
			if ( $activeTheme->get( 'Template' ) != "" ) {
				if ( $themesInactiveChild > 1 ) {
					$msg_1 = sprintf( __( 'You have <strong>%s themes</strong> installed including <strong>%s themes</strong> inactive!', 'wps-bidouille' ), $result, $themesInactiveChild );
				} else {
					$msg_1 = sprintf( __( 'You have <strong>%s themes</strong> installed including <strong>%s theme</strong> inactive!', 'wps-bidouille' ), $result, $themesInactiveChild );
				}
			} else {
				if ( $themesInactive > 1 ) {
					$msg_1 = sprintf( __( 'You have <strong>%s themes</strong> installed including <strong>%s themes</strong> inactive!', 'wps-bidouille' ), $result, $themesInactive );
				} else {
					$msg_1 = sprintf( __( 'You have <strong>%s themes</strong> installed including <strong>%s theme</strong> inactive!', 'wps-bidouille' ), $result, $themesInactive );
				}
			}
			$class = 'exclamation-triangle wps-icon-yellow';
			$color = 'yellow';
			$msg_2 = '<a href="' . admin_url( 'themes.php?wps-delete-themes-unuse=true&nonce=' . wp_create_nonce( 'delete-theme-unuse' ) ) . '" class="wps-btn-delete"><i class ="fal fa-trash-alt"  data-fa-transform="grow-4"></i>' . ( ( $themesInactive == 1 ) ? __( 'Delete theme', 'wps-bidouille' ) : __( 'Delete themes', 'wps-bidouille' ) ) . '</a>';
		}

		if ( empty( $msg_1 ) ) {
			return false;
		} ?>

        <div class="wps-updates wpsnotice wps-border-<?php echo esc_attr( $color ); ?> <?php echo esc_attr( $class_dismiss ); ?>"
             data-dismissible="disable-notice-themes-inactive-forever">
            <div class="wps-up-left">
                <i class="fas fa-<?php echo esc_attr( $class ); ?>"
                   data-fa-transform="grow-8"></i> <?php echo $msg_1; ?>
            </div>
            <div class="wps-up-right"><?php echo $msg_2; ?></div>
        </div>
		<?php
	}

	/**
	 * Delete themes unuse in dashboard
	 */
	public static function delete_unuse_themes() {
		$themes = wp_prepare_themes_for_js();

		if ( is_multisite() && ! current_user_can( 'manage_network_themes' ) ) {
			return false;
		}

		foreach ( $themes as $theme ) {

			if ( $theme['active'] === true ) {
				continue;
			}

			if ( ! isset( $theme['actions']['delete'] ) ) {
				continue;
			}

			delete_theme( $theme['id'] );

		}
	}

	/**
	 * Delete plugins unuse in dashboard
	 */
	public static function delete_unuse_plugins() {
		if ( is_multisite() && ! current_user_can( 'manage_network_plugins' ) ) {
			return false;
		}

		$plugins = get_plugins();

		if ( empty( $plugins ) ) {
			return false;
		}

		$plugins_delete = array();
		foreach ( $plugins as $path_plugin => $plugin ) {
			if ( ! is_plugin_inactive( $path_plugin ) || is_plugin_active_for_network( $path_plugin ) ) {
				continue;
			}

			$plugins_delete[] = $path_plugin;

		}

		delete_plugins( $plugins_delete );
	}

	/**
	 * Display a WPS help tip.
	 *
	 * @param  string $tip Help tip text
	 * @param  bool $allow_html Allow sanitized HTML if true or escape
	 *
	 * @return string
	 */
	public static function wps_help_tip( $tip, $allow_html = false ) {
		if ( $allow_html ) {
			$tip = wc_sanitize_tooltip( $tip );
		} else {
			$tip = esc_attr( $tip );
		}

		return '<span class="wps-help-tip" data-tip="' . $tip . '"></span>';
	}

	/**
	 * Is admin notice active?
	 *
	 * @param string $arg data-dismissible content of notice.
	 *
	 * @return bool
	 */
	public static function is_admin_notice_active( $arg ) {
		$array       = explode( '-', $arg );
		$option_name = implode( '-', $array );
		$db_record   = get_site_transient( $option_name );

		if ( 'forever' == $db_record ) {
			return false;
		} elseif ( absint( $db_record ) >= time() ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 *
	 * Return PF current user
	 *
	 * @return null|string|string[]
	 */
	public static function wps_ip_check_return_pf() {
		$pf        = '';
		$host_name = gethostname();
		if ( strpos( $host_name, 'wps' ) !== false ) {

		    if ( false !== strpos( $host_name, 'wpserveur' ) ) {
			    $pf = 'pf1';
			    return $pf;
            }

			$pf = preg_replace( "/[^0-9]/", '', $host_name );
			$pf = 'pf' . $pf;
		}

		return $pf;
	}

	/**
	 *
	 * Return urls WPServeur
	 *
	 * @return array
	 */
	public static function _ip_check() {
		$pf    = self::wps_ip_check_return_pf();
		$links = array(
			'phpmyadmin' => 'https://allersurmabase-' . $pf . '.wpserveur.net/',
			'console'    => 'https://console.wpserveur.net/',
			'monitoring' => 'https://monitoring.' . $pf . '.wpserveur.net/',
		);

		if ( $pf == 'pf1' ) {
			$links['monitoring'] = 'https://monitoring-' . $pf . '.wpserveur.net/';
		}

		$links = array_merge( $links, self::_get_wps_user() );

		return $links;
	}

	/**
	 *
	 * Get datas users WPServeur
	 *
	 * @return array
	 */
	public static function _get_wps_user() {
		$_path   = realpath( null );
		$path    = explode( '/', $_path );
		$details = array(
			'login' => $path[3],
			'env'   => $path[4],
			'wp_id' => $path[5],
		);

		array_pop( $path );

		$details['path'] = implode( '/', $path );

		return $details;
	}

	/**
	 *
	 * Curl headers
	 *
	 * @param $url
	 * @param bool $purge_cache
	 *
	 * @return string
	 */
	public static function _curl_headers( $url, $purge_cache = false ) {

		if ( $purge_cache === true && class_exists( 'VarnishPurger' ) ) {
			$varnish = new \VarnishPurger;
			$varnish->executePurge();
		}

		$response  = wp_remote_get( $url, array( 'user-agent' => 'WPServeur/1.0 (WPSbidouille check cache)' ) );
		$http_code = wp_remote_retrieve_response_code( $response );
		$header    = wp_remote_retrieve_header( $response, 'x-cache-status' );

		if ( is_wp_error( $response ) ) {
			return '<div class="wps-result-cache">' . $response->get_error_message() . '</div>';
		}

		if ( $http_code == 301 ) {
			$return        = 'HTTP/1.1 301 Moved Permanently';
			$icn_http_code = 'level-down-alt';
		} else {
			$return        = 'HTTP/1.1 200 OK';
			$icn_http_code = 'check-circle';
		}
		$string = '<span class="wps-http-response"><i class="fas fa-' . $icn_http_code . '"></i> HTTP response : ' . $return . '</span>';
		if ( $header ) {
			if ( $header === 'HIT' ) {
				$icn_cache_code = 'thumbs-up';
				$cache          = __( 'Cached', 'wps-bidouille' );
			} else {
				$icn_cache_code = 'thumbs-down';
				$cache          = __( 'Not cached', 'wps-bidouille' );
			}
			$string .= '<span class="wps-cache-status"><i class="fas fa-' . $icn_cache_code . '"></i> x-cache-status : ' . $header . ' (' . $cache . ')</span>';
		} else {
			$icn_cache_code = 'bug';
			$string         .= '<span class="wps-cache-status"><i class="fas fa-' . $icn_cache_code . '"></i> x-cache-status : NO-HIT</span>';
		}

		return '<div class="wps-result-cache">' . $string . '</div>';
	}

	/**
	 * @param null $text
	 * @param string $type
	 * @param string $name
	 * @param bool $wrap
	 * @param null $other_attributes
	 */
	public static function wps_submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
		echo self::wps_get_submit_button( $text, $type, $name, $wrap, $other_attributes );
	}

	/**
	 * @param string $text
	 * @param string $type
	 * @param string $name
	 * @param bool $wrap
	 * @param string $other_attributes
	 *
	 * @return string
	 */
	public static function wps_get_submit_button( $text = '', $type = 'primary large', $name = 'submit', $wrap = true, $other_attributes = '' ) {
		if ( ! is_array( $type ) ) {
			$type = explode( ' ', $type );
		}

		$button_shorthand = array( 'primary', 'small', 'large' );
		$classes          = array( 'button' );
		foreach ( $type as $t ) {
			if ( 'secondary' === $t || 'button-secondary' === $t ) {
				continue;
			}
			$classes[] = in_array( $t, $button_shorthand ) ? 'button-' . $t : $t;
		}
		// Remove empty items, remove duplicate items, and finally build a string.
		$class = implode( ' ', array_unique( array_filter( $classes ) ) );

		$text = $text ? $text : __( 'Save Changes' );

		// Default the id attribute to $name unless an id was specifically provided in $other_attributes
		$id = $name;
		if ( is_array( $other_attributes ) && isset( $other_attributes['id'] ) ) {
			$id = $other_attributes['id'];
			unset( $other_attributes['id'] );
		}

		$attributes = '';
		if ( is_array( $other_attributes ) ) {
			foreach ( $other_attributes as $attribute => $value ) {
				$attributes .= $attribute . '="' . esc_attr( $value ) . '" '; // Trailing space is important
			}
		} elseif ( ! empty( $other_attributes ) ) { // Attributes provided as a string
			$attributes = $other_attributes;
		}

		// Don't output empty name and id attributes.
		$name_attr = $name ? ' name="' . esc_attr( $name ) . '"' : '';
		$id_attr   = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$button = '<button type="submit"' . $name_attr . $id_attr . ' class="' . esc_attr( $class ) . '"' . $attributes . '>' . esc_attr( $text ) . '</button>';

		if ( $wrap ) {
			$button = '<p class="submit">' . $button . '</p>';
		}

		return $button;
	}

	/**
	 * @param $block
	 *
	 * @return string
	 */

	/**
	 * @param $block
	 *
	 * @return array
	 */
	public static function wps_display_block( $block ) {
		if ( is_multisite() ) {
			$wps_display = get_blog_option( get_current_blog_id(), 'wps_display' );
		} else {
			$wps_display = get_option( 'wps_display' );
		}
		if ( 'wps-dashboard-notifications' == $block ) {
			$class = array(
				'div' => 'wps-view',
				'h2'  => 'block-view'
			);
		}
		if ( is_array( $wps_display ) && in_array( $block, $wps_display ) ) {
			$class = array(
				'div' => 'wps-hide',
				'h2'  => 'block-hide'
			);
		} else {
			$class = array(
				'div' => 'wps-view',
				'h2'  => 'block-view'
			);
		}

		return $class;
	}

	public static function wps_db_prefix() {
		global $wpdb;

		return $wpdb->base_prefix;
	}

	/**
	 * Count the number of items concerned by the database cleanup
	 *
	 * @param string $type Item type to count.
	 *
	 * @return int Number of items for this type
	 */
	public static function count_cleanup_items( $type ) {
		global $wpdb;

		$count = 0;

		switch ( $type ) {
			case 'spam_comments':
				$count = $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = 'spam'" );
				break;
			case 'trashed_comments':
				$count = $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE (comment_approved = 'trash' OR comment_approved = 'post-trashed')" );
				break;
			case 'expired_transients':
				$time  = isset( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time();
				$count = $wpdb->get_var( "SELECT COUNT(option_name) FROM $wpdb->options WHERE option_name LIKE '_transient_timeout%' AND option_value < $time" );
				break;
			case 'revisions':
			    $count = $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'revision'" );
				break;
		}

		return $count;
	}

	/**
	 * Check if a given plugin is installed but not necessarily activated
	 * Note: get_plugins( $folder ) from WP Core doesn't work
	 *
	 * @param string $plugin a plugin folder/file.php (e.i. "wps-hide-login/wps-hide-login.php").
	 *
	 * @return bool True if installed, false otherwise
	 */
	public static function is_plugin_installed( $plugin ) {
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $plugin ] );
	}

	/**
	 * @param $plugin
	 *
	 * @return bool
	 */
	public static function plugin_version( $plugin ) {
		$installed_plugins = get_plugins();

		if ( ! isset( $installed_plugins[ $plugin ]['Version'] ) ) {
			return false;
		}

		return $installed_plugins[ $plugin ]['Version'];
	}

	/**
	 * Check if a given theme is installed but not necessarily activate
	 *
	 * @return bool True if installed, false otherwise
	 */
	public static function is_theme_installed( $theme ) {
		$installed_themes = wp_get_themes();

		return isset( $installed_themes[ $theme ] );
	}

	/**
	 * @param $theme
	 *
	 * @return bool
	 */
	public static function theme_version( $theme ) {
		$installed_themes = wp_get_themes();

		if ( ! isset( $installed_themes[ $theme ]['Version'] ) ) {
			return false;
		}

		return $installed_themes[ $theme ]['Version'];
	}

	/**
	 * Instanciate the filesystem class
	 *
	 * @return object WP_Filesystem_Direct instance
	 */
	public static function wps_direct_filesystem() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		return new \WP_Filesystem_Direct( new \StdClass() );
	}

	/**
	 * Try to find the correct wp-config.php file, support one level up in filetree
	 *
	 *
	 * @return string|bool The path of wp-config.php file or false if not found
	 */
	public static function wps_find_wpconfig_path() {
		/**
		 * Filter the wp-config's filename
		 *
		 * @param string $filename The WP Config filename, without the extension.
		 */
		$config_file_name = apply_filters( 'wps_wp_config_name', 'wp-config' );
		$config_file      = ABSPATH . $config_file_name . '.php';
		$config_file_alt  = dirname( ABSPATH ) . '/' . $config_file_name . '.php';

		if ( self::wps_direct_filesystem()->exists( $config_file ) && self::wps_direct_filesystem()->is_writable( $config_file ) ) {
			return $config_file;
		} elseif ( self::wps_direct_filesystem()->exists( $config_file_alt ) && self::wps_direct_filesystem()->is_writable( $config_file_alt ) && ! self::wps_direct_filesystem()->exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return $config_file_alt;
		}

		// No writable file found.
		return false;
	}

	/**
	 * Added or set the value of the WP_ALLOW_REPAIR constant
	 *
	 * @return void
	 */
	public static function set_wps_wp_repair_define( $bool ) {
		// If WP_ALLOW_REPAIR is already define, return to get a coffee.
		if ( $bool && defined( 'WP_ALLOW_REPAIR' ) && WP_ALLOW_REPAIR ) {
			return;
		}

		// Get path of the config file.
		$config_file_path = self::wps_find_wpconfig_path();
		if ( ! $config_file_path ) {
			return;
		}

		// Get content of the config file.
		$config_file = file( $config_file_path );

		$bool = $bool ? 'true' : 'false';

		/**
		 * Filter allow to change the value of WP_ALLOW_REPAIR constant
		 *
		 * @param string $turn_it_on The value of WP_ALLOW_REPAIR constant.
		 */
		apply_filters( 'set_wps_wp_repair_define', $bool );

		// Lets find out if the constant WP_CACHE is defined or not.
		$is_wp_allow_repair_exist = false;

		// Get WP_CACHE constant define.
		$constant = "define('WP_ALLOW_REPAIR', $bool); // Added by WPS Bidouille" . "\r\n";

		foreach ( $config_file as &$line ) {
			if ( ! preg_match( '/^define\(\s*\'([A-Z_]+)\',(.*)\)/', $line, $match ) ) {
				continue;
			}

			if ( 'WP_ALLOW_REPAIR' === $match[1] ) {
				$is_wp_allow_repair_exist = true;
				$line                     = $constant;
			}
		}
		unset( $line );

		// If the constant does not exist, create it.
		if ( ! $is_wp_allow_repair_exist ) {
			array_shift( $config_file );
			array_unshift( $config_file, "<?php\r\n", $constant );
		}

		if ( $bool == 'false' ) {
			unset( $config_file[1] );
		}

		// Insert the constant in wp-config.php file.
		$handle = @fopen( $config_file_path, 'w' );
		foreach ( $config_file as $line ) {
			@fwrite( $handle, $line );
		}

		@fclose( $handle );

		// Update the writing permissions of wp-config.php file.
		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		self::wps_direct_filesystem()->chmod( $config_file_path, $chmod );
	}

	public static function download_package( $url ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		WP_Filesystem();

		$skin     = new \Automatic_Upgrader_Skin;
		$upgrader = new \WP_Upgrader( $skin );

		// Suppress feedback
		ob_start();

		$package  = $url;
		$download = $upgrader->download_package( $package );

		if ( is_wp_error( $download ) ) {
			throw new \Exception( $download->get_error_message() );
		}

		$working_dir = $upgrader->unpack_package( $download, true );

		if ( is_wp_error( $working_dir ) ) {
			throw new \Exception( $working_dir->get_error_message() );
		}

		$result = $upgrader->install_package( array(
			'source'                      => $working_dir,
			'destination'                 => WP_PLUGIN_DIR,
			'clear_destination'           => false,
			'abort_if_destination_exists' => false,
			'clear_working'               => true,
			'hook_extra'                  => array(
				'type'   => 'plugin',
				'action' => 'install',
			),
		) );

		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
		}

		// Discard feedback
		ob_end_clean();

		wp_clean_plugins_cache();
	}

	public static function download_theme_package( $url ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		WP_Filesystem();

		$skin     = new \Automatic_Upgrader_Skin;
		$upgrader = new \WP_Upgrader( $skin );

		// Suppress feedback
		ob_start();

		$package  = $url;
		$download = $upgrader->download_package( $package );

		if ( is_wp_error( $download ) ) {
			throw new \Exception( $download->get_error_message() );
		}

		$working_dir = $upgrader->unpack_package( $download, true );

		if ( is_wp_error( $working_dir ) ) {
			throw new \Exception( $working_dir->get_error_message() );
		}

		$result = $upgrader->install_package( array(
			'source'                      => $working_dir,
			'destination'                 => WP_CONTENT_DIR . '/themes',
			'clear_destination'           => false,
			'abort_if_destination_exists' => false,
			'clear_working'               => true,
			'hook_extra'                  => array(
				'type'   => 'theme',
				'action' => 'install',
			),
		) );

		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
		}

		// Discard feedback
		ob_end_clean();

		wp_clean_themes_cache();
	}

	public static function get_informations_ssl() {
		if ( false === ( $certinfo = get_transient( 'wps_certificate_ssl' ) ) ) :
			if ( is_ssl() && function_exists( 'stream_context_get_params' ) ) {

				$url            = home_url();
				$original_parse = parse_url( $url, PHP_URL_HOST );

				if ( $original_parse ) {
					$get = stream_context_create( array( "ssl" => array( "capture_peer_cert" => true ) ) );
					if ( $get ) {
						set_error_handler( '__return_true' );
						$read = stream_socket_client( "ssl://" . $original_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get );
						restore_error_handler();
						if ( $errno == 0 && $read ) {
							$cert     = stream_context_get_params( $read );
							$certinfo = openssl_x509_parse( $cert['options']['ssl']['peer_certificate'] );
						}
					}
				}
			}
			set_transient( 'wps_certificate_ssl', $certinfo, WEEK_IN_SECONDS );
		endif;

		return $certinfo;
    }
}