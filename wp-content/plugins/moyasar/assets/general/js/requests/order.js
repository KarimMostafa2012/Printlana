/**
 * @description Moyasar Order Triggers
 */
class MoyasarOrder {

    static orderRequest = null
    constructor() {
    }


    /**
     * @description Submit The Serialized Form
     */
    async submit(formData) {
        return new Promise(async (resolve, reject) => {
            // Use  Fetch
            try{
                const response = await fetch(wc_checkout_params.checkout_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData
                });
                const responseData = await response.json();

                if (responseData.result === 'success') {
                    resolve(responseData);
                } else {
                    document.body.dispatchEvent(new CustomEvent('checkout_error', { detail: responseData }));
                    reject(responseData);
                }

            }catch (error){
                document.body.dispatchEvent(new CustomEvent('checkout_error', { detail: 'An unexpected error occurred.' }));
                reject(error.response);
            }
        });
    }

     /**
     * @description Get Order Details including Cart Total
     */
    async getOrderDetails() {
        if (MoyasarOrder.orderRequest){
            return MoyasarOrder.orderRequest;
        }

        MoyasarOrder.orderRequest = new Promise(async (resolve, reject) => {
            try {
                const response = await fetch('?rest_route=/moyasar/v2/order-details', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const responseData = await response.json();

                if (responseData.success) {
                    resolve(responseData);
                } else {
                    reject(responseData);
                }

            } catch (error) {
                reject(error);
            } finally {
                MoyasarOrder.orderRequest = null
            }
        });

        return MoyasarOrder.orderRequest
    }
}