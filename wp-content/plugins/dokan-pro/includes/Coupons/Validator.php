<?php

namespace WeDevs\DokanPro\Coupons;

use WC_Coupon;
use WC_Product;

class Validator {
    /**
     * Admin coupon is valid for current cart items
     *
     * @since 3.4.0
     *
     * @param object $coupon
     * @param array  $vendors
     * @param array  $products
     *
     * @deprecated  4.0.0, Refactor the validation logic, and it will be removed after the first quarter of 2025. Pls use is_coupon_valid_for_product or is_coupon_valid_for_vendor.
     * @return boolean
     */
	public function is_admin_coupon_valid( $coupon, $vendors, $products, $coupon_meta_data = array(), $valid = false ) {
		_deprecated_function( __METHOD__, '4.0.0', 'is_coupon_valid_for_product' );
		return $this->is_coupon_valid( $coupon, $vendors, $products, $coupon_meta_data, $valid );
	}

    /**
     * Admin coupon is valid for current cart items
     *
     * @since 3.4.0
     *
     * @param \WC_Data $coupon                 Coupon object to validate
     * @param array    $vendors_to_validate    Array of vendor IDs to validate
     * @param array    $products_to_validate   Array of product IDs to validate
     * @param array    $coupon_meta_data       Coupon metadata
     * @param bool     $valid                  Initial validation state
     *
     * @deprecated  4.0.0, Refactor the validation logic, and it'll be removed after the first quarter of 2025. Pls use is_coupon_valid_for_product or is_coupon_valid_for_vendor.
     * @return boolean
     */
	public function is_coupon_valid( $coupon, array $vendors_to_validate, array $products_to_validate, array $coupon_meta_data = array(), bool $valid = true ): bool {
		if ( ! $coupon instanceof \WC_Data ) {
			return false;
		}

		if ( ! $coupon instanceof WC_Coupon || ! method_exists( $coupon, 'get_code' ) ) {
			return false;
		}

		$coupon = new WC_Coupon( $coupon->get_code() );

		$is_valid = true;

		if ( count( $products_to_validate ) ) {
			foreach ( $products_to_validate as $product_id ) {
				$is_valid = $is_valid && $this->is_coupon_valid_for_product( $coupon, $product_id, $coupon_meta_data );
			}

			return $is_valid;
		}

		if ( count( $vendors_to_validate ) ) {
			foreach ( $vendors_to_validate as $vendor_id ) {
				$is_valid = $is_valid && $this->is_coupon_valid_for_vendor( $coupon, $vendor_id, $coupon_meta_data );
			}

            return $is_valid;
		}

		return $valid;
    }

    /**
     * Validate if a coupon is applicable for a specific product and its vendor
     *
     * @since 4.0.0
     *
     * @param WC_Coupon      $coupon   The coupon object to validate
     * @param int $product_id  The product to validate against
     *
     * @return boolean True if coupon is valid for the product, false otherwise
     */
    public function is_coupon_valid_for_product( WC_Coupon $coupon, int $product_id, array $coupon_meta_data = array() ): bool {
        $product = wc_get_product( $product_id );

        if ( ! $product instanceof WC_Product ) {
            return false;
        }

        // Get product and vendor details
        $vendor_id = dokan_get_vendor_by_product( $product_id, true );

        // Retrieve coupon metadata or use the provided meta data
        $coupon_data = ! empty( $coupon_meta_data ) ? $coupon_meta_data : dokan_get_admin_coupon_meta( $coupon );

        // Extract coupon settings
        $included_product_ids   = $coupon_data['product_ids'] ?? [];
        $excluded_product_ids   = $coupon_data['excluded_product_ids'] ?? [];
        $parent_product_id      = $product->get_parent_id();

        if ( $parent_product_id ) {
            $parent_product = wc_get_product( $parent_product_id );
            $child_ids = $parent_product->get_children();
            if ( ! empty( $child_ids ) && is_array( $child_ids ) ) {
                $included_product_ids = array_merge( $included_product_ids, $child_ids );
            }
        }

        $is_valid = true; // Default assumption

        $not_valid_for_vendor  = ! $this->is_coupon_valid_for_vendor( $coupon, $vendor_id, $coupon_data );
        $not_included_products = ! in_array( $product_id, $included_product_ids, true );
        // Check if coupon is valid or not for vendor
        if ( $not_valid_for_vendor && $not_included_products ) {
            $is_valid = false;
        } elseif ( $parent_product_id > 0 && in_array( $parent_product_id, $excluded_product_ids, true ) ) { // Check if parent product is excluded
            $is_valid = false;
        } elseif ( in_array( $product_id, $excluded_product_ids, true ) ) { // Check if product is excluded
            $is_valid = false;
        } elseif ( ! empty( $included_product_ids ) && ! in_array( $product_id, $included_product_ids, true ) ) { // If specific products are included, check if current product is in the list
            $is_valid = false;
        }

        return apply_filters( 'dokan_coupon_is_valid_for_product', $is_valid, $coupon, $product, $coupon_meta_data );
    }

    /**
     * Validate if a coupon is applicable for a specific vendor
     *
     * @since 4.0.0
     *
     * @param WC_Coupon $coupon           The coupon object to validate
     * @param int       $vendor_id        The vendor ID to validate
     * @param array     $coupon_meta_data The coupon metadata
     *
     * @return boolean True if coupon is valid for the vendor, false otherwise
     */
    public function is_coupon_valid_for_vendor( WC_Coupon $coupon, int $vendor_id, array $coupon_meta_data = array() ): bool {
        if ( $vendor_id <= 0 ) {
            return false;
        }

        // Retrieve coupon metadata or use the provided meta data
        $coupon_data = ! empty( $coupon_meta_data ) ? $coupon_meta_data : dokan_get_admin_coupon_meta( $coupon );

        $included_vendors_ids = $coupon_data['coupons_vendors_ids'] ?? [];
        $excluded_vendors_ids = $coupon_data['coupons_exclude_vendors_ids'] ?? [];
        $enable_for_vendor    = $coupon_data['admin_coupons_enabled_for_vendor'] ?? '';
        $author_id            = get_post_field( 'post_author', $coupon->get_id() );
        $is_valid             = true;

        // Marketplace coupon logic
        if ( $enable_for_vendor ) {
			if ( 'yes' !== $enable_for_vendor && (int) $author_id !== $vendor_id && ! in_array( $vendor_id, $included_vendors_ids, true ) ) { // If the coupon is not enabled for all vendors and the vendor is not included then return false
				dokan_log( 'Coupon is not valid for vendor: ' . $vendor_id . ' author:' . $author_id . ' because it is not included in the coupon settings.' );
                $is_valid = false;
			} elseif ( 'yes' === $enable_for_vendor && in_array( $vendor_id, $excluded_vendors_ids, true ) ) { // If the coupon is enabled for all vendors and the vendor is excluded then return false
				$is_valid = false;
			}
		} elseif ( (int) $author_id !== $vendor_id ) {
            /**
             * If coupon author is not equal to the vendor ID then return false
             */

                $is_valid = false;
		}

        return apply_filters( 'dokan_coupon_is_valid_for_vendor', $is_valid, $coupon, $vendor_id, $coupon_meta_data );
    }
}
