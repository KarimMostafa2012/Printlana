<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Moyasar_Helper_Coupons
{
    /**
     * @param WC_Order $order
     * @param array $payment
     * @return void
     */
    public static function tryApplyCoupon($order, $payment)
    {
        $helper = new Moyasar_Helper_Coupons();
        $helper->tryApplyCouponToOrder($order, $payment);
    }

    /**
     * @param WC_Order $order
     * @param array $payment
     * @return void
     */
    public function tryApplyCouponToOrder($order, $payment)
    {
        if (!isset($payment['metadata']['#coupon_id'])) {
            return;
        }
        $target_grand_total = Moyasar_Currency_Helper::amount_to_major($payment['amount'], $payment['currency']);
        $order_total = $order->get_total();
        $coupon_name = $payment['metadata']['#coupon_code'] . " (" . $payment['metadata']['#coupon_discount'] . "%)";

        // Calculate needed discount
        $discount_amount = $order_total - $target_grand_total;
        // If Order has discounted item drop
        $items = $order->get_items();
        foreach ($items as $item) {
           if ($item->get_name() === $coupon_name) {
               moyasar_logger(sprintf(
                   "[Moyasar] [Coupons] Coupon %s already applied to order #%d",
                   $payment['metadata']['#coupon_code'],
                   $order->get_id()
               ), 'info', $order->get_id());
               return;
           }
        }
        $prod = new WC_Order_Item_Product();
        $prod->set_name($coupon_name);
        $prod->set_tax_class('0');
        $prod->set_total(-$discount_amount);
        $prod->set_subtotal(-$discount_amount);
        $order->add_item($prod);

        $order->calculate_totals();
        $order->save();
        moyasar_logger(sprintf(
            "[Moyasar] [Coupons] Applied coupon %s with discount %s to order #%d",
            $payment['metadata']['#coupon_code'],
            $discount_amount,
            $order->get_id()
        ), 'info', $order->get_id());

    }

}
