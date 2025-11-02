<?php 

class pisol_corw_wallet_refund{

    static $instance = null;

    static function get_instance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    static function has_supported_wallet(){
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if(is_plugin_active( 'woo-wallet/woo-wallet.php')) return 'terra-wallet';

        if(is_plugin_active( 'add-coupon-by-link-for-woocommerce/add-coupon-by-link-woocommerce.php')) return 'add-coupon-by-link-woocommerce';

        return false;
    }

    static function waller_refund_enabled(){
        $enabled = get_option('pi_corw_enable_wallet_refund', 0);

        if(!empty($enabled) && self::has_supported_wallet() && is_user_logged_in()) return true;

        return false;
    }

    static function give_wallet_refund_option(){
        $user_given_choice = get_option('pi_corw_user_choice_for_wallet_refund', 'wallet-refund');

        if($user_given_choice == 'give-option') return true;

        return false;
    }

    static function user_opted_for_wallet_refund($order){
        $user_given_choice = get_option('pi_corw_user_choice_for_wallet_refund', 'wallet-refund');

        if($user_given_choice == 'wallet-refund') return true;

        $wallet_refund = $order->get_meta( 'do_wallet_refund', true);

        if(!empty($wallet_refund)) return true;

        return false;
    }

    static function refund_in_wallet($order, $refund_amount){
        $wallet = self::has_supported_wallet();

        if($wallet == 'terra-wallet' && function_exists('woo_wallet')){
            $refund_reason =  __( 'Wallet refund #', 'woo-wallet' ) . $order->get_order_number();

            $transaction_id = woo_wallet()->wallet->credit( $order->get_customer_id(), $refund_amount, $refund_reason, array( 'currency' => $order->get_currency( 'edit' ), 'for' => 'refund' ) );

            $order->add_order_note(sprintf(__('Wallet refund of %s credited. Transaction ID: %s', 'cancel-order-request-woocommerce'), wc_price($refund_amount), $transaction_id));

        }elseif($wallet == 'add-coupon-by-link-woocommerce'){
            $coupon_code = 'refund-' . $order->get_id().'-'.wp_generate_password(6, false);

            $coupon = new WC_Coupon();
            $coupon->set_code($coupon_code);
            $coupon->set_discount_type('pisol_acblw_store_credit');
            $coupon->set_amount($refund_amount);
            $order_no = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order->get_id();
            $coupon->set_description(sprintf(__('Store credit coupon given as a refund for order #%s', 'cancel-order-request-woocommerce'), $order_no));
            //get billing email and set as the email for the coupon
            $emails = array();
            $emails[] = $order->get_billing_email();
            if(is_user_logged_in(  )){
                //get the email assiciated with the login profile
                $user_profile = wp_get_current_user();
                if(isset($user_profile->user_email) && !empty($user_profile->user_email)){
                    $emails[] = $user_profile->user_email;
                }
            }
            $emails = array_unique($emails);
            $coupon->set_email_restrictions($emails);
            $coupon->save();

            $order->add_order_note(sprintf(__('Store credit coupon %s of %s amount was for email id %s as a refund', 'cancel-order-request-woocommerce'), $coupon_code, wc_price($refund_amount), implode(', ', $emails)));

            $email_desc = sprintf(__('This coupon was given to you as a refund for the order #%s', 'cancel-order-request-woocommerce'), $order->get_id());
            do_action( 'woocommerce_store_credit_assigned', $coupon_code, $email_desc );
        }
    }
}