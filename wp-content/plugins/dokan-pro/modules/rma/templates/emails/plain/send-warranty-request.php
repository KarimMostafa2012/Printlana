<?php
/**
 * New RMA Warranty Request Email.
 *
 * An email sent to the vendor when a request is sent to vendor.
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! $data ) {
    return;
}

$details    = $data['details'] ?? '';
$type       = isset( $data['type'] ) ? ucwords( $data['type'] ) : '';
$order_id   = $data['order_id'] ?? '';
$reason     = isset( $data['reasons'] ) ? ucwords( $data['reasons'] ) : '';
$order_link = esc_url(
    add_query_arg(
        [
            'order_id'   => $order_id,
            '_view_mode' => 'email',
            'permission' => '1',
        ], dokan_get_navigation_url( 'orders' )
    )
);
echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

esc_html_e( 'Hello there,', 'dokan' );
echo " \n\n";

// translators: Customer name.
printf( __( 'A new refunds and return request is made by %s', 'dokan' ), esc_html( wp_strip_all_tags( wptexturize( $replace['{customer_name}'] ) ) ) );
echo " \n\n";

esc_html_e( 'Summary of the Refund Request:', 'dokan' );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: Request type.
printf( __( 'Request Type: %s', 'dokan' ), esc_html( wp_strip_all_tags( wptexturize( $type ) ) ) );
echo " \n";

// translators: Request Details.
printf( __( 'Request Details: %s', 'dokan' ), esc_html( wp_strip_all_tags( wptexturize( $details ) ) ) );
echo " \n";

// translators: Order ID.
printf( __( 'Order ID: %s', 'dokan' ), $order_id );
echo " \n";
echo esc_url( $order_link );
echo " \n";

// translators: Reason.
printf( __( 'Reason: %s', 'dokan' ), $reason );
echo " \n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
