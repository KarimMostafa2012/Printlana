<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @description This file is used to render the STC Pay form in the checkout page.
 */
?>
<p><?php echo esc_html(($this->get_description())); ?></p>
<?php wp_nonce_field('moyasar-form', 'moyasar-stc-nonce-field'); ?>
<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form"
          style="background:transparent;">

    <div class="">
        <p><?php echo esc_html(__('Please enter your mobile number and press \'Send OTP\'. You will receive an SMS code required to complete the payment process.', 'moyasar')); ?></p>

        <div class="">
            <!-- Sent OTP -->
            <div id="moyasar-phone-number-div" class="form-row form-row-wide">
                <label for="moyasar-phone-number">
                    <?php echo esc_html(__('Phone Number', 'moyasar')); ?>
                    &nbsp;<abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text" id="moyasar-phone-number" aria-label="Phone number"
                           autocomplete="phone"
                           placeholder="05x xxx xxxx"
                           name="phone-number"
                           class="input-text moyasar-number"
                    />
                 </span>

                <button id="moyasar-stc-start-order" class="moyasar-stc-pay-button">
                    <span><?php echo esc_html(__('Send OTP', 'moyasar')); ?></span>
                </button>
            </div>

            <!-- Validate OTP -->
            <div id="moyasar-otp-number-div" class="form-row form-row-wide" style="display: none;">
                <label for="moyasar-otp-number">
                    <?php echo esc_html(__('OTP', 'moyasar')); ?>
                    &nbsp;<abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text" id="moyasar-otp-number" aria-label="OTP number"
                           autocomplete="otp"
                           placeholder="xxx"
                           name="otp-number"
                           class="input-text moyasar-number"
                    />
                 </span>

                <button id="moyasar-stc-submit" class="moyasar-stc-pay-button">
                    <span><?php echo esc_html(__('Submit', 'moyasar')); ?></span>
                </button>
            </div>

        </div>
    </div>

</fieldset>
