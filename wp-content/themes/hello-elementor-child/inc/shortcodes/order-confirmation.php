<?php

function render_custom_order_confirmation()
{
  if (!isset($_GET['key'])) return '';

  $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
  $order = wc_get_order($order_id);
  if (!$order) return '';

  ob_start();

  // Get data
  $name = $order->get_formatted_billing_full_name();
  $address = $order->get_formatted_billing_address();
  $phone = $order->get_billing_phone();
  $email = $order->get_billing_email();
  $order_number = $order->get_order_number();
  $order_date = wc_format_datetime($order->get_date_created());
  $shipping_total = wc_price($order->get_shipping_total());
  $tax_total = wc_price($order->get_total_tax());
  $total = $order->get_formatted_order_total();

?>

  <div class="order-confirmation">
    <div class="order-confirmation-container">
      <div class="container-left">
        <h2 class="order-title">Thank you for your purchase!</h2>
        <p class="order-description">
          Your order will be processed within 24 hours during working days.
          We will notify you by email once your order has been shipped.
        </p>

        <div class="order-data">
          <h3 class="section-title">Billing Address</h3>

          <div class="billing-container">
            <div class="billing-field">Name</div>
            <div class="billing-data"><?php echo esc_html($name); ?></div>
          </div>

          <div class="billing-container">
            <div class="billing-field">Address</div>
            <div class="billing-data address-data"><?php echo wp_kses_post($address); ?></div>
          </div>

          <div class="billing-container">
            <div class="billing-field">Phone</div>
            <div class="billing-data"><?php echo esc_html($phone); ?></div>
          </div>

          <div class="billing-container">
            <div class="billing-field">Email</div>
            <div class="billing-data"><?php echo esc_html($email); ?></div>
          </div>

          <a class="track-button" href="<?php echo esc_url(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))); ?>">
            Track Order
          </a>

        </div>
      </div>

      <div class="container-right order-summary">
        <h3 class="summary-header">Order Summary</h3>

        <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">

        <div class="summary-row">
          <span class="summary-row-label">Order Number</span>
          <span class="summary-row-data"><?php echo esc_html($order_number); ?></span>
        </div>

        <div class="summary-row">
          <span class="summary-row-label">Date</span>
          <span class="summary-row-data"><?php echo esc_html($order_date); ?></span>
        </div>

        <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">

        <?php foreach ($order->get_items() as $item):
          $product = $item->get_product();
          if (! $product) continue;
          $image = wp_get_attachment_image_src($product->get_image_id(), 'thumbnail');
        ?>
          <div class="product">
            <img src="<?php echo esc_url($image[0]); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">

            <div class="product-details">
              <strong><?php echo esc_html($item->get_name()); ?></strong>
              <?php
              echo wc_display_item_meta($item, array('echo' => false));
              ?>
              <p style="font-size: 12px;"> <?php echo esc_html($item->get_quantity()); ?> Items</p>
            </div>

            <div class="product-price"><?php echo wc_price($item->get_total()); ?></div>
          </div>
          <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">
        <?php endforeach; ?>

        <div class="summary-row">
          <span class="summary-row-label">Shipping</span>
          <span class="summary-row-data"><?php echo $shipping_total; ?></span>
        </div>

        <div class="summary-row">
          <span class="summary-row-label">Taxes</span>
          <span class="summary-row-data"><?php echo $tax_total; ?></span>
        </div>

        <hr style="margin: 0; height:1px; border-width:1;" color="#B9CDFF">

        <div class="summary-row total-row">
          <span>Order Total</span>
          <span><?php echo $total; ?></span>
        </div>

      </div>
    </div>
  </div>
<?php

  return ob_get_clean();
}
add_shortcode('order_confirmation', 'render_custom_order_confirmation');
