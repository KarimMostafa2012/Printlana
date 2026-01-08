<?php
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( ! function_exists('wpacuIsPremmerceWooCommerceProductPage') ) {
	/**
	 * @param $requestUriAsItIs
	 *
	 * @return array|object|stdClass
	 */
	function wpacuIsPremmerceWooCommerceProductPage($requestUriAsItIs)
	{
		global $wpdb;

		$premmercePermalinkManager = get_option( 'premmerce_permalink_manager' );
		$productSetting            = isset( $premmercePermalinkManager['product'] ) ? $premmercePermalinkManager['product'] : '';

		if ( ! $productSetting ) {
			return array(); // skip it as "Use WooCommerce settings" is used for "Products" within /wp-admin/admin.php?page=premmerce-url-manager-admin
		}

		$url = trim( $requestUriAsItIs, '/' );

		$urlParts = array_reverse( explode( '/', $url ) );

		$slug       = $urlParts[0];
		$productCat = isset( $urlParts[1] ) ? $urlParts[1] : false;

		if ( in_array( $slug, array( 'feed', 'amp' ) ) ) {
			$slug       = $urlParts[1]; // next one
			$productCat = isset( $urlParts[2] ) ? $urlParts[2] : false;
		}

		$commentsPosition = strpos( $slug, 'comment-page-' );

		if ( 0 === $commentsPosition ) {
			$slug       = $urlParts[1]; // next one
			$productCat = isset( $urlParts[2] ) ? $urlParts[2] : false;
		}

		// Is the slug belonging to a WooCommerce product category slug?
		if ( $productCat && in_array( $productSetting, array( 'category_slug', 'hierarchical' ) )
		     && ! $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM `{$wpdb->prefix}terms` WHERE slug='%s'",
				array( rawurlencode( $productCat ) ) ) ) ) {
			return array();
		}

		$sqlQuery = $wpdb->prepare(
			"SELECT ID, post_type FROM {$wpdb->posts} WHERE post_name='%s' AND post_type='%s'",
			array( rawurlencode( $slug ), 'product' )
		);

		$result = $wpdb->get_row( $sqlQuery, ARRAY_A );

		if ( ! empty( $result ) ) {
			return $result;
		}

		return array();
	}
}
