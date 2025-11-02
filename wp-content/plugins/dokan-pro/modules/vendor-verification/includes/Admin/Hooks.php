<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Admin;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Class
 *
 * @since 3.11.1 Migrated to Class.
 */
class Hooks {

    /**
     * Class Constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        add_action( 'dokan_admin_menu', [ $this, 'load_verification_admin_menu' ], 11, 2 );
        add_filter( 'dokan-admin-routes', [ $this, 'vue_admin_routes' ] );
        add_filter( 'dokan_new_seller_enable_selling_statuses', [ $this, 'add_verified_enabled_status' ] );
    }

    /**
     * Load Admin menu
     *
     * @since unknown
     * @since 3.11.1 Verification vue page support added.
     *
     * @param string  $capability
     * @param integer $menu_position
     *
     * @return void
     */
    public function load_verification_admin_menu( $capability, $menu_position ) {
        global $submenu;

        $request_count = ( new VerificationRequest() )->count();
        $menu_text     = __( 'Verifications', 'dokan' );
        $slug          = 'dokan';

        if ( $request_count[ VerificationRequest::STATUS_PENDING ] ) {
            // translators: %s: amount of pending verification
            $menu_text = sprintf( __( 'Verifications %s', 'dokan' ), '<span class="awaiting-mod count-1"><span class="pending-count">' . number_format_i18n( $request_count[ VerificationRequest::STATUS_PENDING ] ) . '</span></span>' );
        }

        if ( current_user_can( $capability ) ) {
            $submenu[ $slug ][] = [ $menu_text, $capability, 'admin.php?page=' . $slug . '#/verifications?status=' . VerificationRequest::STATUS_PENDING ]; // phpcs:ignore
        }
    }

    /**
     * Add vue routes for admin pages.
     *
     * @since 3.11.1
     *
     * @param array $routes Array of routes.
     *
     * @return array
     */
    public function vue_admin_routes( $routes ): array {
        $routes[] = [
            'path'      => '/verifications',
            'name'      => 'Verifications',
            'component' => 'Verifications',
        ];

        return $routes;
    }

    /**
     * Add verified status.
     *
     * @since 4.0.3
     *
     * @param $statuses
     *
     * @return array
     */
    public function add_verified_enabled_status( $statuses ) {
        return array_merge( $statuses, [ 'verified_only' => __( 'Verified Only', 'dokan' ) ] );
    }
}
