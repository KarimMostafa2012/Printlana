/**
 * @description Moyasar Triggers
 */
const __mysr_triggers = window.wp.i18n.__

class MoyasarTriggers {

    /**
     * @description Register the payment methods to prevent multiple events
     * use-case: apply coupon code this will re-render the payment box
     * @type {[]}
     */
    static registeredMethods = []

    /**
     * @description Register Submit Event
     * @param method
     */
    static registerForm(method) {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        // Loop through the registered methods and remove the event
        MoyasarTriggers.registeredMethods.forEach((method) => {
            checkoutForm.off('checkout_place_order', method);
        });
        MoyasarTriggers.registeredMethods = [];
        checkoutForm.on('checkout_place_order', method);
        MoyasarTriggers.registeredMethods.push(method)
    }

    /**
     * @description un register Submit Event
     * @param method
     */
    static unRegisterForm(method) {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        checkoutForm.off('checkout_place_order', method);
    }

    /**
     * @description Detect if the selected payment method then trigger the callback
     * @param methodId
     * @param cb
     * @returns {boolean}
     */
    static detectSelectedPaymentMethod(methodId, cb) {
        // Add event on change
        document.querySelectorAll('input[id^="payment_method"]').forEach((element) => {
            element.addEventListener('change', (event) => {
                let selectedPaymentMethod = event.target.value;
                if (selectedPaymentMethod === methodId) {
                    cb(true);
                } else {
                    cb(false);
                }
            });
        });
    }

    /**
     * @description Return the selected payment method
     * @returns {*}
     */
    static selectedPaymentMethod() {
        const checkoutForm = jQuery('form.woocommerce-checkout')
        const val = checkoutForm.find('input[name="payment_method"]:checked').val()
        if (!val) {
            return ''
        }
        // Remove spaces
        return val.replace(/\s/g, '');
    }

    /**
     * @description Add error message to the form/payment box
     * @param error_messages
     */
    static submitError(error_messages) {
        let randomId = 'mysr_' + Math.floor(Math.random() * 1000)
        // Loop through the error messages and make them li
        let error_message = ''
        error_messages.forEach((message) => {
            error_message += '<li>' + __mysr_triggers(message, 'moyasar') + '</li>'
        });

        const checkoutForm = jQuery('form.woocommerce-checkout')
        // Get Current Payment Method
        const block = checkoutForm.find('input[name="payment_method"]:checked')
        // Div in block
        const div = block.parent().find('div').first()
        // Remove the error message
        div.find('.moyasar-error-message').remove()

        if (div.length > 0) {
            div.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-error moyasar-error-message ' + randomId + '"><ul class="woocommerce-error" role="alert">' + error_message + '</ul></div>')
        } else {
            checkoutForm.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-error moyasar-error-message ' + randomId + '"><ul class="woocommerce-error" role="alert">' + error_message + '</ul></div>')
        }

        // Scroll to the error
        jQuery('html, body').animate({
            scrollTop: jQuery('.' + randomId).offset().top - 100
        }, 1000);
    }

    /**
     * @description Clear the error message
     */
    static clearError() {
        const checkoutForm = jQuery('form.woocommerce-checkout')
        checkoutForm.find('.moyasar-error-message').remove()
    }

    /**
     * @description Add info message to the form/payment box
     * @param info_messages
     */
    static submitInfo(info_messages) {
        // Loop through the error messages and make them li
        let info_message = ''
        info_messages.forEach((message) => {
            info_message += '<li>' + __mysr_triggers(message, 'moyasar') + '</li>'
        });

        const checkoutForm = jQuery('form.woocommerce-checkout')
        // Get Current Payment Method
        const block = checkoutForm.find('input[name="payment_method"]:checked')
        // Div in block
        const div = block.parent().find('div').first()
        // Remove the error message
        div.find('.moyasar-info-message').remove()

        if (div.length > 0) {
            div.prepend('<div class="woocommerce-message moyasar-info-message">' + info_message + '</div>')
        } else {
            checkoutForm.prepend('<div class="woocommerce-message moyasar-info-message">' + info_message + '</div>')
        }

        // Scroll to the error
        jQuery('html, body').animate({
            scrollTop: jQuery('.moyasar-info-message').offset().top - 100
        }, 1000);
    }

    /**
     * @description Submit Form Error
     * @param error_message
     */
    static submitFormError(error_message) {
        const checkoutForm = jQuery('form.woocommerce-checkout')
        jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message, .is-error, .is-success').remove();
        checkoutForm.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>'); // eslint-disable-line max-len
        checkoutForm.removeClass('processing').unblock();
        checkoutForm.find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');
        jQuery('html, body').animate({
            scrollTop: jQuery('.woocommerce-NoticeGroup').offset().top - 100
        }, 1000);
        jQuery(document.body).trigger('checkout_error', [error_message]);
    }

    /**
     * @description Get Place Order Button (Blocks or Classic)
     * @returns {any}
     */
    static getPlaceOrderButton() {
        let baseButton = document.getElementsByClassName('wc-block-components-checkout-place-order-button');

        // Blocks
        if (baseButton.length > 0) {
            baseButton = baseButton[0];
        } else { // Classic
            baseButton = document.getElementById('place_order')
        }
        return baseButton;
    }

    /**
     * @description Check if moyasar submit button set
     */
    static isMoyasarSubmitButtonSet() {
        const button = MoyasarTriggers.getPlaceOrderButton();
        if (button === null) {
            return false;
        }

        return button.classList.contains('moyasar-submit-button');
    }


    /**
     * @description Reset the Place Order Button
     */
    static resetPlaceOrderButton() {
        const button = MoyasarTriggers.getPlaceOrderButton();
        if (button) {
            button.classList.remove('moyasar-submit-button');
            button.value = button.dataset.mysrOldText;
        }
    }

    /**
     * @description Set Samsung Pay Button, hide place order button and set the callback
     */
    static setMoyasarSubmitButton(amount = '') {
        const button = MoyasarTriggers.getPlaceOrderButton();
        if (!button) {
            setTimeout(() => MoyasarTriggers.setMoyasarSubmitButton(amount), 300);
            return;
        }
        if (MoyasarTriggers.isMoyasarSubmitButtonSet()) {
            return;
        }

        const orderTotalElement = document.querySelector('.order-total .woocommerce-Price-amount'); // Sometimes used for amount
        const currencySymbolElement = document.querySelector('.woocommerce-Price-currencySymbol'); // Sometimes used for currency
        if (amount === ''){
            amount = orderTotalElement ? orderTotalElement.textContent.trim() : "0";
        }
        let currencySymbol = currencySymbolElement ? currencySymbolElement.textContent.trim() : "SAR";


        // Ensure amount is formatted correctly
        amount = amount.replace(/[^\d.,]/g, ''); // Remove non-numeric characters
        // Remove last dot
        amount = amount.replace(/\.+$/, '');


        // If it is SAR replace it with new logo
        if (currencySymbol === "SAR" || currencySymbol === "ر.س"|| currencySymbol === "ريال") {
            currencySymbol = `
                    <svg 
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 1124.14 1256.39"
                      fill="currentColor"
                      style="width: 1.3em; height: 1em; vertical-align: -0.1em;"
                    >
                      <path d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z" />
                      <path d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z" />
                    </svg>
                    `;
        }
        let newText = sprintf(__mysr_triggers("Pay %s %s", 'moyasar'), amount, currencySymbol);
        // Check is RTL?
        if (document.dir !== 'rtl') {
            newText = sprintf(__mysr_triggers("Pay %s %s", 'moyasar'), currencySymbol, amount);
        }
        // Get the button text
        const buttonText = button.value;
        button.classList.add('moyasar-submit-button');
        // Update button text the amount
        button.innerHTML = newText;
        // Save old button text in data
        button.dataset.mysrOldText = buttonText;
    }

}

