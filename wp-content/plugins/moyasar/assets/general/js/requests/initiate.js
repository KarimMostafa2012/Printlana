/**
 * @description Moyasar API Request
 */
class MoyasarRequest {
    constructor(baseUrl, publishableApiKey, method) {
        this.baseUrl = baseUrl;
        this.publishableApiKey = publishableApiKey;
        this.method = method;
    }

    /**
     * @description Initiate Credit Card Payment
     */
    async initiateCreditCard({...formData}) {
        // Prepare the data
        const data = {
            ...formData,
            callback_url: window.location.href + "/payment",
            publishable_api_key: this.publishableApiKey,
            save_only: true,
        };
        // Return a promise
        return new Promise(async (resolve, reject) => {
            let response = null;
            try {
                response = await fetch(`${this.baseUrl}/v1/tokens`, {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: {
                        'Content-Type': 'application/json',
                        'Mysr-Client': typeof variable !== 'undefined' ? moyasar?.version : '7.*.*',
                    },
                });
                const responseData = await response.json();
                if (response.status > 299) {
                    reject(responseData);
                } else {
                    resolve(responseData);
                }
            } catch (error) {
                console.log(error)
                response = error.response;
                reject(response);
            }
        });
    }

    /**
     * @description Initiate Apple Pay Payment
     */
    async initiateApplePay({...formData}) {

        const data = {
            ...formData,
            publishable_api_key: this.publishableApiKey,
        };

        return new Promise(async (resolve, reject) => {
            // Use  Fetch
            let response = null;
            try {
                response = await fetch(`${this.baseUrl}/v1/applepay/initiate`, {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: {
                        'Content-Type': 'application/json',
                        'Mysr-Client': typeof variable !== 'undefined' ? moyasar?.version : '7.*.*',
                    },
                });
                const responseData = await response.json();
                if (response.status > 299) {
                    reject(responseData);
                } else {
                    resolve(responseData);
                }
            } catch (error) {
                response = error.response;
                reject(response);
            }
        });
    }

    /**
     * @description Retrieve the final price
     */
    getFinalPrice(total) {
        return (total / 100).toFixed(2);
    }
}