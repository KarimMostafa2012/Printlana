import React, {useEffect, useRef, useState} from 'react';
import {decodeEntities} from '@wordpress/html-entities';

const ApplePayContent = (props) => {
    /**
     * Props
     */
    const {
        eventRegistration,
        emitResponse,
        setting,
        onSubmit,
        billing
    } = props;

    let data = decodeEntities(setting.description || '');

    /**
     * @description Define the events
     */
    const {onPaymentSetup, onCheckoutSuccess, onCheckoutFail} = eventRegistration;

    /**
     * @description To save the payment token
     * @type {null}
     */
    let paymentToken = null;

    /**
     * @description Total Checkout Amount
     * @type {React.MutableRefObject<number>}
     */
    const totalPrice = useRef(0);

    /**
     * @description Payment Status
     */
    const [paymentStatus, setPaymentStatus] = useState({
        status: '',
        message: ''
    });

    /**
     * @description Detect the total price change
     */
    useEffect(() => {
        totalPrice.current = billing.cartTotal.value;
    }, [billing.cartTotal.value]);

    /**
     * @description Handle Apple Pay Button Clicked
     */
    function handleApplePayButtonClicked() {
        setPaymentStatus({
            status: '',
            message: ''
        })

        const request = new MoyasarRequest(setting.baseUrl, setting.publishableKey, setting.id)
        const currency = billing.currency.code;

        if (!ApplePaySession) {
            return;
        }
        MoyasarAppleHelper.startSession(setting.countryCode, currency, setting.supportedNetworks, setting.supportedCountries, setting.storeName, `${request.getFinalPrice(totalPrice.current)}`);


        MoyasarAppleHelper.appleSession.oncancel = event => {
            MoyasarAppleHelper.appleSession.abort();
        }
        MoyasarAppleHelper.appleSession.abort = event => {
        }

        // Validating Merchant
        MoyasarAppleHelper.appleSession.onvalidatemerchant = async event => {
            const body = {
                "validation_url": event.validationURL,
                "display_name": setting.storeName,
                "domain_name": window.location.hostname,
                "publishable_api_key": setting.publishableKey,
            }
            request.initiateApplePay(body).then(merchantSession => {
                MoyasarAppleHelper.appleSession.completeMerchantValidation(merchantSession);

            }).catch(error => {
                MoyasarAppleHelper.appleSession.completeMerchantValidation({});
                MoyasarAppleHelper.appleSession.abort();
                setPaymentStatus({
                    status: 'failed',
                    message: error?.message || 'An error occurred'
                })
            })

        }

        MoyasarAppleHelper.appleSession.onpaymentauthorized = event => {
            paymentToken = event.payment.token;
            onSubmit();
        };

        MoyasarAppleHelper.appleSession.begin();
    }

    /**
     * @description Set Apple Pay Button on component mount & remove on unmount
     */
    useEffect(() => {
        MoyasarAppleHelper.setApplePayButton(handleApplePayButtonClicked);
        return () => {
            // Remove the Apple Pay button
            MoyasarAppleHelper.resetPlaceOrderButton();
        };
    }, []);

    /**
     * @description Attach the payment token (apple pay) to the form before submitting
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        'mysr_token': JSON.stringify(paymentToken),
                        'moyasar-ap-nonce-field': moyasar_notice.value
                    },
                },
            };
        });
        // Unsubscribes when this component is unmounted.
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
    ]);

    /**
     * @description Checkout Success
     */
    useEffect(() => {
        const unsubscribe = onCheckoutSuccess(async (data) => {
            const response = data.processingResponse.paymentDetails;
            MoyasarAppleHelper.appleSession.completePayment({
                status: MoyasarAppleHelper.appleSession.STATUS_SUCCESS
            });
            return {
                type: emitResponse.responseTypes.SUCCESS,
                message: '',
            }
        });
        return () => {
            unsubscribe();
        };
    }, [onCheckoutSuccess, emitResponse.responseTypes.ERROR]);

    /**
     * @description Checkout Fail
     */
    useEffect(() => {
        const unsubscribe = onCheckoutFail(async (data) => {
            const response = data.processingResponse.paymentDetails;
            MoyasarAppleHelper.appleSession.completeMerchantValidation({});
            MoyasarAppleHelper.appleSession.abort();
            return {
                type: emitResponse.responseTypes.ERROR,
                message: '',
            }
        });
        return () => {
            unsubscribe();
        };
    }, [onCheckoutFail, emitResponse.responseTypes.ERROR]);

    return (
        <div className="">
            <div className="">
                {paymentStatus.message && (
                    <div
                        className={`${paymentStatus.status === 'success' ? 'woocommerce-message' : 'woocommerce-error'}`}>
                        {paymentStatus.message}
                    </div>
                )}
                {data}

                {data}
            </div>
        </div>
    );
};


export default ApplePayContent;