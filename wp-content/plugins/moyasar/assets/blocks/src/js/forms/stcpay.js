import React, {useState, useEffect, useRef} from 'react';
import {decodeEntities} from '@wordpress/html-entities';
const __mysr_sp = wp.i18n.__

const STCPayContent = (props) => {
    /**
     * Props
     */
    const {setting, onSubmit, eventRegistration, emitResponse, billing} = props;

    /**
     * @description Define the events
     */
    const {onPaymentSetup, onCheckoutSuccess, onCheckoutFail} = eventRegistration;

    /**
     * @description Payment Status
     */
    const [paymentStatus, setPaymentStatus] = useState({
        status: '',
        message: ''
    });

    /**
     * @description Payment Step
     */
    const [paymentStep, setPaymentStep] = useState('PHONE');

    /**
     * @description Payment Validation URL (Redirect with OTP)
     */
    const [paymentValidationUrl, setPaymentValidationUrl] = useState('');


    let data = decodeEntities(setting.description || '');
    const [formData, setFormData] = useState({
        phone: '',
        otp: ''
    });

    const form = useRef(formData);
    useEffect(() => {
        form.current = formData;
    }, [formData]);


    const [errors, setErrors] = useState({phone: '', otp: ''});

    /**
     * @description Detect the total price change, reset step to PHONE
     */
    useEffect((data) => {
        setPaymentStep('PHONE');
        setPaymentStatus({
            status: '',
            message: ''
        });
        // Reset payment cycle

    }, [ billing.cartTotal.value ]);


    /**
     * @description Disable Place Order Button on Mount
     */
    useEffect(() => {
        let placeOrderButton = null
        // On document load
        window.onload = () => {
             // Disable Place Order Button
            placeOrderButton = document.getElementsByClassName('wc-block-components-checkout-place-order-button')[0];
            placeOrderButton.disabled = true;
        };



        return () => {
            // Reset Place Order Button
            if (placeOrderButton){
                placeOrderButton.disabled = false;
            }
        };
    }, []);

    /**
     * @description attach phone before submitting the form
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const payload = form.current;
            let validationResult = validateField('phone', payload.phone);
            if (validationResult.isValid === false) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: validationResult.message,
                };
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        'mysr_token': validationResult.submitValue,
                        'moyasar-stc-nonce-field': moyasar_notice.value
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
     * @description Checkout Success, Switch to OTP Step
     */
    useEffect( () => {
        const unsubscribe = onCheckoutSuccess(async (data) => {
            const response = data.processingResponse.paymentDetails;
            setPaymentStatus({
                status: 'success',
                message: __mysr_sp('OTP Sent Successfully. Please enter the OTP code you received.', 'moyasar')
            });
            setPaymentStep('OTP');
            setPaymentValidationUrl(response.redirect);
            await new Promise(resolve => setTimeout(resolve, 100000));

            if (paymentStep === 'PHONE') {
                return;
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'Timeout',
            }
        });
        return () => {
            unsubscribe();
        };
    }, [onCheckoutSuccess, emitResponse.responseTypes.SUCCESS]);


    /**
     * @description Checkout Fail
     */
    useEffect(() => {
        const unsubscribe = onCheckoutFail(async (data) => {
            const response = data.processingResponse.paymentDetails;
            setPaymentStatus({
                status: 'failed',
                message: response.message
            })
            return {
                type: emitResponse.responseTypes.ERROR,
                message: response.message,
            }
        });
        return () => {
            unsubscribe();
        };
    }, [onCheckoutFail, emitResponse.responseTypes.ERROR]);


    /**
     * @description Handle OTP Submission
     * @param e
     */
    const handleSubmit = (e) => {
        e.preventDefault();
        onSubmit();
    };

    /**
     * @description Redirect to Validation URL with OTP
     * @param e
     */
    const handleVerify = (e) => {
        e.preventDefault();
        // Redirect to Validation URL
        window.location.href = paymentValidationUrl + '&otp=' + formData.otp;
    };

    /**
     * @description Validate Field
     */
    const validateField = (field, value, showError = true) => {
        let result = {};
        if (field === 'phone') {
            result = MoyasarValidation.phoneNumber(value);
        } else if (field === 'otp') {
            result = MoyasarValidation.otp(value);
        } else {
            return {isValid: false, showValue: ''};
        }


        if (result.isValid === false && showError) {
            setErrors({...errors, field: result.message});
        } else {
            setErrors({});
        }

        return result;
    }

    const handleChange = (e) => {
        let {name, value} = e.target;

        let result = validateField(name, value, false);
        value = result.showValue;

        setFormData({...formData, [name]: value});
    };

    return (
        <div >
            <div>
                {paymentStatus.message && (
                    <div className={`${paymentStatus.status === 'success' ? 'woocommerce-message' : 'woocommerce-error'}`}>
                        {paymentStatus.message}
                    </div>
                )}
                {data}

                <p>{ __mysr_sp('Please enter your mobile number and press \'Send OTP\'. You will receive an SMS code required to complete the payment process.', 'moyasar') }</p>
                {paymentStep === 'PHONE' && (<div
                    className="wc-block-components-text-input is-active">
                    <input type="text" id="phone-number" aria-label="Phone number"
                           required
                           aria-invalid="false"
                           title=""
                           class="moyasar-number"
                           placeholder={'05x xxx xxxx'}
                           name="phone"
                           value={formData.phone}
                           onChange={handleChange}
                    />
                    <label htmlFor="card-name">{__mysr_sp("Phone Number", 'moyasar')}</label>
                </div>)}

                {paymentStep === 'OTP' && (<div
                    className="wc-block-components-text-input is-active">
                    <input type="text" id="otp-number" aria-label="OTP number"
                           required
                           aria-invalid="false"
                           title=""
                           class="moyasar-number"
                           placeholder={'xxx'}
                           name="otp"
                           value={formData.otp}
                           onChange={handleChange}
                    />
                    <label htmlFor="card-name">{__mysr_sp("OTP", 'moyasar')}</label>
                </div>)}


                {/*    Submit Button */}
                {paymentStep === 'PHONE' && (<button className="moyasar-stc-pay-button"
                                                     onClick={handleSubmit}
                                                     disabled={('phone' in errors)}
                >
                    <span>{__mysr_sp("Send OTP", 'moyasar')}</span>
                </button>)}

                {paymentStep === 'OTP' && (<button className="moyasar-stc-pay-button"
                                                   onClick={handleVerify}
                                                   disabled={('otp' in errors)}
                >
                    <span>{__mysr_sp("Submit", 'moyasar')}</span>
                </button>)}
            </div>
        </div>
    );
};


export default STCPayContent;