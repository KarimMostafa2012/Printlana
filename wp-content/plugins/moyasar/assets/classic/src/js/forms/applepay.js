/**
 * @description Moyasar Apple Pay Classic
 */
class moyasarApplePayClassic {

    /**
     * @description Moyasar Apple Pay Classic Instance
     * @type {null}
     */
    static instance = null;

    /**
     * @description Moyasar Apple Pay Method ID
     * @type {null}
     */
    static id = null;

    /**
     * @description Moyasar Apple Pay Publishable Key
     * @type {null}
     */
    static publishableKey = null;

    /**
     * @description Base URL
     * @type {null}
     */
    static baseUrl = null;

    /**
     * @description Store Country
     */
    static storeCountry;

    /**
     * @description Store Currency
     */
    static storeCurrency;

    /**
     * @description Apple Pay Supported Networks
     */
    static supportedNetworks;

    /**
     * @description Apple Pay Supported Countries
     */
    static supportedCountries;

    /**
     * @description Merchant Name
     */
    static merchantName;

    /**
     * @description Total Checkout Amount
     */
    static total;

    constructor(id, publishableKey, baseUrl) {
        moyasarApplePayClassic.id = id;
        moyasarApplePayClassic.publishableKey = publishableKey;
        moyasarApplePayClassic.baseUrl = baseUrl;
    }

    /**
     * @description Get Instance
     * @param id
     * @param publishableKey
     * @param baseUrl
     * @param storeCountry
     * @param storeCurrency
     * @param supportedNetworks
     * @param supportedCountries
     * @param merchantName
     * @param total
     * @returns {moyasarApplePayClassic|*}
     */
    static getInstance(id, publishableKey, baseUrl, storeCountry, storeCurrency, supportedNetworks, supportedCountries, merchantName, total) {
        moyasarApplePayClassic.storeCountry = storeCountry;
        moyasarApplePayClassic.storeCurrency = storeCurrency;
        moyasarApplePayClassic.supportedNetworks = supportedNetworks;
        moyasarApplePayClassic.supportedCountries = supportedCountries;
        moyasarApplePayClassic.merchantName = merchantName;
        moyasarApplePayClassic.total = total;

        if (moyasarApplePayClassic.instance) {
            return moyasarApplePayClassic.instance;
        }
        const instance = new moyasarApplePayClassic(id, publishableKey, baseUrl);
        moyasarApplePayClassic.instance = instance;
        return instance;
    }

    /**
     * @description Setup Apple Pay Button
     */
    static showApplePayButton() {
        if (MoyasarAppleHelper.isApplePayButtonVisible()) {
            return;
        }

        const request = new MoyasarRequest(moyasarApplePayClassic.baseUrl, moyasarApplePayClassic.publishableKey, moyasarApplePayClassic.id)

        MoyasarAppleHelper.setApplePayButton(async () => {
            MoyasarAppleHelper.startSession(moyasarApplePayClassic.storeCountry, moyasarApplePayClassic.storeCurrency, moyasarApplePayClassic.supportedNetworks, moyasarApplePayClassic.supportedCountries, moyasarApplePayClassic.merchantName, moyasarApplePayClassic.total);

            MoyasarAppleHelper.appleSession.oncancel = event => {
                MoyasarAppleHelper.appleSession.abort();
            }
            MoyasarAppleHelper.appleSession.abort = event => {
            }

            MoyasarAppleHelper.appleSession.onvalidatemerchant = async event => {
                const body = {
                    "validation_url": event.validationURL,
                    "display_name": moyasarApplePayClassic.merchantName,
                    "domain_name": window.location.hostname,
                    "publishable_api_key": moyasarApplePayClassic.publishableKey,
                }
                request.initiateApplePay(body).then(merchantSession => {
                    MoyasarAppleHelper.appleSession.completeMerchantValidation(merchantSession);
                }).catch(error => {
                    MoyasarTriggers.submitError([error?.message || 'An error occurred']);
                    MoyasarAppleHelper.appleSession.completeMerchantValidation({});
                    MoyasarAppleHelper.appleSession.abort();
                })
            }

            MoyasarAppleHelper.appleSession.onpaymentauthorized = event => {

                const checkoutForm = jQuery('form.woocommerce-checkout')

                let token = `${JSON.stringify(event.payment.token)}`;
                checkoutForm.append("<input type='hidden' name='mysr_token' value='" + token + "' />")
                checkoutForm.submit();
            };

            MoyasarAppleHelper.appleSession.begin();
        });
    }

    /**
     * @description Handle Checkout Success (Apple Pay Popup)
     */
    static applePayHandleCheckoutSuccess(data) {
        // Check if apple pay session is available
        try {
            MoyasarAppleHelper.appleSession.completePayment({
                status: ApplePaySession.STATUS_SUCCESS
            });
        } catch (e) {

        }
        // Handle Redirect
        return true;
    }

    /**
     * @description Handle Checkout Error (Apple Pay Popup)
     */
    static applePayHandleCheckoutError(data) {
        // Check if apple pay session is available
        try {
            MoyasarAppleHelper.appleSession.completePayment({
                status: ApplePaySession.STATUS_FAILURE
            });
        } catch (e) {

        }
        return true;
    }

    /**
     * @description Setup Payment
     */
    setupPayment(setDetection = false) {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        const order = new MoyasarOrder();
        let isApplePaySelected = MoyasarTriggers.selectedPaymentMethod() === moyasarApplePayClassic.id;
        let isApplePaySupported = (window.ApplePaySession && ApplePaySession.canMakePayments()) || false;

        if (setDetection && isApplePaySupported === false && document.querySelector(`li[class*="payment_method_${moyasarApplePayClassic.id}"]`)) {
            if (isApplePaySelected) {
                // Select Another Payment Method
                document.querySelector(`input[id^="payment_method"]:not([value="${moyasarApplePayClassic.id}"])`).click();
            }
            // Remove Apple Pay Method
            document.querySelector(`li[class*="payment_method_${moyasarApplePayClassic.id}"]`).remove();
        }

        if (isApplePaySelected && isApplePaySupported === true) {
            moyasarApplePayClassic.showApplePayButton();
            checkoutForm.on('checkout_place_order_success', moyasarApplePayClassic.applePayHandleCheckoutSuccess);
            jQuery(document.body).on('checkout_error', moyasarApplePayClassic.applePayHandleCheckoutError);
        }

        if (setDetection && isApplePaySupported === true) {

            MoyasarTriggers.detectSelectedPaymentMethod(moyasarApplePayClassic.id, (isSelected) => {
                if (isSelected) {
                    moyasarApplePayClassic.showApplePayButton();
                    checkoutForm.on('checkout_place_order_success', moyasarApplePayClassic.applePayHandleCheckoutSuccess);
                    jQuery(document.body).on('checkout_error', moyasarApplePayClassic.applePayHandleCheckoutError);
                } else {
                    MoyasarAppleHelper.resetPlaceOrderButton();
                    jQuery(document.body).off('checkout_error', moyasarApplePayClassic.applePayHandleCheckoutError);
                    checkoutForm.off('checkout_place_order_success', moyasarApplePayClassic.applePayHandleCheckoutSuccess);
                }
            });

            jQuery(document.body).on('updated_checkout',  () => {
                order.getOrderDetails().then((orderDetails) => {
                    moyasarApplePayClassic.total = `${orderDetails['order']['total_row']}`;
                })
            });
        }

        setTimeout(() => {
            this.setupPayment(true);
        }, 1500);
    }
}