<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<style>
.checkout-order-summary {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 520px;
  background-color: #F2F5FF;
  padding: 0;
  border-radius: 12px;
  font-family: var(--e-global-typography-primary-font-family);
  line-height: 150%;
}
.checkout-summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 16px;
}

.checkout-product {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.checkout-product img:not(.remove-icon) {
  width: 150px;
  height: 150px;
  border-radius: 8px;
  object-fit: cover;
}

.checkout-product-details {
  max-width: 220px;
  font-size: 16px;
  line-height: 150%;
}

.checkout-product-details .wc-item-meta {
  padding: 0px;
  list-style: none;
  font-size: 12px;
  color: #797B89;
}

.checkout-product-details .variation dt {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.checkout-product-details .wc-item-meta li {
  display: flex;
}

.checkout-product-details .wc-item-meta li .wc-item-meta-label {
  margin-right: 5px !important;
  font-weight: 400;
}

.checkout-product-price {
  color: var(--e-global-color-89f6c54);
  font-weight: 600;
  font-size: 14px;
  margin-top: 4px;
}

.checkout-total-row {
  font-weight: 600;
}

.checkout-product-quantity {
  font-size: 12px;
  margin: 0;
}
</style>

<div class="checkout-order-summary">
  <?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
  
  <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">
  
  <?php
  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

    if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
      $image = wp_get_attachment_image_src($_product->get_image_id(), 'thumbnail');
      ?>
      <div class="checkout-product <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
        <?php if ($image): ?>
          <img src="<?php echo esc_url($image[0]); ?>" alt="<?php echo esc_attr($_product->get_name()); ?>">
        <?php endif; ?>
        
        <div class="checkout-product-details">
          <strong><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?></strong><br>
          <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
          <p class="checkout-product-quantity"><?php echo esc_html($cart_item['quantity']); ?> Items</p>
          <div class="checkout-product-price">
            <?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
          </div>
        </div>
      
      <div class="checkout-product-remove">
    <?php
    $remove_icon_url = wp_get_attachment_url( 2884 );
    $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
    
    echo apply_filters(
      'woocommerce_cart_item_remove_link',
      sprintf(
        '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">
          <img src="%s" alt="%s" class="remove-icon" />
        </a>',
        esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
        esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $_product->get_name() ) ) ),
        esc_attr( $product_id ),
        esc_attr( $_product->get_sku() ),
        esc_url( $remove_icon_url ),
        esc_attr__( 'Remove item', 'woocommerce' )
      ),
      $cart_item_key
    );
    ?>
  </div>
      </div>
      <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">
      <?php
    }
  }
  ?>

  <?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

  <!-- Subtotal -->
  <div class="checkout-summary-row">
    <span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
    <span><?php wc_cart_totals_subtotal_html(); ?></span>
  </div>

  <!-- Coupons -->
  <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
    <div class="checkout-summary-row coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
      <span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
      <span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
    </div>
  <?php endforeach; ?>

  <!-- Shipping -->
  <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
    <?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
    <?php wc_cart_totals_shipping_html(); ?>
    <?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
  <?php endif; ?>

  <!-- Fees -->
  <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
    <div class="checkout-summary-row fee">
      <span><?php echo esc_html( $fee->name ); ?></span>
      <span><?php wc_cart_totals_fee_html( $fee ); ?></span>
    </div>
  <?php endforeach; ?>

  <!-- Taxes -->
  <?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
    <?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
      <?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
        <div class="checkout-summary-row tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
          <span><?php echo esc_html( $tax->label ); ?></span>
          <span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
        </div>
      <?php endforeach; ?>
    <?php else : ?>
      <div class="checkout-summary-row">
        <span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
        <span><?php wc_cart_totals_taxes_total_html(); ?></span>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

  <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">

  <!-- Order Total -->
  <div class="checkout-summary-row checkout-total-row">
    <span><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
    <span><?php wc_cart_totals_order_total_html(); ?></span>
  </div>

  <?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

</div>