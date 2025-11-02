jQuery(function ($) {
    if (typeof moyasarSamsungPayClassic !== "undefined") {
        moyasarSamsungPayClassic.getInstance(moyasar_samsung_pay.mysrSPPaymentId,
            moyasar_samsung_pay.mysrSPPublishableKey,
            moyasar_samsung_pay.mysrSPMoyasarBaseUrl,
            moyasar_samsung_pay.mysrSPStoreCountry,
            moyasar_samsung_pay.mysrSPStoreCurrency,
            moyasar_samsung_pay.mysrSPSupportedNetworks,
            moyasar_samsung_pay.mysrSPSupportedCountries,
            moyasar_samsung_pay.mysrSPMerchantName,
            moyasar_samsung_pay.mysrSPTotal,
            moyasar_samsung_pay.mysrSPServiceId
        ).setupPayment();
    }
});
