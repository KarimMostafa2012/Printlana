<?php

namespace WPS\WPS_Bidouille;

class RemoveFromCache {

	use Singleton;

	protected function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		add_action( 'wp_before_admin_bar_render', array( $this, '_wps_cache_admin_bar_render' ), 99 );

		add_action( 'wp_head', array( $this, 'wps_rpfc_public' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'wp_head' ) );

		add_action( 'wp_ajax_wps_get_posts', array( __CLASS__, 'wps_get_posts' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_wps_tools_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		add_filter( 'pre_update_option', array(
			__CLASS__,
			'update_option_select2_wps_posts'
		), 10, 3 );

		add_filter( 'pre_update_option', array(
			__CLASS__,
			'update_option_wps_cpt_remove_from_cache'
		), 10, 3 );

		add_filter( 'pre_update_option', array(
			__CLASS__,
			'update_option_wps_archive_cpt_remove_from_cache'
		), 10, 3 );

		add_action( 'admin_init', array( __CLASS__, 'delete_post_exclud_cache' ) );
		add_action( 'admin_init', array( __CLASS__, 'delete_all_posts_exclud_cache' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 1, 2 );

		add_filter( 'wps_not_purge_auto', array( __CLASS__, 'wps_not_purge_auto' ) );
	}

	/**
	 * Register a custom menu page.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'wps-bidouille',
			__( 'Exclude from cache', 'wps-bidouille' ),
			__( 'Exclude from cache', 'wps-bidouille' ),
			'manage_options',
			'wps-bidouille-remove-from-cache',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function register_wps_tools_settings() {
		register_setting( 'wps-bidouille-remove-cache-settings-archive', 'wps_archive_cpt_remove_from_cache' );
		register_setting( 'wps-bidouille-remove-cache-settings-all', 'wps_cpt_remove_from_cache' );
		register_setting( 'wps-bidouille-remove-cache-settings', 'select2_wps_posts' );

		register_setting( 'wps-bidouille-deactivate-purge-auto', 'wps_bidouille_deactivate_purge' );
	}

	public static function admin_page() {
		include( WPS_BIDOUILLE_DIR . '/admin_page/remove_from_cache.php' );
	}

	public static function _columns_head( $defaults ) {
		$defaults['cache_page_status'] = '<i class="fa fa-rocket" title="' . __( 'Cache', 'wps-bidouille' ) . '"></i>';

		return $defaults;
	}

	public static function _columns_content( $column_name, $post_ID ) {
		if ( $column_name != 'cache_page_status' ) {
			return false;
		}

		$list_post_without_cache = get_option( 'list_post_without_cache' );
		if ( is_array( $list_post_without_cache ) && in_array( $post_ID, $list_post_without_cache ) ) {
			$color   = 'red';
			$message = __( 'Removed from cache', 'wps-bidouille' );
		} else {
			$color   = 'green';
			$message = __( 'Cached', 'wps-bidouille' );
		}

		echo '<span style="color:' . $color . '" title="' . $message . '"><i class="fa fa-circle"></i></span>';
	}

	public function _wps_cache_admin_bar_render() {
		if ( ! current_user_can( 'manage_options' ) || is_admin() ) {
			return false;
		}

		global $wp_admin_bar, $wp_query;

		$list_post_without_cache = get_option( 'list_post_without_cache' );

		if ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->ID ) ) {
			$post_id = $wp_query->queried_object->ID;
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$post_id = get_option( 'woocommerce_shop_page_id' );
		} else {
			$post_id = get_the_ID();
		}

		if ( ! empty( $list_post_without_cache ) && in_array( $post_id, $list_post_without_cache ) ) {
			$msg = '<span id="icon-not-cached" class="ab-icon"></span> <span class="ab-label">' . __( 'Not cached', 'wps-bidouille' ) . '</span>';
		} else {
			$msg = '<span id="icon-cached" class="ab-icon"></span> <span class="ab-label">' . __( 'Cached', 'wps-bidouille' ) . '</span>';
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'link-wps-cache-admin',
			'title' => $msg,
			'href'  => false
		) );
	}

	public static function wp_head() {
		if ( ! is_user_logged_in() ) {
			return false;
		} ?>
        <style>
            #wpadminbar #wp-admin-bar-link-wps-cache-admin #icon-not-cached:before {
                content: "\f335";
                top: 2px;
                color: red;
            }

            #wpadminbar #wp-admin-bar-link-wps-cache-admin #icon-cached:before {
                content: "\f147";
                top: 2px;
                color: green;
            }
        </style>
		<?php
	}

	/**
	 *
	 */
	public function wps_rpfc_public() {
		global $wp_query;

		//self::delete_cookie();

		$list_post_without_cache = get_option( 'list_post_without_cache' );
		if ( empty( $list_post_without_cache ) ) {
			return false;
		}

		if ( isset( $wp_query->queried_object ) ) {
			$post_id = $wp_query->queried_object->ID;
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$post_id = get_option( 'woocommerce_shop_page_id' );
		} else {
			$post_id = get_the_ID();
		}

		if ( in_array( $post_id, $list_post_without_cache ) ) {
			add_action( 'send_headers', array( __CLASS__, 'add_header_nocache' ), 2 );
			add_action( 'wp_head', array( __CLASS__, 'add_browser_nocache' ) );
			//add_action( 'wp_head', array( __CLASS__, 'send_cookie' ), 2 );
		}
	}

	/**
	 * @param $page_id
	 *
	 * @return bool
	 */

	/**
	 * @param $page_id
	 *
	 * @return bool
	 */
	public function is_front_page( $page_id ) {
		if ( get_option( 'page_on_front' ) == $page_id ) {
			return true;
		}
	}

	/**
	 *
	 */
	public static function add_header_nocache() {
		nocache_headers();
	}

	/**
	 *
	 */
	public static function add_browser_nocache() { ?>
        <!-- Disable by WPS Bidouille -->
        <meta http-equiv="cache-control" content="no-cache"/>
        <meta http-equiv="pragma" content="no-cache"/>
		<?php
	}

	/**
	 *
	 */
	public static function send_cookie() {
		setcookie( 'WPServeur-php', 'NOCACHE' );
	}

	/**
	 *
	 */
	public static function delete_cookie() {
		if ( isset( $_COOKIE['WPServeur-php'] ) ) {
			unset( $_COOKIE['WPServeur-php'] );
		}
	}

	/**
	 *
	 * Return all cpts public
	 *
	 * @return array
	 */
	public static function _get_cpts() {
		$args = apply_filters( 'wps_post_type_filter', array(
			'public'  => true,
			'show_ui' => true
		) );

		$cpts = get_post_types( $args, 'objects', 'and' );

		foreach ( $cpts as $k => $cpt ) {
			if ( in_array( $cpt->name, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) {
				unset( $cpts[ $k ] );
			}
		}

		if ( is_plugin_active_for_network( 'bbpress/bbpress.php' ) || is_plugin_active( 'bbpress/bbpress.php' ) ) {
			foreach ( $cpts as $k => $cpt ) {
				if ( in_array( $cpt->name, array( 'forum', 'topic', 'reply' ) ) ) {
					unset( $cpts[ $k ] );
				}
			}
		}

		return $cpts;
	}

	public static function wps_get_posts() {
		check_ajax_referer( 'select-posts' );

		$results = array();

		$posts_query = new \WP_Query( array(
				's'              => sanitize_text_field( $_GET['q'] ),
				'post_type'      => 'any',
				'posts_per_page' => 10,
			)
		);

		while ( $posts_query->have_posts() ) :
			$posts_query->the_post();
			$results[] = array(
				'id'   => get_the_ID(),
				'text' => get_the_title(),
				'cpt'  => get_post_type( get_the_ID() )
			);
		endwhile;

		wp_reset_query();

		echo json_encode( $results );
		die;
	}

	public static function update_option_select2_wps_posts( $value, $option, $old_value ) {
		if ( 'select2_wps_posts' !== $option ) {
			return $value;
		}

		if ( ! empty( $value ) ) {
			$list_post_without_cache = get_option( 'list_post_without_cache' );

			if ( is_array( $list_post_without_cache ) ) {
				$data = array_unique( array_merge( $list_post_without_cache, $value ), SORT_REGULAR );
			} else {
				$data = $value;
			}

			update_option( 'list_post_without_cache', $data );
		}

		delete_option( 'select2_wps_posts' );

		return $value;
	}

	public static function update_option_wps_cpt_remove_from_cache( $value, $option, $old_value ) {
		if ( 'wps_cpt_remove_from_cache' !== $option ) {
			return $value;
		}

		if ( ! empty( $value ) ) {
			$list_post_without_cache = get_option( 'list_post_without_cache' );

			$args = array(
				'post_type'     => $value,
				'nopaging'      => true,
				'no_found_rows' => true,
				'post_status'   => 'publish'
			);

			$posts    = new \WP_Query( $args );
			$post_ids = wp_list_pluck( $posts->posts, 'ID' );

			if ( is_array( $list_post_without_cache ) ) {
				$data = array_unique( array_merge( $list_post_without_cache, $post_ids ), SORT_REGULAR );
			} else {
				$data = $post_ids;
			}

			update_option( 'list_post_without_cache', $data );
		}

		delete_option( 'wps_cpt_remove_from_cache' );

		return $value;
	}

	public static function update_option_wps_archive_cpt_remove_from_cache( $value, $option, $old_value ) {
		if ( 'wps_archive_cpt_remove_from_cache' !== $option ) {
			return $value;
		}

		if ( ! empty( $value ) ) {
			$list_post_without_cache = get_option( 'list_post_without_cache' );

			$args = array(
				'post_type'     => $value,
				'nopaging'      => true,
				'no_found_rows' => true,
				'post_status'   => 'publish'
			);

			$posts    = new \WP_Query( $args );
			$post_ids = wp_list_pluck( $posts->posts, 'ID' );

			if ( is_array( $list_post_without_cache ) ) {
				$data = array_unique( array_merge( $list_post_without_cache, $post_ids ), SORT_REGULAR );
			} else {
				$data = $post_ids;
			}

			update_option( 'list_post_without_cache', $data );
		}

		delete_option( 'wps_archive_cpt_remove_from_cache' );

		return $value;
	}

	public static function admin_init() {
		$cpts = self::_get_cpts();

		if ( ! empty( $cpts ) ) {
			$cpts = array_keys( $cpts );
			foreach ( $cpts as $cpt ) {
				add_filter( 'manage_' . $cpt . '_posts_columns', array( __CLASS__, '_columns_head' ) );
				add_action( 'manage_' . $cpt . '_posts_custom_column', array(
					__CLASS__,
					'_columns_content'
				), 10, 2 );
			}
		}
	}

	public static function delete_post_exclud_cache() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'exclude_cache' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'delete-exclud-cache' || ! isset( $_GET['post_id'] ) ) {
			return false;
		}

		$list_post_without_cache = get_option( 'list_post_without_cache' );
		$list_post_without_cache = array_diff( $list_post_without_cache, array( esc_html( $_GET['post_id'] ) ) );
		update_option( 'list_post_without_cache', $list_post_without_cache );
		wp_redirect( wp_get_referer() );
		exit;
	}

	public static function delete_all_posts_exclud_cache() {
		if ( ! isset( $_GET['wps-nonce'] ) || ! wp_verify_nonce( $_GET['wps-nonce'], 'exclude_cache' ) ) {
			return false;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'delete-exclud-cache-all' ) {
			return false;
		}

		update_option( 'list_post_without_cache', array() );
		delete_option( 'wps_archive_cpt_remove_from_cache' );
		delete_option( 'wps_cpt_remove_from_cache' );
		delete_option( 'select2_wps_posts' );
		wp_redirect( wp_get_referer() );
		exit;
	}

	public static function add_meta_boxes() {
		$cpts = self::_get_cpts();

		if ( ! empty( $cpts ) ) {

			$is_user_white_label = Helpers::is_user_white_label();

			$title_metabox = __( 'WPServeur exclud cache NGINX', 'wps-bidouille' );
			if ( $is_user_white_label ) {
				$title_metabox = __( 'Exclud cache NGINX', 'wps-bidouille' );
			}

			$cpts = array_keys( $cpts );
			foreach ( $cpts as $cpt ) {
				add_meta_box(
					'wps-remove-cache',
					$title_metabox,
					array( __CLASS__, 'render_meta_box' ),
					$cpt,
					'side',
					'default'
				);
			}
		}
	}

	/**
	 * Output the HTML for the metabox.
	 */
	public static function render_meta_box() {
		global $post;

		wp_nonce_field( basename( __FILE__ ), 'wps_cache_fields' );

		$list_post_without_cache = get_option( 'list_post_without_cache' );
		$checked                 = '';
		if ( is_array( $list_post_without_cache ) && in_array( $post->ID, $list_post_without_cache ) ) {
			$checked = 'checked="checked"';
		}
		// Output the field
		echo '<input type="checkbox" name="wps-cache" value="' . $post->ID . '" ' . $checked . '><label for="wps-cache">' . __( 'Remove this post for cache', 'wps-bidouille' ) . '</label>';
	}

	/**
	 * Save the metabox data
	 */
	public static function save_post( $post_id, $post ) {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( ! isset( $_POST['wps_cache_fields'] ) || ! wp_verify_nonce( $_POST['wps_cache_fields'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		$list_post_without_cache = get_option( 'list_post_without_cache' );
		if ( ! isset( $_POST['wps-cache'] ) ) {
			if ( empty( $list_post_without_cache ) ) {
				$list_post_without_cache = array();
			}
			update_option( 'list_post_without_cache', array_diff( $list_post_without_cache, array( $post_id ) ) );

			return $post_id;
		}

		if ( isset( $_POST['wps-cache'] ) ) {
			$list_post_without_cache[] = $_POST['wps-cache'];
			update_option( 'list_post_without_cache', $list_post_without_cache );
		}
	}

	public static function wps_not_purge_auto( $bool ) {
		$option_purge = get_option( 'wps_bidouille_deactivate_purge' );
		if ( 1 == $option_purge ) {
			return true;
		}

		return $bool;
	}
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
if ( ! class_exists( 'WPS_Uncached_List_Table' ) ) {
	class WPS_Uncached_List_Table extends \WP_List_Table {
		/**
		 * Prepare the items for the table to process
		 *
		 * @return Void
		 */
		public function prepare_items() {
			$columns  = $this->get_columns();
			$hidden   = $this->get_hidden_columns();
			$sortable = $this->get_sortable_columns();

			$data = $this->table_data();
			usort( $data, array( &$this, 'sort_data' ) );

			$perPage     = 20;
			$currentPage = $this->get_pagenum();
			$totalItems  = count( $data );

			$this->set_pagination_args( array(
				'total_items' => $totalItems,
				'per_page'    => $perPage
			) );

			$data = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ), $perPage );

			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items           = $data;
		}

		/**
		 * Override the parent columns method. Defines the columns to use in your listing table
		 *
		 * @return array
		 */
		public function get_columns() {
			$columns = array(
				'id'        => __( 'ID' ),
				'title'     => __( 'Title' ),
				'permalink' => __( 'Permalink', 'wps-bidouille' ),
				'post_type' => __( 'Post type' ),
				'delete'    => __( 'Erase', 'wps-bidouille' )
			);

			return $columns;
		}

		/**
		 * Define which columns are hidden
		 *
		 * @return array
		 */
		public function get_hidden_columns() {
			return array();
		}

		/**
		 * Define the sortable columns
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return array(
				'id'        => array( 'id', false ),
				'title'     => array( 'title', false ),
				'post_type' => array( 'post_type', false )
			);
		}

		/**
		 * Get the table data
		 *
		 * @return array
		 */
		private function table_data() {
			$data = $post__not_in = array();

			$list_post_without_cache = get_option( 'list_post_without_cache' );
			$cpts                    = RemoveFromCache::_get_cpts();

			if ( ! empty( $list_post_without_cache ) ) {
				foreach ( $list_post_without_cache as $k => $page ) {

					$post__not_in[] = $page;

					$args  = array(
						'post_type'     => array_keys( $cpts ),
						'post__in'      => array( $page ),
						'no_found_rows' => true
					);
					$query = new \WP_Query( $args );

					foreach ( $query->posts as $raw_data ) {

						$data[] = array(
							'id'        => $raw_data->ID,
							'title'     => $raw_data->post_title,
							'permalink' => '<a href="' . esc_url( get_permalink( $raw_data->ID ) ) . '" target="_blank">' . str_replace( get_site_url(), '', get_permalink( $raw_data->ID ) ) . '</a>',
							'post_type' => $raw_data->post_type,
							'delete'    => '<div class="flex-align-middle"><a href="' . add_query_arg( array(
									'action'  => 'delete-exclud-cache',
									'post_id' => $raw_data->ID
								), wp_nonce_url( admin_url( 'admin.php?page=wps-bidouille-remove-from-cache' ), 'exclude_cache', 'wps-nonce' ) ) . '">' . __( 'Erase', 'wps-bidouille' ) . '</a></div>'
						);
					}

					wp_reset_query();
				}
			}

			return $data;
		}

		/**
		 * Define what data to show on each column of the table
		 *
		 * @param  array $item Data
		 * @param  String $column_name - Current column name
		 *
		 * @return Mixed
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'id':
				case 'title':
				case 'permalink':
				case 'post_type':
				case 'delete':
					return $item[ $column_name ];

				default:
					return print_r( $item, true );
			}
		}

		/**
		 * Allows you to sort the data by the variables set in the $_GET
		 *
		 * @return Mixed
		 */
		private function sort_data( $a, $b ) {
			// Set defaults
			$orderby = 'title';
			$order   = 'asc';

			// If orderby is set, use this as the sort column
			if ( ! empty( $_GET['orderby'] ) ) {
				$orderby = $_GET['orderby'];
			}

			// If order is set use this as the order
			if ( ! empty( $_GET['order'] ) ) {
				$order = $_GET['order'];
			}

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

			if ( $order === 'asc' ) {
				return $result;
			}

			return - $result;
		}

		/**
		 * Get a list of CSS classes for the WP_List_Table table tag.
		 *
		 * @since 3.1.0
		 *
		 * @return array List of CSS classes for the table tag.
		 */
		protected function get_table_classes() {
			return array( 'widefat', 'fixed', 'striped', $this->_args['plural'], 'wps-list-cache' );
		}
	}
}