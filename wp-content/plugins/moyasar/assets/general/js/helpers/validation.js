/**
 * @description Moyasar Validation class
 */
const __mysr_validation = window.wp.i18n.__

class MoyasarValidation {

    /**
     * @description Clear numebr from any non-numeric characters & convert arabic numbers to english
     * @param value
     * @returns {string}
     */
    static numericInputComputed(value) {
        // Remove spaces
        value = value.replace(/\s/g, "");
        // convert to arabic numbers to english
		const arabicToEnglish = {
			'٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
			'٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
		};
		value = value.replace(/[٠-٩]/g, match => arabicToEnglish[match]);
        // Replace Characters
        value = value.replace(/[^0-9]/g, '');
        return value;
    }

    /**
     * @description Detect the card type (visa, mc, amex, mada)
     * @param value
     * @returns {boolean}
     */
    static detectCardType(value) {
        let madaStarts = ["22337902", "22337986", "22402030", "242030", "403024", "406136", "406996", "40719700", "40739500", "407520", "409201", "410621", "410685", "410834", "412565", "417633", "419593", "420132", "421141", "422817", "422818", "422819", "428331", "428671", "428672", "428673", "431361", "432328", "434107", "439954", "440533", "440647", "440795", "442463", "445564", "446393", "446404", "446672", "45488707", "455036", "455708", "457865", "457997", "458456", "462220", "468540", "468541", "468542", "468543", "474491", "483010", "483011", "483012", "484783", "486094", "486095", "486096", "489318", "489319", "504300", "513213", "515079", "516138", "520058", "521076", "52166100", "524130", "524514", "524940", "529415", "529741", "530060", "531196", "535825", "535989", "536023", "537767", "53973776", "543085", "543357", "549760", "554180", "555610", "558563", "588845", "588848", "588850", "589206", "604906", "605141", "636120", "968201", "968202", "968203", "968204", "968205", "968206", "968207", "968208", "968209", "968211"];
        let detected = '';
        // Check for different card types
        if (/^4/.test(value)) {
            detected = 'visa';
        }
        if (/^5[1-5]/.test(value) || /^2[2-7]/.test(value)) {
            detected = 'mc';
        }
        if (/^3[47]/.test(value)) {
            detected = 'amex';
        }
        if (madaStarts.includes(value)) {
            // if starts with following
            detected = 'mada';
        }
        return detected !== '';
    }

    /**
     * @description Check if the card number is valid using Luhn algorithm
     * @param text
     * @returns {boolean}
     */
    static isLuhn(text) {
        let sum = 0
        let bit = 1
        let array = [0, 2, 4, 6, 8, 1, 3, 5, 7, 9]
        let length = text.length
        let value

        while(length) {
            value = parseInt(text.charAt(--length), 10)
            bit ^= 1
            sum += bit ? array[value] : value
        }

        return sum % 10 === 0
    }

    /**
     * @description validate the card number
     * @param value
     * @returns Object {isValid: boolean, message: string, showValue: string, submitValue: string}
     */
    static cardNumber(value) {
        value = MoyasarValidation.numericInputComputed(value);
        // Add spaces after every 4 characters
        let showValue = value.replace(/(\d{4})/g, '$1 ').trim();
        // Check if the value is empty
        if (value === '') {
            return {
                isValid: false,
                message: __mysr_validation('Please enter the card number.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }
        // Check if the value is less than 16 characters
        if (value.length < 16) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid card number.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }

        // Check if the value is more than 16 characters remove only the last character
        if (value.length > 16) {
            showValue = showValue.slice(0, 19);
            value = value.slice(0, 16);
        }

        // Check if the card number is valid
        if (!MoyasarValidation.detectCardType(value)) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid card number.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }

        // Check if the card number is valid using Luhn algorithm
        if (!MoyasarValidation.isLuhn(value) && value.length === 16) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid card number.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }


        return {
            isValid: true,
            message: '',
            showValue: showValue,
            submitValue: value
        };
    }

    /**
     * @description validate card name
     * @param value
     * @returns Object {isValid: boolean, message: string, showValue: string, submitValue: string}
     */
    static cardName(value) {
        // Check if the value is empty
        if (value === '') {
            return {
                isValid: false,
                message: __mysr_validation('Please enter the card name.', 'moyasar'),
                showValue: value,
                submitValue: value
            };
        }
        // Check if the value is less than 3 characters
        if (value.length < 3) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid card name.', 'moyasar'),
                showValue: value,
                submitValue: value
            };
        }
        // Check if it has first name and last name
        if (!value.includes(' ')) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid card name.', 'moyasar'),
                showValue: value,
                submitValue: value
            };
        }

        return {
            isValid: true,
            message: '',
            showValue: value,
            submitValue: value
        };
    }

    /**
     * @description validate card expiry date
     * @param value
     * Example param value: 12/22
     * @returns Object {isValid: boolean, message: string, showValue: string, submitValue: string}
     */
    static cardExpiry(value) {
        value = MoyasarValidation.numericInputComputed(value);
        let showValue = value;
        // Check if greater than 5 characters remove the last character
        if (value.length > 4) {
            value = value.slice(0, 4);
        }
        // Add slash after 2 characters to show the expiry date
        if (value.length >= 3) {
            showValue = value.replace(/(\d{2})/, '$1 / ').trim();
        }

        // check if first two digits are more than 12 or remove them
        if (value.length >= 2 && parseInt(value.slice(0, 2)) > 12) {
            value = value.slice(0, 1);
            showValue = value.replace(/(\d{2})/, '$1 / ').trim();
        }


        // Check if the value is empty
        if (value === '') {
            return {
                isValid: false,
                message: __mysr_validation('Please enter the expiry date.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }
        // Check if the value is less than 5 characters
        if (value.length < 4) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid expiry date.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }

        return {
            isValid: true,
            message: '',
            showValue: showValue,
            submitValue: value
        };

    }

    /**
     * @description validate cvc
     * @param value
     * Example param value: 123
     * @returns Object {isValid: boolean, message: string, showValue: string, submitValue: string}
     */
    static cvc(value) {
        value = MoyasarValidation.numericInputComputed(value);
        // Check if the value is empty
        if (value === '') {
            return {
                isValid: false,
                message: __mysr_validation('Please enter the cvc.', 'moyasar'),
                showValue: value,
                submitValue: value
            };
        }

        return {
            isValid: true,
            message: '',
            showValue: value,
            submitValue: value
        };
    }

    /**
     * @description validate Phone number
     * @param value
     * Example param value: 05xxxxxxxx
     * @returns Object {isValid: boolean, message: string, showValue: string, submitValue: string}
     */
    static phoneNumber(value) {
        value = MoyasarValidation.numericInputComputed(value);
        let showValue = value;
        // Add spaces after every 3 characters
        showValue = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
        // Check if the value is empty
        if (value === '') {
            return {
                isValid: false,
                message: __mysr_validation('Please enter the phone number.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }

        // Check if the value is more than 10 numbers remove the last number
        if (value.length > 10) {
            showValue = showValue.slice(0, 12);
            value = value.slice(0, 10);
        }

        // Check if the value is less than 10 characters
        if (value.length !== 10) {
            return {
                isValid: false,
                message: __mysr_validation('Please enter a valid phone number.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }

        // Check if starts with 05
        if (!value.startsWith('05')) {
            return {
                isValid: false,
                message: __mysr_validation('Phone number must start with 05.', 'moyasar'),
                showValue: showValue,
                submitValue: value
            };
        }

        return {
            isValid: true,
            message: '',
            showValue: showValue,
            submitValue: value
        };
    }

    /**
     * @description validate OTP
     * @param value
     * Example param value: 1234
     * @returns Object {isValid: boolean, message: string, showValue: string, submitValue: string}
     */
    static otp(value) {
        value = MoyasarValidation.numericInputComputed(value);
        // Check if the value is empty
        if (value === '') {
            return {
                isValid: false,
                message: __mysr_validation('Please enter the OTP.', 'moyasar'),
                showValue: value,
                submitValue: value
            };
        }

        return {
            isValid: true,
            message: '',
            showValue: value,
            submitValue: value
        };
    }
}