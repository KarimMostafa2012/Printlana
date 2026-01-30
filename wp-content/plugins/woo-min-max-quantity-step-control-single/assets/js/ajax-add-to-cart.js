/**
 * AJAX Add to Cart handler for WC Min Max Quantity plugin
 * 
 * Converted to structured OOP style
 * @since 3.6.0
 */
(function ($) {
    'use strict';

    const WCMMQAjaxCart = {
        init: function () {
            this.bindEvents();
        },

        // =========================
        // Event Bindings
        // =========================
        bindEvents: function () {
            const self = this;

            // Handle add to cart button click
            $(document.body).on('click', '.single_add_to_cart_button', function (e) {
                e.preventDefault();
                self.handleAddToCart($(this));
            });
        },

        // =========================
        // Core Logic
        // =========================
        handleAddToCart: function ($button) {
            if (typeof wc_add_to_cart_params === 'undefined') {
                WCMMQCustom.showNotification('WooCommerce parameters not found!', 'error');
                return;
            }

            // Disabled or variation not selected
            if ($button.hasClass('disabled') || $button.hasClass('wc-variation-selection-needed')) {
                WCMMQCustom.showNotification('Please select variation before adding to cart.', 'error');
                return;
            }

            const $form = $button.closest('form.cart');
            const id = $button.val();

            const productQty = $form.find('input[name=quantity]').val() || 1;
            const productId = $form.find('input[name=product_id]').val() || id;
            const variationId = $form.find('input[name=variation_id]').val() || 0;

            const data = {
                action: 'woocommerce_ajax_add_to_cart',
                product_id: productId,
                product_sku: '',
                quantity: productQty,
                variation_id: variationId,
                _nonce: WCMMQ_DATA._nonce
            };

            $(document.body).trigger('adding_to_cart', [$button, data]);

            this.sendAjaxRequest($button, data);
        },

        sendAjaxRequest: function ($button, data) {
            $.ajax({
                type: 'post',
                url: wc_add_to_cart_params.ajax_url,
                data: data,
                beforeSend: function () {
                    $button.removeClass('added').addClass('loading');
                },
                complete: function () {
                    $button.addClass('added').removeClass('loading');
                },
                success: function (response) {
                    WCMMQAjaxCart.handleResponse(response, $button);
                },
                error: function () {
                    WCMMQCustom.showNotification('Something went wrong. Please try again.', 'error');
                }
            });
        },

        handleResponse: function (response, $button) {

            if (response.error && response.product_url) {
                window.location = response.product_url;
                return;
            } else if (response.error && response.message && response.message.length > 0) {
                WCMMQCustom.showNotification(response.message, 'error');
            } else {
                // Redirect if WooCommerce setting enabled
                if (wc_add_to_cart_params.cart_redirect_after_add === 'yes') {
                    window.location = wc_add_to_cart_params.cart_url;
                }

                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                WCMMQCustom.showNotification('🎁 Product added!', 'success', 2000);
            }
        },
    };

    // Init
    $(document).ready(function () {
        WCMMQAjaxCart.init();
    });

    // Expose for debugging
    window.WCMMQAjaxCart = WCMMQAjaxCart;

})(jQuery);
