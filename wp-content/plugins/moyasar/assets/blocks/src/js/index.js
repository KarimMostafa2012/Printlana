/**
 * External dependencies
 */
const registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
const getPaymentMethodData = window.wc.wcSettings.getPaymentMethodData;

/**
 * Payment Methods
 */
import CreditCardContent from "./forms/creditcard";
import STCPayContent from "./forms/stcpay";
import ApplePayContent from "./forms/applepay";
import SamsungPayContent from "./forms/samsungpay";

const i18n = window.wp.i18n
const settings = getPaymentMethodData('moyasar', {});

const isEnabled = (method) => settings[method].enabled;
const getTitle = (method) => settings[method].title;
const getIcon = (method) => settings[method].icon;
const  getSupports = (method) => settings[method].supports;
const Icon = (props) => {
    const icons = props.images;
    if (!icons) {
        return '';
    }
    return icons.map((icon, index) => {
        return <img key={index} src={icon} style={{float: i18n.isRTL() ? 'left' : 'right', marginRight: i18n.isRTL() ? '0px' : '15px', marginLeft: i18n.isRTL() ? '15px' : '0px', maxWidth: '35px', display: 'inline'}} alt={icon}/>
    });

}
const Label = (props) => {
    return <span style={{width: '100%'}}>
            {getTitle(props.setting)}
        <Icon images={getIcon(props.setting)}/>
        </span>
};


/**
 * Credit card payment method
 */
const creditCardPaymentName = 'moyasar-credit-card';
if (settings[creditCardPaymentName]) {
    const creditCardPaymentMethod = {
        name: creditCardPaymentName,
        label: <Label setting={creditCardPaymentName}/>,
        content: <CreditCardContent setting={settings[creditCardPaymentName]} />,
        edit: <CreditCardContent setting={settings[creditCardPaymentName]} />,
        canMakePayment: () => isEnabled(creditCardPaymentName),
        ariaLabel: getTitle(creditCardPaymentName),
        supports: {
            features: getSupports(creditCardPaymentName)
        },
    };

    registerPaymentMethod(creditCardPaymentMethod);
}


/**
 * STC Pay payment method
 */
const stcPayPaymentName = 'moyasar-stc-pay';
if (settings[stcPayPaymentName]) {
    const stcPayPaymentMethod = {
        name: stcPayPaymentName,
        label: <Label setting={stcPayPaymentName}/>,
        content: <STCPayContent setting={settings[stcPayPaymentName]} />,
        edit: <STCPayContent setting={settings[stcPayPaymentName]} />,
        canMakePayment: () => isEnabled(stcPayPaymentName),
        ariaLabel: getTitle(stcPayPaymentName),
        supports: {
            features: getSupports(stcPayPaymentName),
        },
    };
    registerPaymentMethod(stcPayPaymentMethod);
}


/**
 * Apple Pay payment method
 */
const applePayPaymentName = 'moyasar-apple-pay';
if (settings[applePayPaymentName]) {
    const applePayPaymentMethod = {
        name: applePayPaymentName,
        label: <Label setting={applePayPaymentName}/>,
        content: <ApplePayContent setting={settings[applePayPaymentName]} />,
        edit: <ApplePayContent setting={settings[applePayPaymentName]} />,
        canMakePayment: () => isEnabled(applePayPaymentName),
        ariaLabel: getTitle(applePayPaymentName),
        supports: {
            features: getSupports(applePayPaymentName),
        },
    };

    /**
     * Register Apple Pay payment method if the browser supports it.
     */
    if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
        registerPaymentMethod(applePayPaymentMethod);
    }
}

/**
 * Samsung Pay payment method
 */
const samsungPayPaymentName = 'moyasar-samsung-pay';
const samsungConfig = settings[samsungPayPaymentName];
if (settings[samsungPayPaymentName]) {
    const samsungPayPaymentMethod = {
        name: samsungPayPaymentName,
        label: <Label setting={samsungPayPaymentName}/>,
        content: <SamsungPayContent setting={settings[samsungPayPaymentName]} />,
        edit: <SamsungPayContent setting={settings[samsungPayPaymentName]} />,
        canMakePayment: () => isEnabled(samsungPayPaymentName),
        ariaLabel: getTitle(samsungPayPaymentName),
        supports: {
            features: getSupports(samsungPayPaymentName),
        },
    };

    /**
     * Register Samsung Pay payment method if the browser supports it.
     */
    if (await MoyasarSamsungHelper.initializeSamsungPay(samsungConfig['serviceId'], samsungConfig['supportedNetworks'])) {
        registerPaymentMethod(samsungPayPaymentMethod);
    }
}