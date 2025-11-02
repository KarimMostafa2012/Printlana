jQuery(function ($) {
    if (typeof moyasarApplePayClassic !== "undefined") {
        moyasarApplePayClassic.getInstance(moyasar_apple_pay.mysrAPPaymentId,
            moyasar_apple_pay.mysrAPPublishableKey,
            moyasar_apple_pay.mysrAPMoyasarBaseUrl,
            moyasar_apple_pay.mysrAPStoreCountry,
            moyasar_apple_pay.mysrAPStoreCurrency,
            moyasar_apple_pay.mysrAPSupportedNetworks,
            moyasar_apple_pay.mysrAPSupportedCountries,
            moyasar_apple_pay.mysrAPMerchantName,
            moyasar_apple_pay.mysrAPTotal
        ).setupPayment();
    }
});
