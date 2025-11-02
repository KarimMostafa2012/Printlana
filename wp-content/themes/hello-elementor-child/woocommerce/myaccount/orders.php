<?php
/**
 * Orders with Product Cards
 *
 * Shows orders on the account page with product cards display.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<style>

    .woocommerce-MyAccount-content-wrapper{

.order-card {
border-bottom: 1px solid var(--sections-border-color, #d5d8dc);
padding-bottom: 32px;
margin-bottom: 32px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.order-number, .order-date {
    font-weight: 600;
    font-size: 1.125rem;
    line-height: normal;
}


.order-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-processing { background: #fef3c7; color: #d97706; }
.status-completed { background: #d1fae5; color: #059669; }
.status-pending { background: #fed7d7; color: #e53e3e; }
.status-on-hold { background: #e0e7ff; color: #3730a3; }
.status-cancelled { background: #f3f4f6; color: #6b7280; }

.order-total {
    font-weight: 600;
    font-size: 18px;
    color: #1f2937;
}

.products-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: space-between;
    align-items: flex-end;
    margin: 15px 0;
}

.products-grid {
    flex-grow: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, 200px);
    gap: 15px;
}

.product-card {
    padding: 12px;
    border: 1px solid var(--sections-border-color, #d5d8dc);
    border-radius: 6px;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 180px;
    border-radius: 5px;
    object-fit: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.woocommerce-button.button{
    border: 1px solid var(--e-global-color-ee4e79d);
    background-color: white;
    padding: 12px 47px;
    border-radius: 100px;
}

.product-image img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 5px;
    height: 100%;
    width: 100%;
    object-fit: cover;
}

.product-name {
    font-weight: 500;
    font-size: 14px;
    color: #1f2937;
    margin-bottom: 4px;
    line-height: 1.3;
}

.product-details {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.product-price {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.order-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 15px;
    padding-top: 15px;
}

.no-orders-message {
    text-align: center;
    padding: 40px 20px;
    background: #f9fafb;
    border-radius: 8px;
    margin: 20px 0;
}

}
</style>

<?php if ( $has_orders ) : ?>

    <?php foreach ( $customer_orders->orders as $customer_order ) :
        $order = wc_get_order( $customer_order );
        $item_count = $order->get_item_count() - $order->get_item_count_refunded();
    ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-number">
                        <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                            Order No. <?php echo esc_html( $order->get_order_number() ); ?>
                        </a>
                    </div>
                    <div class="order-total">
                        <?php echo $order->get_formatted_order_total(); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="order-date">
                        <time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
                            <?php echo esc_html( $order->get_date_created()->date( 'd.m.Y' ) ); ?>
                        </time>
                    </div>
                    <div class="order-status status-<?php echo esc_attr( $order->get_status() ); ?>">
                        <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                    </div>
                </div>
            </div>

        <div class="products-wrapper">
            <div class="products-grid">
                <?php
                $items = $order->get_items();
                foreach ( $items as $item_id => $item ) :
                    $product = $item->get_product();
                    if ( ! $product ) continue;

                    $product_image = $product->get_image_id();
                    $image_url = $product_image ? wp_get_attachment_image_src( $product_image, 'woocommerce_thumbnail' )[0] : wc_placeholder_img_src();?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
                        </div>
                        <div class="product-info">
                            <div class="product-name">
                                <?php echo esc_html( $product->get_name() ); ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-actions">
                <?php
                $actions = wc_get_account_orders_actions( $order );
                if ( ! empty( $actions ) ) {
                    foreach ( $actions as $key => $action ) {
                        if ( empty( $action['aria-label'] ) ) {
                            $action_aria_label = sprintf( __( '%1$s order number %2$s', 'woocommerce' ), $action['name'], $order->get_order_number() );
                        } else {
                            $action_aria_label = $action['aria-label'];
                        }
                        echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button ' . sanitize_html_class( $key ) . '" aria-label="' . esc_attr( $action_aria_label ) . '" style="margin-right: 10px;">' . esc_html( $action['name'] ) . '</a>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

    <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
            <?php if ( 1 !== $current_page ) : ?>
                <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
            <?php endif; ?>

            <?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
                <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else : ?>

    <div class="no-orders-message">
        <?php wc_print_notice( esc_html__( 'No order has been made yet.', 'woocommerce' ) . ' <a class="woocommerce-Button wc-forward button' . esc_attr( $wp_button_class ) . '" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">' . esc_html__( 'Browse products', 'woocommerce' ) . '</a>', 'notice' ); ?>
    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>