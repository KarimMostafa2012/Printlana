<?php
// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

if ( ! function_exists('wpacuPregMatchInput') ) {
	/**
	 * @param $patterns
	 * @param $subject
	 *
	 * @return bool|false|int
	 */
	function wpacuPregMatchInput( $patterns, $subject )
	{
		$patterns = trim( $patterns );

		if ( ! $patterns ) {
			return false;
		}

		// One line (there aren't several lines in the textarea)
		if ( strpos( $patterns, "\n" ) === false ) {
			$pattern = $patterns;
			$return = @preg_match( $pattern, $subject );

			if ( ! $return && function_exists('error_get_last') && function_exists('preg_last_error_msg') && function_exists('preg_last_error') && preg_last_error() !== PREG_NO_ERROR ) {
				$errorGetLast = error_get_last();
				error_log( '"Asset CleanUp Pro" / Invalid RegEx: ' . $pattern . ' / Error: ' . $errorGetLast['message'] . ' / File: '. $errorGetLast['file'] . ' / Line: '.$errorGetLast['line']);
			}

			return $return;
		}

		// Multiple lines
		foreach ( explode( "\n", $patterns ) as $pattern ) {
			$pattern = trim( $pattern );

			if ( ! $pattern ) {
				continue;
			}

			$return = @preg_match( $pattern, $subject );

			if ( ! $return && function_exists('error_get_last') && function_exists('preg_last_error_msg') && function_exists('preg_last_error') && preg_last_error() !== PREG_NO_ERROR ) {
				$errorGetLast = error_get_last();
				error_log( '"Asset CleanUp Pro" / Invalid RegEx: ' . $pattern . ' / Error: ' . $errorGetLast['message'] . ' / File: '. $errorGetLast['file'] . ' / Line: '.$errorGetLast['line']);
			}

			if ( $return ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists('wpacuEndsWith') ) {
	/**
	 * Alias of \WpAssetCleanUp\Misc::endsWith()
	 *
	 * @param $string
	 * @param $endsWithString
	 *
	 * @return bool
	 */
	function wpacuEndsWith( $string, $endsWithString ) {
		$stringLen         = strlen( $string );
		$endsWithStringLen = strlen( $endsWithString );

		if ( $endsWithStringLen > $stringLen ) {
			return false;
		}

		return substr_compare(
			       $string,
			       $endsWithString,
			       $stringLen - $endsWithStringLen, $endsWithStringLen
		       ) === 0;
	}
}

// Match "product" post type pages if their URL structure is changed
include_once 'mu-plugins/_compatible/_premmerce-woocommerce-product-filter.php';

if ( ! function_exists( 'wpacuGetAllPossiblePostTypes') ) {
	/**
	 * @return array
	 */
	function wpacuGetAllPossiblePostTypes()
	{
		global $wpdb;

		$sqlQuery = <<<SQL
SELECT DISTINCT(post_type) AS post_type FROM `{$wpdb->posts}`
SQL;

		return $wpdb->get_col($sqlQuery);
	}
}

if ( ! function_exists( 'wpacuGetCommonTaxonomies') ) {
	/**
	 * @return string[]
	 */
	function wpacuGetCommonTaxonomies()
	{
		return array( 'category', 'post_tag', 'product_cat', 'product_tag', 'download_category', 'download_tag' );
	}
}

if ( ! function_exists( 'wpacuIsTaxonomyRecord') ) {
	/**
	 * @param $maybeTaxonomy
	 * @param $maybeSlug
	 *
	 * @return bool
	 */
	function wpacuIsTaxonomyRecord($maybeTaxonomy, $maybeSlug)
	{
		//
		// e.g. if this is a product category / tag page (taxonomy: product_cat / product_tag), thus get the targeted category
		// e.g. if it's a sub-category, make sure to fetch its slug, not the parent category that also has the slug within the URI
		//
		$taxLongSlug = trim($maybeSlug, '/');

		if (strpos($taxLongSlug, '/') !== false) {
			$taxSlug = substr($taxLongSlug, strrpos($taxLongSlug, '/') + 1);
		} else {
			$taxSlug = $taxLongSlug;
		}

		$taxNameForDbCall = $taxSlug;
		$prepareQuery = true;

		if ($taxSlug !== rawurlencode($taxSlug)) {
			// The URI contains special characters (e.g. from Greek, Romanian, Spanish, etc.)
			$taxNameForDbCall = rawurlencode($taxSlug);
			$prepareQuery = false;
		}

		global $wpdb;

		$sqlQuery = <<<SQL
SELECT t.term_id FROM `{$wpdb->prefix}terms` t 
LEFT JOIN `{$wpdb->prefix}term_taxonomy` tt ON (t.term_id = tt.term_id) 
WHERE t.slug='%s' && tt.taxonomy='{$maybeTaxonomy}'
SQL;
		if ($prepareQuery) {
			$sqlQueryFinal = $wpdb->prepare( $sqlQuery, array( $taxNameForDbCall ) );
		} else {
			$sqlQueryFinal = str_replace('%s', $taxNameForDbCall, $sqlQuery);
		}

		return $wpdb->get_var($sqlQueryFinal);
	}
}

if ( ! function_exists( 'wpacuIsAuthorPage') ) {
	/**
	 * @param $maybeAuthor
	 *
	 * @return string|null
	 */
	function wpacuIsAuthorPage($maybeAuthor)
	{
		global $wpdb;

		$sqlQuery = <<<SQL
SELECT `ID` FROM `{$wpdb->users}` WHERE `user_nicename`='{$maybeAuthor}'
SQL;
		return $wpdb->get_var($sqlQuery);
	}
}

if ( ! function_exists( 'wpacuGetRewriteRules') ) {
	/**
	 * @return mixed
	 */
	function wpacuGetRewriteRules()
	{
		global $wp_rewrite;

		if ( ! isset( $wp_rewrite ) ) {
			require_once ABSPATH . WPINC . '/rewrite.php';
			$wp_rewrite = new WP_Rewrite();
		}

		$GLOBALS['wpacu_wp_rewrite_rules'] = get_option( 'rewrite_rules' );

		if ( empty( $GLOBALS['wpacu_wp_rewrite_rules'] ) ) {
			$GLOBALS['wpacu_wp_rewrite_rules'] = $wp_rewrite->rewrite_rules();
		}

		return $GLOBALS['wpacu_wp_rewrite_rules'];
	}
}

if ( ! function_exists( 'wpacuUrlToPageType') ) {
	/**
	 * @param $url (it's usually the request URI)
	 *
	 * @return array
	 */
	function wpacuUrlToPageType( $url )
	{
		if (wpacuIsHomePageUrl($url)) {
			return array(); // it was already detected as the home page, so stop here
		}

		if (isset($GLOBALS['wpacu_url_to_page_type'])) {
			return $GLOBALS['wpacu_url_to_page_type'];
		}

		global $wp_rewrite;

		if ( ! isset( $wp_rewrite ) ) {
			require_once ABSPATH . WPINC . '/rewrite.php';
			$wp_rewrite = new WP_Rewrite();
		}

		$host_from_url_parameter = parse_url( $url, PHP_URL_HOST );
		$site_home_url_host      = parse_url( home_url(), PHP_URL_HOST );

		// If it's not a URI, but a URL (passed as a parameter)
		// compare it with the home_url() to make sure it belongs to the same host
		// $site_home_url_host should never be null, but you never know how it's altered by a 3rd party code
		if ($host_from_url_parameter !== null && $site_home_url_host !== null) {
			$host_from_url_parameter = str_replace( 'www.', '', $host_from_url_parameter );
			$site_home_url_host      = str_replace( 'www.', '', $site_home_url_host );

			// Bail early if the URL does not belong to this site.
			if ( $host_from_url_parameter && $host_from_url_parameter !== $site_home_url_host ) {
				$GLOBALS['wpacu_url_to_page_type'] = array();
				return array();
			}
		}

		// First, check to see if there is a 'p=N' or 'page_id=N' to match against.
		if ( preg_match( '#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values ) ) {
			$id = absint( $values[2] );
			if ( $id ) {
				global $wpdb;
				$sql = <<<SQL
SELECT ID, post_type FROM `{$wpdb->posts}` WHERE ID='{$id}'
SQL;
				$post_result = $wpdb->get_row( $sql, ARRAY_A );

				$to_return = (! empty($post_result)) ? $post_result : array();

				$GLOBALS['wpacu_url_to_page_type'] = $to_return;
				return $to_return;
			}
		}

		// Search page: /?s=keyword was used instead of /search/keyword
		if (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '?s=') !== false || strpos($_SERVER['REQUEST_URI'], '&s=') !== false)) {
			$to_return = array(
				'page_type'       => 'search',
				'is_archive_type' => true
			);

			$GLOBALS['wpacu_url_to_page_type'] = $to_return;
			return $to_return;
		}

		// Get rid of the #anchor.
		$url_split = explode( '#', $url );
		$url       = $url_split[0];

		// Get rid of URL ?query=string.
		$url_split = explode( '?', $url );
		$url       = $url_split[0];

		// Set the correct URL scheme.
		$scheme = parse_url( home_url(), PHP_URL_SCHEME );
		$url    = set_url_scheme( $url, $scheme );

		// Add 'www.' if it is absent and should be there.
		if ( false !== strpos( home_url(), '://www.' ) && false === strpos( $url, '://www.' ) ) {
			$url = str_replace( '://', '://www.', $url );
		}

		// Strip 'www.' if it is present and shouldn't be.
		if ( false === strpos( home_url(), '://www.' ) ) {
			$url = str_replace( '://www.', '://', $url );
		}

		if ( trim( $url, '/' ) === home_url() && 'page' === get_option( 'show_on_front' ) ) {
			$page_on_front = get_option( 'page_on_front' );
			$getPost = get_post( $page_on_front ) instanceof WP_Post;

			if ( $page_on_front && isset($getPost->post_type) && $getPost->post_type ) {
				$to_return = array(
					'ID'        => $page_on_front,
					'post_type' => $getPost->post_type
				);

				$GLOBALS['wpacu_url_to_page_type'] = $to_return;
				return $to_return;
			}
		}

		// Compatibility with other plugins
		// If the request URI is just a forward slash, then do not do any extra checks as it's not a WooCommerce product page
		if ( ! ($url === '' || $url === '/') ) {
			global $wpdb;

			if ( ! isset($GLOBALS['wpacu_active_plugins_from_db']) ) {
				$sqlQuery          = <<<SQL
SELECT option_value FROM `{$wpdb->prefix}options` WHERE option_name='active_plugins'
SQL;
				$activePluginsJson = $wpdb->get_var( $sqlQuery );
				$activePlugins = maybe_unserialize($activePluginsJson) ?: array();

				$GLOBALS['wpacu_active_plugins_from_db'] = $activePlugins;
			} else {
				$activePlugins = $GLOBALS['wpacu_active_plugins_from_db'];
			}
			$isPremmercePermalinkManager = in_array( 'woo-permalink-manager/premmerce-url-manager.php', $activePlugins)
                                        || in_array( 'woo-permalink-manager-premium/premmerce-url-manager.php', $activePlugins );

			if ( $isPremmercePermalinkManager && in_array( 'woocommerce/woocommerce.php', $activePlugins ) ) {
				$premmerceResult = wpacuIsPremmerceWooCommerceProductPage( $url );

				if ( ! empty( $premmerceResult ) ) {
					$GLOBALS['wpacu_url_to_page_type'] = $premmerceResult;

					return $premmerceResult;
				}
			}
		}

		// Check to see if we are using rewrite rules.
		$rewrite = wpacuGetRewriteRules();

		// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options.
		if ( empty( $rewrite ) ) {
			$GLOBALS['wpacu_url_to_page_type'] = array();
			return array();
		}

		// Strip 'index.php/' if we're not using path info permalinks.
		if ( ! $wp_rewrite->using_index_permalinks() ) {
			$url = str_replace( $wp_rewrite->index . '/', '', $url );
		}

		if ( false !== strpos( trailingslashit( $url ), home_url( '/' ) ) ) {
			// Chop off http://domain.com/[path].
			$url = str_replace( home_url(), '', $url );
		} else {
			// Chop off /path/to/blog.
			$home_path = parse_url( home_url( '/' ) );
			$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '';
			$url       = preg_replace( sprintf( '#^%s#', preg_quote( $home_path ) ), '', trailingslashit( $url ) );
		}

		// Trim leading and lagging slashes.
		$url = trim( $url, '/' );

		$request = $url;

		$possible_post_types = array();

		// Look for matches.
		$request_match = $request;

		foreach ( (array) $rewrite as $queryKey => $query ) {
			if ( strpos( $query, '?post_type=' ) !== false ) {
				list( , $post_type_to_append ) = explode( '?post_type=', $query );

				if ( strpos( $post_type_to_append, '&' ) === false ) {
					$possible_post_types[] = $post_type_to_append;
					}
			}

			// Special cases
			if ( strpos( $queryKey, 'template/') !== false && strpos($query, '?bricks_template=') !== false ) {
				$possible_post_types[] = 'bricks_template';
			}
		}

		foreach ( (array) $rewrite as $match => $query ) {
			// If the requesting file is the anchor of the match,
			// prepend it to the path info.
			if ( ! empty( $url ) && ( $url != $request ) && ( strpos( $match, $url ) === 0 ) ) {
				$request_match = $url . '/' . $request;
			}

			if ( preg_match( "#^$match#", $request_match, $matches ) ) {
				// Got a match.
				// Trim the query of everything up to the '?'.
				$query = preg_replace( '!^.+\?!', '', $query );

				// Substitute the substring matches into the query.
				$query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

				// Filter out non-public query vars.
				parse_str( $query, $query_vars );

				$query = array();

				foreach ( $query_vars as $key => $value ) {
					if ( $key === 'pagename' ) { // Page
						$query['name'] = $value;
					} elseif ( $key === 'name' ) { // Post, Attachment
						$query['name'] = $value;
					}

					// [Taxonomy]
					elseif ($key === 'category_name') {
						$query['taxonomy'] = array('name' => 'category', 'value' => $value);
					} elseif ($key === 'tag') {
						$query['taxonomy'] = array('name' => 'post_tag', 'value' => $value);
					} elseif ($value && ($termId = wpacuIsTaxonomyRecord($key, $value))) {
						$query['taxonomy'] = array('name' => $key, 'value' => $value, 'term_id' => $termId);
					}
					// [/Taxonomy]

					// [Archive Page Types]
						// [Search Page: List of posts based on the standard WordPress search]
						elseif ($key === 's') {
							$query['search'] = array('name' => $key, 'value' => $value);
						}
						// [/Search Page: List of posts based on the standard WordPress search]

						// [Author Page: List of posts belonging to a specific author]
						elseif ($key === 'author_name' && ($userId = wpacuIsAuthorPage($value))) {
							$query['author'] = array('name' => $key, 'value' => $value, 'user_id' => $userId);
						}
						// [/Author Page: List of posts belonging to a specific author]

						// [Date Archive]
						elseif ($key === 'year' && isset($query_vars['monthnum'])) {
							$query['date'] = array('name' => 'date');
						}
						// [/Date Archive]
					// [/Archive Page Types]

					// [Custom Post Types]
					elseif ( $value && (in_array($key, $possible_post_types) || in_array($key, wpacuGetAllPossiblePostTypes())) ) {
						$query['name'] = $value;
					}
					// [/Custom Post Types]

					if (isset($query['name']) && $query['name']) { // Already found? Stop here!
						break;
					}

					}

				global $wpdb;

				if ( isset( $query['name'] ) && $query['name'] &&
				     (strpos($query['name'], '/') === false) &&
				     (strpos($query['name'], "'") === false) &&
				     (strpos($query['name'], '?') === false)
				) {
					$postNameForDbCall = $query['name'];
					$prepareQuery = true;

					if ($query['name'] !== rawurlencode($query['name'])) {
						// The URI contains special characters (e.g. from Greek, Romanian, Spanish, etc.)
						$postNameForDbCall = rawurlencode($query['name']);
						$prepareQuery = false;
					}
					//
					// This is a post ('post', 'page', 'attachment' or a custom post type)
					//
					$sqlQuery = <<<SQL
SELECT ID, post_type, post_status FROM `{$wpdb->posts}` WHERE post_name='%s'
SQL;
					if ($prepareQuery) {
						$sqlQueryFinal = $wpdb->prepare( $sqlQuery, array( $postNameForDbCall ) );
					} else {
						$sqlQueryFinal = str_replace('%s', $postNameForDbCall, $sqlQuery);
					}

					$postResult = $wpdb->get_row( $sqlQueryFinal, ARRAY_A );

					if ( ! empty( $postResult ) ) {
						$to_return = $postResult;

						$GLOBALS['wpacu_url_to_page_type'] = $to_return;
						return $to_return;
					}
				} elseif (isset($query['taxonomy']['name'], $query['taxonomy']['value']) && in_array($query['taxonomy']['name'], array('category', 'post_tag'))) {
					//
					// This is a category page (taxonomy: category), thus get the targeted category
					// e.g. if it's a sub-category, make sure to fetch its slug, not the parent category that also has the slug within the URI
					//
					$taxLongSlug = trim($query['taxonomy']['value'], '/');

					if (strpos($taxLongSlug, '/') !== false) {
						$taxSlug = substr($taxLongSlug, strrpos($taxLongSlug, '/') + 1);
					} else {
						$taxSlug = $taxLongSlug;
					}

					$tax = get_term_by( 'slug', $taxSlug, $query['taxonomy']['name'] );

					if (isset($tax->term_id) && $tax->term_id) {
						$to_return = array(
							'ID'          => $tax->term_id,
		                    'tax_data'    => $tax,
		                    'page_type'   => $query['taxonomy']['name'],
							'is_taxonomy' => true
						);

						$GLOBALS['wpacu_url_to_page_type'] = $to_return;
						return $to_return;
					}
				} elseif (isset($query['taxonomy']['name'], $query['taxonomy']['value'], $query['taxonomy']['term_id'])) {
					$to_return = array(
						'ID'              => $query['taxonomy']['term_id'],
	                    'page_type'       => $query['taxonomy']['name'],
	                    'is_taxonomy'     => true
					);

					$GLOBALS['wpacu_url_to_page_type'] = $to_return;
					return $to_return;
				} elseif (isset($query['search']['name'], $query['search']['value'])) {
					$to_return = array(
						'page_type'       => 'search',
						'is_archive_type' => true
					);

					$GLOBALS['wpacu_url_to_page_type'] = $to_return;
					return $to_return;
				} elseif (isset($query['author']['name'], $query['author']['value'], $query['author']['user_id'])) {
					$to_return = array(
						'ID'              => $query['author']['user_id'],
						'page_type'       => 'author',
						'is_archive_type' => true
					);

					$GLOBALS['wpacu_url_to_page_type'] = $to_return;
					return $to_return;
				} elseif (isset($query['date']['name'])) {
					$to_return = array(
						'page_type'       => 'date',
						'is_archive_type' => true
					);

					$GLOBALS['wpacu_url_to_page_type'] = $to_return;
					return $to_return;
				}

				}
		}

		$GLOBALS['wpacu_url_to_page_type'] = array();
		return array();
	}
}

// REST Request
// If "WPACU_LOAD_ON_REST_CALLS" constant is set to "true" on wp-config.php
// Then DO NOT disable Asset CleanUp on REST calls as some experienced users might want to unload useless plugins on those REST calls
// The following code should be placed BEFORE the inclusion of "wp-settings.php" - e.g. require_once(ABSPATH . 'wp-settings.php');
// define('WPACU_LOAD_ON_REST_CALLS', true);
if (assetCleanUpIsRestCall()) {
	// Situation 1: The user has not chosen to load the plugin when REST calls are made
	if (! ( defined('WPACU_LOAD_ON_REST_CALLS') && WPACU_LOAD_ON_REST_CALLS )) {
		add_filter( 'wpacu_plugin_no_load', '__return_true' );
	}

	// Situation 2: The user has chosen to load the plugin when REST calls are made
	// Only allow unloading rules as everything else (e.g. minify CSS/JS) is irrelevant in this case
	elseif ( ! defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') ) {
		define( 'WPACU_ALLOW_ONLY_UNLOAD_RULES', true );
	}
}
