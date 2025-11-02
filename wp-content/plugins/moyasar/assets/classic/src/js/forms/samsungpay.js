/**
 * @description Moyasar Samsung Pay Classic
 */
class moyasarSamsungPayClassic {

    /**
     * @description Moyasar Samsung Pay Classic Instance
     * @type {null}
     */
    static instance = null;

    /**
     * @description Moyasar Samsung Pay Method ID
     * @type {null}
     */
    static id = null;

    /**
     * @description Moyasar Samsung Pay Publishable Key
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
     * @description Samsung Pay Supported Networks
     */
    static supportedNetworks;

    /**
     * @description Samsung Pay Supported Countries
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

    /**
     * @description Service ID
     * @type {null}
     */
    static serviceId;


    /**
     * @param id
     * @param publishableKey
     * @param baseUrl
     */
    constructor(id, publishableKey, baseUrl) {
        moyasarSamsungPayClassic.id = id;
        moyasarSamsungPayClassic.publishableKey = publishableKey;
        moyasarSamsungPayClassic.baseUrl = baseUrl;
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
     * @param serviceId
     * @returns {moyasarSamsungPayClassic|*}
     */
    static getInstance(id, publishableKey, baseUrl, storeCountry, storeCurrency, supportedNetworks, supportedCountries, merchantName, total, serviceId) {
        moyasarSamsungPayClassic.storeCountry = storeCountry;
        moyasarSamsungPayClassic.storeCurrency = storeCurrency;
        moyasarSamsungPayClassic.supportedNetworks = supportedNetworks;
        moyasarSamsungPayClassic.supportedCountries = supportedCountries;
        moyasarSamsungPayClassic.merchantName = merchantName;
        moyasarSamsungPayClassic.total = total;
        moyasarSamsungPayClassic.serviceId = serviceId;

        if (moyasarSamsungPayClassic.instance) {
            return moyasarSamsungPayClassic.instance;
        }
        const instance = new moyasarSamsungPayClassic(id, publishableKey, baseUrl);
        moyasarSamsungPayClassic.instance = instance;
        return instance;
    }

    /**
     * @description Setup Samsung Pay Button
     */
    static showSamsungPayButton() {
        if (MoyasarSamsungHelper.isSamsungPayButtonVisible()) {
            return;
        }


        MoyasarSamsungHelper.setSamsungPayButton(async (e) => {
            const transactionDetail = {
                orderNumber: 'ORDER-' + new Date().getTime(),
                merchant: {
                    name: moyasarSamsungPayClassic.merchantName,
                    countryCode: moyasarSamsungPayClassic.storeCountry,
                    url: window.location.hostname
                },
                amount: {
                    option: 'FORMAT_TOTAL_PRICE_ONLY',
                    currency: moyasarSamsungPayClassic.storeCurrency,
                    total: moyasarSamsungPayClassic.total
                }
            };
            MoyasarSamsungHelper.SamsungPaySession.loadPaymentSheet(MoyasarSamsungHelper.samsungPaymentMethods, transactionDetail)
                .then((credentials) => {
                    const checkoutForm = jQuery('form.woocommerce-checkout')
                    let token = `${credentials['3DS']['data']}`;
                    checkoutForm.append("<input type='hidden' name='mysr_token' value='" + token + "' />")
                    checkoutForm.submit();

                })
                .catch(function (err) {
                    console.log(err)
                    // The user canceled or something else failed
                    MoyasarSamsungHelper.SamsungPaySession.notify({status: 'CANCELED', provider: 'Moyasar'});
                });

        });
    }

    /**
     * @description Handle Checkout Success (Samsung Pay Popup)
     */
    static samsungPayHandleCheckoutSuccess(data) {
        // Check if samsung pay session is available
        try {
            MoyasarSamsungHelper.SamsungPaySession.notify({status: 'CHARGED', provider: 'Moyasar'});
        } catch (e) {

        }
        // Handle Redirect
        return true;
    }

    /**
     * @description Handle Checkout Error (Samsung Pay Popup)
     */
    static samsungPayHandleCheckoutError(data) {
        // Check if samsung pay session is available
        try {
            MoyasarSamsungHelper.SamsungPaySession.notify({status: 'ERRED', provider: 'Moyasar'});
        } catch (e) {

        }
        return true;
    }

    /**
     * @description Setup Payment
     */
    async setupPayment(setDetection = false) {
        const checkoutForm = jQuery('form.woocommerce-checkout');
        const order = new MoyasarOrder();
        let isSamsungPaySelected = MoyasarTriggers.selectedPaymentMethod() === moyasarSamsungPayClassic.id;
        let isSamsungPaySupported = await MoyasarSamsungHelper.initializeSamsungPay(moyasarSamsungPayClassic.serviceId, moyasarSamsungPayClassic.supportedNetworks);

        if (setDetection && isSamsungPaySupported === false) {
            if (isSamsungPaySelected) {
                // Select Another Payment Method
                document.querySelector(`input[id^="payment_method"]:not([value="${moyasarSamsungPayClassic.id}"])`).click();
            }
            // Remove Samsung Pay Method
            document.querySelector(`li[class*="payment_method_${moyasarSamsungPayClassic.id}"]`).remove();
        }

        if (isSamsungPaySelected) {
            moyasarSamsungPayClassic.showSamsungPayButton();
            checkoutForm.on('checkout_place_order_success', moyasarSamsungPayClassic.samsungPayHandleCheckoutSuccess);
            jQuery(document.body).on('checkout_error', moyasarSamsungPayClassic.samsungPayHandleCheckoutError);
        }

        if (setDetection) {

            MoyasarTriggers.detectSelectedPaymentMethod(moyasarSamsungPayClassic.id, (isSelected) => {
                if (isSelected) {
                    moyasarSamsungPayClassic.showSamsungPayButton();
                    checkoutForm.on('checkout_place_order_success', moyasarSamsungPayClassic.samsungPayHandleCheckoutSuccess);
                    jQuery(document.body).on('checkout_error', moyasarSamsungPayClassic.samsungPayHandleCheckoutError);
                } else {
                    MoyasarSamsungHelper.resetPlaceOrderButton();
                    jQuery(document.body).off('checkout_error', moyasarSamsungPayClassic.samsungPayHandleCheckoutError);
                    checkoutForm.off('checkout_place_order_success', moyasarSamsungPayClassic.samsungPayHandleCheckoutSuccess);
                }
            });

            jQuery(document.body).on('updated_checkout', () => {
                order.getOrderDetails().then((orderDetails) => {
                    moyasarSamsungPayClassic.total = `${orderDetails['order']['total_row']}`;
                })
            });
        }

        setTimeout(() => {
            this.setupPayment(true);
        }, 1500);
    }
}