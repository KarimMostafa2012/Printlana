<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @description Credit Card Form
 */
?>
<p><?php echo esc_html($this->get_description()); ?></p>
<?php wp_nonce_field('moyasar-form', 'moyasar-cc-nonce-field'); ?>
<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form"
          style="background:transparent;">
    <div>
        <div id="mysr-modal" class="mysr-modal">
                <div class="mysr-modal-content">
                    <iframe id="mysr-3d-secure-iframe" src="about:blank" frameBorder="0" class="moyasar-iframe" height="100%" width="100%"></iframe>
                </div>
            </div>
        <p class="form-row form-row-wide">
                <label for="moyasar-card-name">
                    <?php echo esc_html(__('Name on card', 'moyasar')); ?>
                    &nbsp;<abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text"  id="moyasar-card-name" aria-label="Name on card"
                           autocomplete="cc-name"
                           placeholder="<?php echo esc_html(__('Name on card', 'moyasar')); ?>"
                           name="cardHolderName"
                           class="input-text"
                    />
                 </span>
            </p>
        <p class="form-row form-row-wide">
                <label for="moyasar-card-number">
                    <?php echo esc_html(__('Card Number', 'moyasar')); ?>
                    &nbsp;<abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text"  id="moyasar-card-number" aria-label="Card number"
                           autocomplete="cc-number"
                           placeholder="1234 5678 9101 1121"
                           name="cardNumber"
                           class="input-text moyasar-number"
                    />
                 </span>
            </p>
        <p class="form-row form-row-wide">
                <label for="moyasar-card-expiry">
                    <?php echo esc_html(__('Expiration', 'moyasar')); ?>
                    &nbsp;<abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text"  id="moyasar-card-expiry" aria-label="Expiration"
                           autocomplete="cc-exp"
                           placeholder="MM / YY"
                           name="cardExpiration"
                           class="input-text moyasar-number"
                    />
                 </span>
            </p>
        <p class="form-row form-row-wide">
                <label for="moyasar-card-cvc">
                    <?php echo esc_html(__('CVC', 'moyasar')); ?>
                    &nbsp;<abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text"  id="moyasar-card-cvc" aria-label="CVC"
                           autocomplete="cc-cvc"
                           placeholder="CVC"
                           name="cardCVC"
                           class="input-text moyasar-number"
                    />
                 </span>
            </p>
    </div>
</fieldset>

