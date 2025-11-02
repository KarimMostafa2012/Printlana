<div class="pi-corw-container">
<form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
    <h4 class="mt-5"><?php printf(__('Request to cancel order #%s', 'cancel-order-request-woocommerce'), $order_no); ?></h4>
    <?php if(!empty($admin_message)): ?>
    <div class="pi-alert-box"><?php echo strip_tags($admin_message, '<br><strong><b>'); ?></div>
    <?php endif; ?>
    
    <?php echo $predefined_reasons; ?>

    <?php if(pisol_corw_wallet_refund::give_wallet_refund_option() && pisol_corw_wallet_refund::waller_refund_enabled()): 
        $wallet_refund_text = get_option('pi_corw_give_refund_in_wallet', __('Credit refund amount in the wallet','cancel-order-request-woocommerce'));    
    ?>
    <label class="pi-wallet-refund" for="do_wallet_refund">
        <input type="checkbox" name="do_wallet_refund" id="do_wallet_refund"value="1" checked="checked" class="mr-5" /> <?php echo $wallet_refund_text; ?>
    </label>
    <?php endif; ?>
 
    <textarea name="order_cancel_reason" placeholder="<?php echo esc_attr(__('Reason for order canceling','cancel-order-request-woocommerce')); ?>" class="mb-10"></textarea>
    <input type="hidden" name="action" value="pi_cancellation_request">
    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
    <input type="hidden" name="redirect_url" value="<?php echo esc_attr($redirect_url); ?>">
    <input type="hidden" name="order_key" value="<?php echo esc_attr($order_key); ?>">
    <?php wp_nonce_field('cancellation_request_'.$order_id, 'pi_cancellation_request_nonce'); ?>
    <input type="submit" class="woocommerce-button button pi-cancel-request-submit-button" value="<?php echo esc_attr(__('Send Cancellation Request', 'cancel-order-request-woocommerce')); ?>">
</form>
</div>