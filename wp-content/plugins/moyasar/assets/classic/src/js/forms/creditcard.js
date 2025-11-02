/**
 * @description Moyasar Credit Card Classic
 */
class moyasarCreditCardClassic {

    /**
     * @description Moyasar Credit Card Classic Instance
     * @type {null}
     */
    static instance = null;

    /**
     * @description Moyasar Credit Card Method ID
     * @type {null}
     */
    static id = null;

    /**
     * @description Moyasar Credit Card Publishable Key
     * @type {null}
     */
    static publishableKey = null;

    /**
     * @description Base URL
     * @type {null}
     */
    static baseUrl = null;

    /**
     * @description Card Information
     * @type {{}}
     */
    static cardInfo = {}

    /**
     * @description Popup Session
     * @type {{}}
     */
    static isModalOpened = false

    constructor(id, publishableKey, baseUrl) {
        moyasarCreditCardClassic.id = id;
        moyasarCreditCardClassic.publishableKey = publishableKey;
        moyasarCreditCardClassic.baseUrl = baseUrl;
    }

    /**
     * @description Get Instance
     * @param id
     * @param publishableKey
     * @param baseUrl
     * @returns {moyasarCreditCardClassic|*}
     */
    static getInstance(id, publishableKey, baseUrl) {
        if (moyasarCreditCardClassic.instance) {
            return moyasarCreditCardClassic.instance;
        }
        const instance = new moyasarCreditCardClassic(id, publishableKey, baseUrl);
        moyasarCreditCardClassic.instance = instance;
        return instance;
    }

    /**
     * @description Save Card information then remove them from the form to prevent sending it to the server
     */
    static removeCardInformation() {
        const checkoutForm = jQuery('form.woocommerce-checkout')
        let name = checkoutForm.find('#moyasar-card-name');
        let number = checkoutForm.find('#moyasar-card-number');
        let expiry = checkoutForm.find('#moyasar-card-expiry');
        let cvc = checkoutForm.find('#moyasar-card-cvc');
        moyasarCreditCardClassic.cardInfo = {
            name: name.val(),
            number: number.val(),
            expiry: expiry.val(),
            cvc: cvc.val()
        }
        name.val('')
        number.val('')
        expiry.val('')
        cvc.val('')
    }

    /**
     * @description Reset Card Information
     * use-case: when the user tries to submit the form and the request fails
     */
    static resetCardInformation() {
        MoyasarTriggers.registerForm(moyasarCreditCardClassic.tokenRequest);
        const checkoutForm = jQuery('form.woocommerce-checkout')
        let name = checkoutForm.find('#moyasar-card-name');
        let number = checkoutForm.find('#moyasar-card-number');
        let expiry = checkoutForm.find('#moyasar-card-expiry');
        let cvc = checkoutForm.find('#moyasar-card-cvc');
        const info = moyasarCreditCardClassic.cardInfo;
        moyasarCreditCardClassic.cardInfo = {}
        name.val(info.name)
        number.val(info.number)
        expiry.val(info.expiry)
        cvc.val(info.cvc)
        jQuery(document.body).off('checkout_error', moyasarCreditCardClassic.resetCardInformation);
    }

    /**
     * @description Save Card Success Callback
     */
    static saveCardSuccessCallback(data) {
        const checkoutForm = jQuery('form.woocommerce-checkout')
        checkoutForm.append('<input type="hidden" name="mysr_token" value="' + data.token + '"/>')
        checkoutForm.append('<input type="hidden" name="mysr_form" value="classic"/>')
        checkoutForm.off('checkout_place_order', moyasarCreditCardClassic.tokenRequest)
        // Remove Card from Form
        moyasarCreditCardClassic.removeCardInformation()
        checkoutForm.submit()
        jQuery(document.body).on('checkout_error', moyasarCreditCardClassic.resetCardInformation);
    }

    /**
     * @description Save Card Fail Callback
     */
    static saveCardFailCallback(data) {
        try {
            const fields = data.errors;
            let errorMessages = [];
            // Append Error on top of the form
            Object.keys(fields).forEach((field) => {
                let errors = fields[field]
                errors.forEach((error) => {
                    errorMessages.push(field + ': ' + error)
                });
            });

            MoyasarTriggers.submitError(errorMessages);
        } catch (e) {
            MoyasarTriggers.submitError(['An error occurred while processing your request. Please try again later.']);
        }
    }

    /**
     * @description Validate Field
     */
    static validateField(field, selector, showError = true) {
        let result = {};
        if (field === 'name') {
            result = MoyasarValidation.cardName(selector.value);
            selector.value = result.showValue;
        } else if (field === 'number') {
            result = MoyasarValidation.cardNumber(selector.value);
            selector.value = result.showValue;
        } else if (field === 'expiry') {
            result = MoyasarValidation.cardExpiry(selector.value);
            selector.value = result.showValue;
        } else if (field === 'cvc') {
            result = MoyasarValidation.cvc(selector.value);
            selector.value = result.showValue;
        } else {
            return {isValid: false};
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
        // Validate Fields
        const values = {
            name: document.getElementById('moyasar-card-name'),
            number: document.getElementById('moyasar-card-number'),
            expiry: document.getElementById('moyasar-card-expiry'),
            cvc: document.getElementById('moyasar-card-cvc')
        }
        const submitCard = {};
        // Loop through fields
        for (const field in values) {
            let selector = values[field];
            let validationResult = moyasarCreditCardClassic.validateField(field, selector);
            if (validationResult.isValid === false) {
                return false;
            }
            if (field === 'expiry') {
                submitCard['month'] = validationResult.submitValue.substring(0, 2);
                submitCard['year'] = validationResult.submitValue.substring(2);
            } else {
                submitCard[field] = validationResult.submitValue;
            }
        }

        const request = new MoyasarRequest(moyasarCreditCardClassic.baseUrl, moyasarCreditCardClassic.publishableKey, moyasarCreditCardClassic.id)
        request.initiateCreditCard(submitCard).then((res) => {
            moyasarCreditCardClassic.saveCardSuccessCallback({token: res.id})
        }).catch((error) => {
            console.log(error)
            moyasarCreditCardClassic.saveCardFailCallback(error)
        });

        // Prevent Form Submission
        return false;

    }


    /**
     * @description Unregister Form
     */
    unregisterForm() {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        checkoutForm.off('checkout_place_order', moyasarCreditCardClassic.tokenRequest);
    }

    /**
     * @description Watch Fields
     */
    watchFields() {
        const values = {
            name: document.getElementById('moyasar-card-name'),
            number: document.getElementById('moyasar-card-number'),
            expiry: document.getElementById('moyasar-card-expiry'),
            cvc: document.getElementById('moyasar-card-cvc')
        }
        // Loop through fields
        for (const field in values) {
            let selector = values[field];
            selector.addEventListener('input', () => {
                moyasarCreditCardClassic.validateField(field, selector, false);
            });
        }
    }

    /**
     * @description Start 3D Secure
     */
    static start3DSecure(data, metadata) {
        if (moyasarCreditCardClassic.isModalOpened){
            return true;
        }

        if (!metadata.transactionUrl){
            window.location = metadata['redirect2'];
            return true
        }

        moyasarCreditCardClassic.isModalOpened = true;
        const iframe = jQuery('#mysr-3d-secure-iframe');
        iframe.attr('src', metadata.transactionUrl);
        moyasarCreditCardClassic.popupManager().then(() => {
            if (-1 === metadata.redirect.indexOf('https://') || -1 === metadata.redirect.indexOf('http://')) {
                window.location = metadata['redirect2'];
            } else {
                window.location = decodeURI(metadata['redirect2']);
            }
        }).finally(() => {
            moyasarCreditCardClassic.isModalOpened = false;

        });
        // Click outside the modal, dimis and redirect
        jQuery(document.body).on('click', function (e) {
            if (e.target.id === 'mysr-modal') {
                jQuery('#mysr-modal').css('display', 'none');
                window.location = metadata['redirect2'];
            }
        });

        return true;
    }

    /**
     * @description Popup Manager
     * @returns {Promise<unknown>}
     */
    static popupManager() {
        return new Promise(async (resolve, reject) => {
            const checkoutForm = jQuery('form.woocommerce-checkout');
            // stop Loading
            checkoutForm.removeClass('processing').unblock();
            await moyasarCreditCardClassic.showModal();
            resolve(true);
        });
    }

    /**
     * @description Show Modal
     * @returns {Promise<unknown>}
     */
    static showModal() {
        return new Promise((resolve, reject) => {
            const modal = document.getElementById('mysr-modal');
            const iframe = document.getElementById('mysr-3d-secure-iframe');
            modal.style.display = "block";
            const interval = setInterval(() => {
                try {
                    let location = iframe.contentWindow?.location;
                    let href = location?.href;
                    let isSameHost = location?.host === window.location.host;
                    if (href && (href.includes('mysr_checkout') || isSameHost) ) {
                        clearInterval(interval);
                        modal.style.display = "none";
                        resolve(true);
                    }
                } catch (e) {
                }
                //     TODO: Timeout
            }, 50)
        });
    }

    /**
     * @description Setup Payment
     */
    setupPayment(setDetection = false) {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        this.unregisterForm();
        if (MoyasarTriggers.selectedPaymentMethod() === moyasarCreditCardClassic.id) {
            MoyasarTriggers.registerForm(moyasarCreditCardClassic.tokenRequest);
            checkoutForm.on('checkout_place_order_success', moyasarCreditCardClassic.start3DSecure);
            this.watchFields();
            MoyasarTriggers.setMoyasarSubmitButton();
        }
        if (setDetection) {
            MoyasarTriggers.detectSelectedPaymentMethod(moyasarCreditCardClassic.id, (isSelected) => {
                if (isSelected) {
                    MoyasarTriggers.registerForm(moyasarCreditCardClassic.tokenRequest);
                    checkoutForm.on('checkout_place_order_success', moyasarCreditCardClassic.start3DSecure);
                    this.watchFields();
                    MoyasarTriggers.setMoyasarSubmitButton();
                } else {
                    MoyasarTriggers.unRegisterForm(moyasarCreditCardClassic.tokenRequest);
                    checkoutForm.off('checkout_place_order_success', moyasarCreditCardClassic.start3DSecure);
                    MoyasarTriggers.resetPlaceOrderButton();
                }

                jQuery(document.body).on('updated_checkout', () => {
                    if (MoyasarTriggers.selectedPaymentMethod() === moyasarCreditCardClassic.id) {
                        MoyasarTriggers.resetPlaceOrderButton();
                        MoyasarTriggers.setMoyasarSubmitButton();
                    }

                });
            });

        }


        setTimeout(() => {
            this.setupPayment(true);
        }, 1500);
    }
}