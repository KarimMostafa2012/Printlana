<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Class
 *
 * @since 3.11.1 Migrated to Class.
 */
class Helper {
    /**
     * Get verified status Icon list
     *
     * @since 3.11.1
     *
     * @return array
     */
    public static function get_verified_icons(): array {
        $icons = [
            'check_circle_solid'   => '<i class="fas fa-check-circle"></i>',
            'check_circle_regular' => '<i class="far fa-check-circle"></i>',
            'check_solid'          => '<i class="fas fa-check"></i>',
            'check_double_solid'   => '<i class="fas fa-check-double"></i>',
            'check_square_solid'   => '<i class="fas fa-check-square"></i>',
            'check_squire_regular' => '<i class="far fa-check-square"></i>',
            'user_check_solid'     => '<i class="fas fa-user-check"></i>',
            'certificate_solid'    => '<i class="fas fa-certificate"></i>',
        ];

        return apply_filters( 'dokan_pro_vendor_verification_verified_store_icon', $icons );
    }

    /**
     * Get the translated version of approval statuses
     *
     * @since 3.5.4
     *
     * @param $status
     *
     * @return string Translated Status
     */
    public static function get_translated_status( $status ) {
        switch ( $status ) {
            case VerificationRequest::STATUS_APPROVED:
                return __( 'Approved', 'dokan' );
            case VerificationRequest::STATUS_PENDING:
                return __( 'Pending', 'dokan' );
            case VerificationRequest::STATUS_REJECTED:
                return __( 'Rejected', 'dokan' );
            default:
                return $status;
        }
    }
}
