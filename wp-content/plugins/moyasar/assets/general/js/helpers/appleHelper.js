/**
 * @description Moyasar Apple Pay Helper
 */
class MoyasarAppleHelper {

    /**
     * @description Save the Apple Pay Session Globally
     */
    static appleSession = null;

    /**
     * @description Apple Pay Button Style
     * @type {{}}
     */
    static buttonStyle = {};

    /**
     * @description Start Apple Pay Session
     * @param countryCode
     * @param currencyCode
     * @param supportedNetworks
     * @param label
     * @param total
     */
    static startSession(countryCode, currencyCode, supportedNetworks, supportedCountries, label, total) {
        const applePayPaymentRequest = {
            countryCode: countryCode,
            currencyCode: currencyCode,
            supportedNetworks: supportedNetworks,
            supportedCountries: supportedCountries,
            merchantCapabilities: ['supports3DS', 'supportsDebit', 'supportsCredit'],
            total: {
                label: label,
                amount: `${total}`
            },
        }
        MoyasarAppleHelper.appleSession = new window.ApplePaySession(3, applePayPaymentRequest);
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

        MoyasarAppleHelper.buttonStyle = {
            height: '50px',
            width: '100%',
        }
        return baseButton;
    }

    /**
     * @description Reset the Place Order Button
     */
    static resetPlaceOrderButton() {
        const placeOrderButton = MoyasarAppleHelper.getPlaceOrderButton();
        placeOrderButton.style.display = 'block';
        const applePayButton = document.getElementById('moyasar-apple-pay-button');
        if (applePayButton) {
            applePayButton.remove();
        }
    }

    /**
     * @description Set Apple Pay Button, hide place order button and set the callback
     * @param callback
     */
    static setApplePayButton(callback) {
        const placeOrderButton = MoyasarAppleHelper.getPlaceOrderButton();
        if (!placeOrderButton){
            setTimeout(() => MoyasarAppleHelper.setApplePayButton(callback), 300);
            return;
        }


        const applePayButton = document.createElement('apple-pay-button');
        applePayButton.id = 'moyasar-apple-pay-button';
        applePayButton.className = 'primary';
        applePayButton.buttonstyle = 'black';
        applePayButton.type = 'buy';
        applePayButton.onclick = callback;

        // Hide the Place Order Button
        placeOrderButton.style.display = 'none';
        applePayButton.style = `
            --apple-pay-button-height: ${MoyasarAppleHelper.buttonStyle.height};
            --apple-pay-button-border-radius: 3px;
            --apple-pay-button-padding: 0px 0px;
            --apple-pay-button-box-sizing: border-box;
            --apple-pay-button-width: ${MoyasarAppleHelper.buttonStyle.width};
            display: block;
        `
        placeOrderButton.after(applePayButton);
    }

    /**
     * @description Check if Apple Pay Button is Visible
     * @returns {boolean}
     */
    static isApplePayButtonVisible() {
        const applePayButton = document.getElementById('moyasar-apple-pay-button');
        return applePayButton && applePayButton.style.display === 'block';
    }

}