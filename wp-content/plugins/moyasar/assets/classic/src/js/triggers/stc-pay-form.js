jQuery(function ($) {
    if (typeof moyasarSTCPayClassic !== "undefined") {
        moyasarSTCPayClassic.getInstance(moyasar_stc_pay.mysrSPPaymentId, moyasar_stc_pay.mysrSPPublishableKey, moyasar_stc_pay.mysrSPMoyasarBaseUrl).setupPayment();
    }
});