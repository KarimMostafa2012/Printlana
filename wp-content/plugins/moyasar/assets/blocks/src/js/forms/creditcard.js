import React, {useState, useEffect, useRef} from "react";
import {decodeEntities} from '@wordpress/html-entities';
const __mysr_cc = wp.i18n.__
const CreditCardContent = (props) => {
    /**
     * Props
     */
    const {setting, eventRegistration, emitResponse, billing} = props;

    /**
     * @description Define the events
     */
    const {onPaymentSetup, onCheckoutSuccess, onCheckoutFail} = eventRegistration;

    let data = decodeEntities(setting.description || '');

    /**
     * @description Form Ref
     */
    const [errors, setErrors] = useState({});
    const [formData, setFormData] = useState({
        name: '',
        number: '',
        expiry: '',
        cvc: '',
    });

    const form = useRef(formData);

    /**
     * @description Detect the form data change
     */
    useEffect(() => {
        form.current = formData;
    }, [formData]); // Update the ref every time myState changes

    /**
     * @description Setup Payment and get card token before submitting the form
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const payload = form.current;

            const submitCard = {};
            // Loop through fields
            for (const field in payload) {
                let validationResult = validateField(field, payload[field]);
                if (validationResult.isValid === false) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: validationResult.message
                    };
                }
                if (field === 'expiry') {
                    submitCard['month'] = validationResult.submitValue.substring(0, 2);
                    submitCard['year'] = validationResult.submitValue.substring(2);
                } else {
                    submitCard[field] = validationResult.submitValue;
                }
            }


            const request = new MoyasarRequest(setting.baseUrl, setting.publishableKey, setting.id)
            try {
                let init = await request.initiateCreditCard(submitCard);
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            'mysr_token': init.id,
                            'mysr_form': 'blocks',
                            'moyasar-cc-nonce-field': moyasar_notice.value
                        },
                    },
                };
            } catch (error) {
                let message = '';
                if (error.type === 'authentication_error' || error.type === 'account_inactive_error'){
                    message = error.message
                }else{
                    const fields = error.errors;
                    // Get First field
                    message = fields[Object.keys(fields)[0]][0];
                }
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: message
                };
            }
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
     * @description Checkout Success, start the 3D Secure process (Modal)
     */
    useEffect(() => {
        const unsubscribe = onCheckoutSuccess(async (data) => {
            const response = data.processingResponse.paymentDetails;

            const iframe = jQuery('#mysr-3d-secure-iframe');

            if (!response.transactionUrl){
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    message: '',
                }
            }

            iframe.attr('src', response.transactionUrl);
            await popupManager();
            return {
                type: emitResponse.responseTypes.SUCCESS,
                message: '',
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
            const errorMsg = data?.processingResponse?.paymentDetails?.message || 'Something went wrong';
            console.error('[Moyasar error]', errorMsg);
            return {
                type: emitResponse.responseTypes.ERROR,
                message: data.message,
            }
        });
        return () => {
            unsubscribe();
        };
    }, [onCheckoutFail, emitResponse.responseTypes.ERROR]);

    /**
     * @description Popup Manager
     * @returns {Promise<unknown>}
     */
    const popupManager = () => {
        return new Promise(async (resolve, reject) => {
            await showModal();
            resolve(true);
        });
    }

    /**
     * @description Show Modal
     * @returns {Promise<unknown>}
     */
    const showModal = () => {
        return new Promise((resolve, reject) => {
            const modal = document.getElementById('mysr-modal');
            const iframe = document.getElementById('mysr-3d-secure-iframe');
            modal.style.display = "block";
            const interval = setInterval(() => {
                try {
                    let location = iframe.contentWindow?.location;
                    let href = location?.href;
                    let isSameHost = location?.host === window.location.host;
                    if (href && (href.includes('mysr_checkout') && isSameHost )) {
                        clearInterval(interval);
                        modal.style.display = "none";
                        resolve(true);
                    }
                } catch (e) {}
                //     TODO: Timeout
            }, 50)
            // Click outside the modal, dimis and redirect
            jQuery(document.body).on('click', function (e) {
                if (e.target.id === 'mysr-modal') {
                    clearInterval(interval);
                    modal.style.display = "none";
                    resolve(false);
                }
            });
        });
    }


    /**
     * @description Validate Field
     */
    const validateField = (field, value, showError = true) => {
        let result = {};
        if (field === 'name') {
            result = MoyasarValidation.cardName(value);
        } else if (field === 'number') {
            result = MoyasarValidation.cardNumber(value);
        } else if (field === 'expiry') {
            result = MoyasarValidation.cardExpiry(value);
        } else if (field === 'cvc') {
            result = MoyasarValidation.cvc(value);
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

    /**
     * @description Handle Change
     * @param e
     */
    const handleChange = (e) => {
        let {name, value} = e.target;

        let result = validateField(name, value, false);
        value = result.showValue;

        setFormData({...formData, [name]: value});
    };

    /**
     * @description Set submit button
     */
    useEffect(() => {
        // MoyasarTriggers.setMoyasarSubmitButton(`${(billing.cartTotal.value / 100).toFixed(2)}`);
        return () => {
            // Remove the submit button
            // MoyasarTriggers.resetPlaceOrderButton();
        };
    }, []);


    return (
        <div>
            <div id="mysr-modal" className="mysr-modal">
                <div className="mysr-modal-content">
                    <iframe id="mysr-3d-secure-iframe" src="about:blank" frameBorder="0" className="moyasar-iframe" height="100%" width="100%"></iframe>
                </div>


            </div>
            <div>
                {data}
                <div className="wc-block-components-text-input is-active">
                    <input type="text" id="card-name" aria-label="Card Name"
                           required
                           aria-invalid="false"
                           title=""
                           placeholder={__mysr_cc("Name on card", 'moyasar')}
                           name="name"
                           value={formData.name}
                           onChange={handleChange}
                    />
                    <label htmlFor="card-name">{__mysr_cc('Card Name', 'moyasar')}</label>
                </div>

                <div className="wc-block-components-text-input is-active">
                    <input type="text" id="card-number" aria-label="Card Number"
                           required
                           aria-invalid="false"
                           title=""
                           name="number"
                           className="moyasar-number"
                           value={formData.number}
                           onChange={handleChange}
                           placeholder={"1234 5678 9101 1121"}
                    />
                    <label htmlFor="card-number">{ __mysr_cc( 'Card Number', 'moyasar' )}</label>
                </div>
                <div className="wc-block-components-text-input is-active">
                    <input type="text" id="card-exp" aria-label="Expiration"
                           required
                           aria-invalid="false"
                           title=""
                           name="expiry"
                           className="moyasar-number"
                           value={formData.expiry}
                           onChange={handleChange}
                           placeholder={"MM / YY"}
                    />
                    <label htmlFor="card-exp">{ __mysr_cc('Expiration' , 'moyasar' ) }</label>
                </div>
                <div className="wc-block-components-text-input is-active">
                    <input type="text" id="card-cvc" aria-label="cvc"
                           required
                           aria-invalid="false"
                           title=""
                           name="cvc"
                           value={formData.cvc}
                           className="moyasar-number"
                           onChange={handleChange}
                           placeholder={"cvc"}
                    />
                    <label htmlFor="card-cvc">CVC</label>
                </div>
            </div>
        </div>
    );
};


export default CreditCardContent;