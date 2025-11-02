jQuery(function ($) {
    console.log("[Moyasar] Begin [Credit Card] Payment");
    if (typeof moyasarCreditCardClassic !== "undefined") {
        console.log("[Moyasar] Class [Credit Card] Exists");
        moyasarCreditCardClassic.getInstance(moyasar_credit_card.mysrCCPaymentId, moyasar_credit_card.mysrCCPublishableKey, moyasar_credit_card.mysrCCMoyasarBaseUrl).setupPayment();
    } else {
        console.log("[Moyasar] Class [Credit Card] Not Exists");
    }
});