<?php
/**
 * Dokan Dashboard Product Listing
 * filter template
 *
 * @var int|string           $product_cat
 * @var int|string           $product_brand
 * @var array<string,string> $product_types
 * @var string               $product_search_name
 * @var string|int           $date
 * @var string               $product_type
 * @var string               $filter_by_other
 * @var string               $post_status
 *
 * @since 2.4
 */

do_action( 'dokan_product_listing_filter_before_form' );
?>

    <form class="dokan-form-inline dokan-product-date-filter" method="get" ><!-- Delete dokan-w8 class -->
        <?php do_action( 'dokan_product_listing_filter_from_start', [] ); ?>
        <div class="dokan-form-group">
            <?php dokan_product_listing_filter_months_dropdown( dokan_get_current_user_id() ); ?>
            <svg class="select-arrow-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M7.41 8.58002L12 13.17L16.59 8.58002L18 10L12 16L6 10L7.41 8.58002Z" fill="#0044F1"/>
            </svg><!-- Add SVG -->
        </div>

        <div class="dokan-form-group">
            <?php
            wp_dropdown_categories(
                apply_filters(
                    'dokan_product_cat_dropdown_args',
                    [
                        'show_option_none' => __( 'Select category', 'dokan-lite' ),
                        'hierarchical'     => 1,
                        'hide_empty'       => 0,
                        'name'             => 'product_cat',
                        'id'               => 'product_cat',
                        'taxonomy'         => 'product_cat',
                        'orderby'          => 'name',
                        'order'            => 'ASC',
                        'title_li'         => '',
                        'class'            => 'product_cat dokan-form-control chosen',
                        'exclude'          => '',
                        'selected'         => $product_cat,
                    ]
                )
            );
            ?>
            <svg class="select-arrow-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M7.41 8.58002L12 13.17L16.59 8.58002L18 10L12 16L6 10L7.41 8.58002Z" fill="#0044F1"/>
            </svg><!-- Add SVG -->
        </div>

        <?php if ( is_array( $product_types ) ) : ?>
            <div class="dokan-form-group">
                <select name="product_type" id="filter-by-type" class="dokan-form-control dokan-hide" style="max-width:140px;"> <!-- Hide filter Product type -->
                    <option value=""><?php esc_html_e( 'Product type', 'dokan-lite' ); ?></option>
                    <?php foreach ( $product_types as $type_key => $p_type ) : ?>
                        <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $product_type, $type_key ); ?>>
                            <?php echo esc_html( $p_type ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="dokan-form-group dokan-hide"> <!-- Hide filter Select a brand -->
            <?php
            wp_dropdown_categories(
                apply_filters(
                    'dokan_product_brand_dropdown_args',
                    [
                        'show_option_none' => __( '- Select a brand -', 'dokan-lite' ),
                        'hierarchical'     => 1,
                        'hide_empty'       => 0,
                        'name'             => 'product_brand',
                        'id'               => 'product_brand',
                        'taxonomy'         => 'product_brand',
                        'orderby'          => 'name',
                        'order'            => 'ASC',
                        'title_li'         => '',
                        'class'            => 'product_brand dokan-form-control chosen',
                        'exclude'          => '',
                        'selected'         => $product_brand,
                    ]
                )
            );
            ?>
        </div>

        <?php do_action( 'dokan_product_listing_filter_from_end', [] ); ?>

        <?php if ( ! empty( $product_search_name ) ) : ?>
            <input type="hidden" name="product_search_name" value="<?php echo esc_attr( $product_search_name ); ?>">
        <?php endif; ?>

        <?php if ( ! empty( $post_status ) ) : ?>
            <input type="hidden" name="post_status" value="<?php echo esc_attr( $post_status ); ?>">
        <?php endif; ?>

        <?php wp_nonce_field( 'product_listing_filter', '_product_listing_filter_nonce', false ); ?>

        <div class="dokan-form-group">
            <button type="submit" class="dokan-btn"><?php esc_html_e( 'Apply', 'dokan-lite' ); ?></button> <!-- Change from Filter to Apply -->
            <a class="dokan-btn" href="<?php echo esc_attr( dokan_get_navigation_url( 'products' ) ); ?>"><?php esc_html_e( 'Reset', 'dokan-lite' ); ?></a>
        </div>
    </form>

    <?php do_action( 'dokan_product_listing_filter_before_search_form' ); ?>

    <form method="get" class="dokan-form-inline dokan-product-search-form"><!-- Delete dokan-w5 class -->

        <button type="submit" name="product_listing_search" value="ok" class="dokan-btn dokan-btn-theme"><?php esc_html_e( 'Search', 'dokan-lite' ); ?></button>

        <?php wp_nonce_field( 'product_listing_filter', '_product_listing_filter_nonce', false ); ?>

        <div class="dokan-form-group my-search-field"> <!-- Add class my-search-field and Add SVG icon-->
            <input type="text" class="dokan-form-control" name="product_search_name" placeholder="<?php esc_html_e( 'Search Products', 'dokan-lite' ); ?>" value="<?php echo esc_attr( $product_search_name ); ?>">
            <svg class="search-input-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><g id="mdi:search"><path id="Vector" d="M9.5 3C11.2239 3 12.8772 3.68482 14.0962 4.90381C15.3152 6.12279 16 7.77609 16 9.5C16 11.11 15.41 12.59 14.44 13.73L14.71 14H15.5L20.5 19L19 20.5L14 15.5V14.71L13.73 14.44C12.5504 15.4465 11.0506 15.9996 9.5 16C7.77609 16 6.12279 15.3152 4.90381 14.0962C3.68482 12.8772 3 11.2239 3 9.5C3 7.77609 3.68482 6.12279 4.90381 4.90381C6.12279 3.68482 7.77609 3 9.5 3ZM9.5 5C7 5 5 7 5 9.5C5 12 7 14 9.5 14C12 14 14 12 14 9.5C14 7 12 5 9.5 5Z" fill="#808089"></path></g></svg>
        </div>

        <input type="hidden" name="product_cat" value="<?php echo esc_attr( $product_cat ); ?>">

        <?php if ( ! empty( $date ) ) : ?>
            <input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
        <?php endif; ?>

        <?php if ( ! empty( $product_type ) ) : ?>
            <input type="hidden" name="product_type" value="<?php echo esc_attr( $product_type ); ?>">
        <?php endif; ?>

        <?php if ( ! empty( $post_status ) ) : ?>
            <input type="hidden" name="post_status" value="<?php echo esc_attr( $post_status ); ?>">
        <?php endif; ?>
    </form>

    <?php do_action( 'dokan_product_listing_filter_after_form' ); ?>
