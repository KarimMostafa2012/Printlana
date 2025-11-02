import React, {useEffect, useRef, useState} from 'react';
import {decodeEntities} from '@wordpress/html-entities';

const SamsungPayContent = (props) => {
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
     * @description Handle Samsung Pay Button Clicked
     */
    function handleSamsungPayButtonClicked() {
        setPaymentStatus({
            status: '',
            message: ''
        })
        const request = new MoyasarRequest(setting.baseUrl, setting.publishableKey, setting.id)

        const transactionDetail = {
            orderNumber: 'ORDER-' + new Date().getTime(),
            merchant: {
                name: setting.storeName,
                countryCode: setting.countryCode,
                url: window.location.hostname
            },
            amount: {
                option: 'FORMAT_TOTAL_PRICE_ONLY',
                currency: setting.storeCurrency,
                total: `${request.getFinalPrice(totalPrice.current)}`
            }
        };
        MoyasarSamsungHelper.SamsungPaySession.loadPaymentSheet(MoyasarSamsungHelper.samsungPaymentMethods, transactionDetail)
            .then((credentials) => {
                paymentToken = credentials['3DS']['data'];
                onSubmit();
            })
            .catch(function (err) {
                console.log(err)
                // The user canceled or something else failed
                MoyasarSamsungHelper.SamsungPaySession.notify({status: 'CANCELED', provider: 'Moyasar'});
            });
    }

    /**
     * @description Set Samsung Pay Button on component mount & remove on unmount
     */
    useEffect(() => {
        MoyasarSamsungHelper.setSamsungPayButton(handleSamsungPayButtonClicked);
        return () => {
            // Remove the Samsung Pay button
            MoyasarSamsungHelper.resetPlaceOrderButton();
        };
    }, []);

    /**
     * @description Attach the payment token (samsung pay) to the form before submitting
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        'mysr_token': paymentToken,
                        'moyasar-sp-nonce-field': moyasar_notice.value
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
            MoyasarSamsungHelper.SamsungPaySession.notify({status: 'CHARGED', provider: 'Moyasar'});
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
            MoyasarSamsungHelper.SamsungPaySession.notify({status: 'ERRED', provider: 'Moyasar'});
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
            </div>
        </div>
    );
};


export default SamsungPayContent;