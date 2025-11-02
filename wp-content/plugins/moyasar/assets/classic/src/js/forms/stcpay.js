/**
 * @description Moyasar STC Pay Classic
 */
class moyasarSTCPayClassic {

    /**
     * @description Moyasar STC Pay Classic Instance
     * @type {null}
     */
    static instance = null;

    /**
     * @description Moyasar STC Pay Method ID
     * @type {null}
     */
    static id = null;

    /**
     * @description Moyasar STC Pay Publishable Key
     * @type {null}
     */
    static publishableKey = null;

    /**
     * @description Base URL
     * @type {null}
     */
    static baseUrl = null;

    /**
     * @description Redirect URL
     * @type {null}
     */
    static redirectUrl = null;

    constructor(id, publishableKey, baseUrl) {
        moyasarSTCPayClassic.id = id;
        moyasarSTCPayClassic.publishableKey = publishableKey;
        moyasarSTCPayClassic.baseUrl = baseUrl;
    }

    /**
     * @description Get Instance
     * @param id
     * @param publishableKey
     * @param baseUrl
     * @returns {*|moyasarSTCPayClassic}
     */
    static getInstance(id, publishableKey, baseUrl) {
        if (moyasarSTCPayClassic.instance) {
            return moyasarSTCPayClassic.instance;
        }
        const instance = new moyasarSTCPayClassic(id, publishableKey, baseUrl);
        moyasarSTCPayClassic.instance = instance;
        return instance;
    }

    /**
     * @description Validate Field
     */
    static validateField(field, selector, showError = true) {
        let result = {};

        if (field === 'phone') {
            result = MoyasarValidation.phoneNumber(selector.value);
            selector.value = result.showValue;
        } else if (field === 'otp') {
            result = MoyasarValidation.otp(selector.value);
            selector.value = result.showValue;
        } else {
            return {isValid: false, showValue: ''};
        }


        if (result.isValid === false && showError) {
            MoyasarTriggers.submitError([result.message]);
        } else {
            MoyasarTriggers.clearError();
        }

        return result;
    }

    /**
     * @description Token Request
     */
    static tokenRequest() {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        checkoutForm.addClass('processing').block()
        // Validate Fields
        const values = {
            phone: document.getElementById('moyasar-phone-number')
        }

        let validationResult = moyasarSTCPayClassic.validateField('phone', values.phone);
        if (validationResult.isValid === false) {
            checkoutForm.removeClass('processing').unblock();
            return false;
        }


        checkoutForm.append('<input type="hidden" name="mysr_token" value="' + values.phone.value + '"/>');
        checkoutForm.append('<input type="hidden" name="mysr_form" value="classic"/>');

        // Disable the form submission
        moyasarSTCPayClassic.toggleDisableButtons('disable');
        // Perform the AJAX request
        const order = new MoyasarOrder();
        order.submit(checkoutForm.serialize()).then((response) => {
            // Handle success
            moyasarSTCPayClassic.togglePhoneComponent('hide');
            moyasarSTCPayClassic.toggleOTPComponent('show');
            MoyasarTriggers.submitInfo(['OTP Sent Successfully. Please enter the OTP code you received.'])
            moyasarSTCPayClassic.redirectUrl = response.redirect;
        }).catch((error) => {
            if (error.messages) {
                if (error.messages.includes('div')) {
                    MoyasarTriggers.submitFormError(error.messages)
                } else {
                    MoyasarTriggers.submitError([error.messages]);
                }
            } else {
                MoyasarTriggers.submitError(['An error occurred while processing your request. Please try again later.']);
            }
            // Handle error
            moyasarSTCPayClassic.toggleDisableButtons('enable');
        }).finally(() => {
            checkoutForm.removeClass('processing').unblock();
        });

        // Prevent Form Submission
        return false;

    }

    /**
     * @description Unregister Form
     */
    unregisterForm() {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        checkoutForm.off('checkout_place_order', moyasarSTCPayClassic.tokenRequest);
    }

    /**
     * @description Watch Fields
     */
    watchFields() {
        const values = {
            phone: document.getElementById('moyasar-phone-number')
        }
        // Loop through fields
        for (const field in values) {
            let selector = values[field];
            selector.addEventListener('input', () => {
                moyasarSTCPayClassic.validateField(field, selector, false);
            });
        }
    }

    /**
     * @description Toggle Phone Component - Show/Hide
     * @param event
     */
    static togglePhoneComponent(event = 'show') {
        const input = document.getElementById('moyasar-phone-number-div');
        // Toggle
        input.style.display = event === "show" ? "block" : "none"
    }

    /**
     * @description Toggle OTP Component - Show/Hide
     * @param event
     */
    static toggleOTPComponent(event = 'show') {
        const input = document.getElementById('moyasar-otp-number-div');
        // Toggle
        input.style.display = event === "show" ? "block" : "none"
    }

    /**
     * @description Toggle Disable Buttons
     * use-case: After sending the OTP, we need to disable the form submission.
     * @param event
     */
    static toggleDisableButtons(event = 'disable') {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        const sendButton = jQuery('#moyasar-stc-start-order');
        checkoutForm.find('button[type="submit"]').prop('disabled', event === 'disable');
        sendButton.prop('disabled', event === 'disable');
    }

    /**
     * @description Setup Submit Button
     */
    handleSubmitButton() {
        const button = document.getElementById('moyasar-stc-submit');
        const otpInput = document.getElementById('moyasar-otp-number');
        button.addEventListener('click', (e) => {
            e.preventDefault();
            // Redirect the user with the OTP.
            moyasarSTCPayClassic.redirectToSubmitPage(otpInput.value);
        });
    }

    /**
     * @description Redirect To The Submit Page
     */
    static redirectToSubmitPage(otp) {
        window.location.href = moyasarSTCPayClassic.redirectUrl + '&otp=' + otp;
    }

    /**
     * @description Setup Payment
     */
    setupPayment(setDetection = false) {
        this.unregisterForm();
        if (MoyasarTriggers.selectedPaymentMethod() === moyasarSTCPayClassic.id) {
            MoyasarTriggers.registerForm(moyasarSTCPayClassic.tokenRequest);
            this.watchFields();
            this.handleSubmitButton();
        }
        if (setDetection) {
            MoyasarTriggers.detectSelectedPaymentMethod(moyasarSTCPayClassic.id, (isSelected) => {
                if (isSelected) {
                    MoyasarTriggers.registerForm(moyasarSTCPayClassic.tokenRequest);
                    this.watchFields();
                    this.handleSubmitButton();
                } else {
                    MoyasarTriggers.unRegisterForm(moyasarSTCPayClassic.tokenRequest);
                }
            });
        }

        setTimeout(() => {
            this.setupPayment(true);
        }, 1500);
    }
}