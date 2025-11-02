<?php

namespace WeDevs\DokanPro\Admin;

use WeDevs\DokanPro\Modules\TableRate\DokanGoogleDistanceMatrixAPI;
use WeDevs\DokanPro\Storage\Session;

/**
 * Ajax handling for Dokan in Admin area
 *
 * @since  2.2
 *
 * @author weDevs <info@wedevs.com>
 */
class Ajax {

    /**
     * Load automatically all actions
     */
    public function __construct() {
        add_action( 'wp_ajax_regenerate_order_commission', [ $this, 'regenerate_order_commission' ] );
        add_action( 'wp_ajax_check_duplicate_suborders', [ $this, 'check_duplicate_suborders' ] );
        add_action( 'wp_ajax_rewrite_product_variations_author', [ $this, 'rewrite_product_variations_author' ] );
        add_action( 'wp_ajax_dokan_get_distance_btwn_address', [ $this, 'get_distance_btwn_address' ] );
    }

    /**
     * Regenerate order commission data.
     *
     * @since 3.9.3
     *
     * @return void
     */
    public function regenerate_order_commission() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }

        $bg_processor = dokan_pro()->bg_process->regenerate_order_commission;

        $args = [
            'paged' => 1,
        ];

        $bg_processor->push_to_queue( $args )->save()->dispatch();

        wp_send_json_success(
            [
                'process' => 'running',
                'message' => __( 'Your orders have been successfully queued for processing. You will be notified once the task has been completed.', 'dokan' ),
            ]
        );
    }

    /**
     * Remove duplicate sub-orders if found
     *
     * @since 2.4.4
     *
     * @return void
     */
    public function check_duplicate_suborders() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }

        // get session object
        $session = new Session( 'duplicate_suborders' );

        $limit        = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 0;
        $offset       = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
        $prev_done    = isset( $_POST['done'] ) ? absint( $_POST['done'] ) : 0;
        $total_orders = isset( $_POST['total_orders'] ) ? absint( $_POST['total_orders'] ) : 0;

        $query_args = [
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key'     => 'has_sub_order',
                    'value'   => '1',
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ];

        if ( $offset === 0 ) {
            $session->forget_session();
            $query_args['return'] = 'count';
            unset( $query_args['limit'] );
            unset( $query_args['offset'] );
            $total_orders = dokan()->order->all( $query_args );
        }

        $query_args['return'] = 'ids';
        $query_args['limit'] = $limit;
        $query_args['paged'] = $offset + 1;

        $orders           = dokan()->order->all( $query_args );
        $duplicate_orders = null !== $session->get( 'dokan_duplicate_order_ids' ) ? $session->get( 'dokan_duplicate_order_ids' ) : [];

        if ( empty( $orders ) ) {
            $dashboard_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=dokan' ), __( 'Go to Dashboard &rarr;', 'dokan' ) );
            $message        = count( $duplicate_orders ) ?
                // translators: %s: dashboard link
                sprintf( __( 'All orders are checked and we found some duplicate orders. %s', 'dokan' ), $dashboard_link ) :
                // translators: %s: dashboard link
                sprintf( __( 'All orders are checked and no duplicate was found. %s', 'dokan' ), $dashboard_link );

            $data = [
                'offset'  => 0,
                'done'    => 'All',
                'message' => $message,
            ];

            if ( count( $duplicate_orders ) ) {
                $data['duplicate'] = true;
            }

            wp_send_json_success( $data, 200 );
        } else {
            foreach ( $orders as $order_id ) {
                $sellers_count = count( dokan_get_sellers_by( $order_id ) );
                $sub_order_ids = dokan_get_suborder_ids_by( $order_id );

                if ( $sellers_count < count( $sub_order_ids ) ) {
                    $duplicate_orders = array_merge( array_slice( $sub_order_ids, $sellers_count ), $duplicate_orders );
                }
            }
        }

        if ( count( $duplicate_orders ) ) {
            $session->set( 'dokan_duplicate_order_ids', $duplicate_orders );
        }

        $done = $prev_done + count( $orders );

        wp_send_json_success(
            [
                'offset'       => $offset + 1,
                'total_orders' => $total_orders,
                'done'         => $done,
                // translators: %1$d: done orders, %2$d: total orders
                'message'      => sprintf( __( '%1$d orders checked out of %2$d', 'dokan' ), $done, $total_orders ),
            ]
        );
    }

    /**
     * Rewrite product variations author via ajax.
     *
     * @since 3.7.13
     *
     * @return void
     */
    public function rewrite_product_variations_author() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }

        $page         = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $bg_processor = dokan()->bg_process->rewrite_variable_products_author;

        $args = [
            'updating' => 'dokan_update_variable_product_variations_author_ids',
            'page'     => $page,
        ];

        $bg_processor->push_to_queue( $args )->save()->dispatch();

        wp_send_json_success(
            [
                'process' => 'running',
                'message' => __( 'Variable product variations author ids rewriting queued successfully', 'dokan' ),
            ]
        );
    }

    /**
     * Get distance between two address to check if Distance Matrix API is working or not
     *
     * @since 3.7.21
     *
     * @return void
     */
    public function get_distance_btwn_address() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }

        // check if module active
        if ( ! dokan_pro()->module->is_active( 'table_rate_shipping' ) ) {
            wp_send_json_error( __( 'Table Rate Shipping module is not active', 'dokan' ), 403 );
        }

        $address1 = isset( $_POST['address1'] ) ? sanitize_text_field( wp_unslash( $_POST['address1'] ) ) : '';
        $address2 = isset( $_POST['address2'] ) ? sanitize_text_field( wp_unslash( $_POST['address2'] ) ) : '';

        if ( empty( $address1 ) ) {
            wp_send_json_error( __( 'Address 1 is empty', 'dokan' ), 403 );
        }

        if ( empty( $address2 ) ) {
            wp_send_json_error( __( 'Address 2 is empty', 'dokan' ), 403 );
        }

        // check if API key is set
        $gmap_api_key = trim( dokan_get_option( 'gmap_api_key', 'dokan_appearance', '' ) );
        if ( empty( $gmap_api_key ) ) {
            wp_send_json_error( __( 'Google Map API key is not set', 'dokan' ), 403 );
        }

        $api      = new DokanGoogleDistanceMatrixAPI( $gmap_api_key, false );
        $distance = $api->get_distance(
            $address1,
            $address2,
            false
        );

        if ( isset( $distance->status ) && 'OK' === $distance->status ) {
            $message = __( 'Distance Matrix API is enabled.', 'dokan' );
            wp_send_json_success( $message );
        }

        $message = sprintf(
            '<strong>%s:</strong> %s, <strong>%s:</strong> %s',
            __( 'Error Code', 'dokan' ),
            $distance->status,
            __( 'Error Message', 'dokan' ),
            $distance->error_message
        );

        wp_send_json_error( $message, 403 );
    }
}
