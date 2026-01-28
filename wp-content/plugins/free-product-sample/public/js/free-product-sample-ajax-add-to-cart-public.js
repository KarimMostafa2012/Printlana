(function($) {
    $(document).ready(function() {
        // Script for ajax sample add to cart
        $(document).on('click', '.dsfps_ajax_add_to_cart', function(e) {
            var $thisbutton = $(this);
            var href;
            try {
                href = $thisbutton.prop('href').split('?')[1];

                if (href.indexOf('add-to-cart') === -1) {
                    return;   
                }
            } catch (err) {
                return;
            }

            e.preventDefault();

            // Split the query string into individual parameters
            let parameters = href.split('&');

            let variationId = null;
            let productId = null;
            parameters.forEach(function(parameter) {
                // Split the parameter into name and value
                let parts = parameter.split('=');
                let paramName = parts[0];
                let paramValue = parts[1];
                
                if (paramName === 'add-to-cart') {
                    productId = paramValue;
                }
                if (paramName === 'variation_id') {
                    variationId = paramValue;
                }
            });

            let data = {
                action: 'dsfps_add_to_cart_action_using_ajax',
                product_id: productId,
                variation_id: variationId,
            };

            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: data,
                beforeSend: function() {
                    $thisbutton.removeClass('added').addClass('loading');
                },
                complete: function() {
                    $thisbutton.addClass('added').removeClass('loading');
                },
                success: function(response) {
                    if ('' !== response || null !== response) {
                        let hasErrorInResponse = $('<div />').html(response).find('.dsfps-add-to-cart-failed').length > 0;
                        if ( !hasErrorInResponse ) {
                            let viewCartBtn = document.createElement('a');
                            viewCartBtn.setAttribute('href', dsfps_coditional_vars.cart_url);
                            viewCartBtn.setAttribute('title', dsfps_coditional_vars.view_cart);
                            viewCartBtn.setAttribute('class', 'dsfps_added_to_cart added_to_cart wc-forward');
                            viewCartBtn.innerText = dsfps_coditional_vars.view_cart;
                            if ( $thisbutton.parent().find('.dsfps_added_to_cart').length === 0 ) {
                                $thisbutton[0].insertAdjacentElement('afterend', viewCartBtn);
                            }

                            // Update mini cart fragment
                            $(document.body).trigger('wc_fragment_refresh');
                        } else {
                            if ( $('.dsfps-add-to-cart-failed').length === 0 ) {
                                if ( $('.site-content .woocommerce').length !== 0 ) {
                                    $('.site-content .woocommerce').append(response);
                                } else {
                                    $thisbutton.parent().append(response);
                                }

                                setTimeout( function(){
                                    $('.dsfps-add-to-cart-failed').remove();
                                    $('.woocommerce-error').remove();
                                }, 4000);
                            }

                            // Scroll to top if any error
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                        }
                    }
                }
            });

            return false;
        });

        // Script to display sample button on shop page for variable product (Variation Swatches Plugin by Emran Ahmed)
        $(document).on('change', '.wvs-archive-product-wrapper .woo-variation-raw-select', function() {
            let $thisbutton = $(this).parents('.wvs-archive-product-wrapper').find('.add_to_cart_button');            

            let href;
            try {
                href = $thisbutton.prop('href').split('?')[1];
                
                // Remove sample button if not selected any attributes
                if ( undefined === href ) {
                    $(this).parents('.wvs-archive-product-wrapper').find('.dsfps_ajax_add_to_cart').remove();
                }

                if (href.indexOf('add-to-cart') === -1) {
                    return;   
                }
            } catch (err) {
                return;
            }

            // Split the query string into individual parameters
            let parameters = href.split('&');

            let variationId = null;
            let productId = null;
            parameters.forEach(function(parameter) {
                // Split the parameter into name and value
                let parts = parameter.split('=');
                let paramName = parts[0];
                let paramValue = parts[1];
                
                if (paramName === 'add-to-cart') {
                    productId = paramValue;
                }
                if (paramName === 'variation_id') {
                    variationId = paramValue;
                }
            });

            if ( (productId !== null || productId !== '') && (variationId !== null || variationId !== '') ) {
                $(this).parents('.wvs-archive-product-wrapper').find('.dsfps_ajax_add_to_cart').remove();
                if ( $(this).parents('.wvs-archive-product-wrapper').find('.dsfps_ajax_add_to_cart').length === 0 ) {
                    let sampleCartBtn = document.createElement('a');
                    sampleCartBtn.setAttribute('href', $thisbutton.prop('href'));
                    sampleCartBtn.setAttribute('name', 'dsfps-variable-add-to-cart-disable');
                    sampleCartBtn.setAttribute('class', 'button product_type_variable dsfps_ajax_add_to_cart ajax_add_to_cart');
                    sampleCartBtn.setAttribute('data-quantity', '1');
                    sampleCartBtn.setAttribute('rel', 'nofollow');
                    sampleCartBtn.innerText = dsfps_coditional_vars.sample_btn_label;

                    $thisbutton[0].insertAdjacentElement('afterend', sampleCartBtn);
                }
            } else {
                // Remove sample button if not found product and variation id
                $(this).parents('.wvs-archive-product-wrapper').find('.dsfps_ajax_add_to_cart').remove();
            }
        });

        // Script for add samples to cart via Ajax from single product page if option enabled
        var ajax_add_to_cart_status = $('#fps-ajax-add-to-cart-flag').val();
        if ( 'yes' === ajax_add_to_cart_status ) {
            $(document).on('click', '.single_add_to_cart_button', function() {
                if (!$(this).hasClass('.dsfps-free-sample-ajax-btn')) {
                    setTimeout(function() {
                        $('.dsfps-free-sample-ajax-btn').removeClass('loading');
                    }, 55); // Remove the class after 55 milliseconds
                }
            });
            $('.dsfps-free-sample-ajax-btn').on('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var $thisbutton = $(this);

                var $form = $thisbutton.closest('form.cart');
                let id = $thisbutton.val();
                let product_qty = $form.find('input[name=quantity]').val() || 1;
                let product_id = $form.find('input[name=product_id]').val() || id;
                let variation_id = $form.find('input[name=variation_id]').val() || 0;
                var final_data = {
                    action: 'dsfps_single_product_add_to_cart_using_ajax',
                    product_id: product_id,
                    quantity: product_qty,
                    variation_id: variation_id,
                };

                var otherData = $form.find('input:not([name="add-to-cart"]):not([name="quantity"]):not([name="product_id"]):not([name="variation_id"]):not([type="radio"]):not([type="button"]), input[type="radio"]:checked, select, button, textarea').serializeArray();
                $.each(otherData, function( i, item ) {
                    final_data[item.name] = item.value;
                });

                $.ajax({
                    type: 'post',
                    url: wc_add_to_cart_params.ajax_url,
                    data: final_data,
                    beforeSend: function () {
                        $thisbutton.removeClass('added').addClass('loading');
                    },
                    complete: function () {
                        $thisbutton.addClass('added').removeClass('loading');
                    }, 
                    success: function (response) {
                        if ('' !== response || null !== response) {
                            let hasErrorInResponse = $('<div />').html(response).find('.dsfps-add-to-cart-failed').length > 0;
                            if ( !hasErrorInResponse ) {
                                let viewCartBtn = document.createElement('a');
                                viewCartBtn.setAttribute('href', dsfps_coditional_vars.cart_url);
                                viewCartBtn.setAttribute('title', dsfps_coditional_vars.view_cart);
                                viewCartBtn.setAttribute('class', 'dsfps_added_to_cart added_to_cart');
                                viewCartBtn.innerText = dsfps_coditional_vars.view_cart;
                                if ( $($form).find('.added_to_cart').length === 0 && $('.wc-variation-selection-needed').length === 0 ) {
                                    $thisbutton[0].insertAdjacentElement('afterend', viewCartBtn);
                                }

                                if ( $('.dsfps-ajax-success-msg').length === 0 && $('.wc-variation-selection-needed').length === 0 ) {
                                    $thisbutton.parent().append(response);
                                    setTimeout( function(){
                                        $('.dsfps-ajax-success-msg').remove();
                                        $('.dsfps_added_to_cart').remove();
                                    }, 2500);
                                }

                                // Update mini cart fragment
                                $(document.body).trigger('wc_fragment_refresh');
                            } else {
                                if ( $('.dsfps-add-to-cart-failed').length === 0 ) {
                                    if ( $('.site-content .woocommerce').length !== 0 ) {
                                        $('.site-content .woocommerce').append(response);
                                    } else {
                                        $thisbutton.parent().append(response);
                                    }

                                    setTimeout( function(){
                                        $('.dsfps-add-to-cart-failed').remove();
                                        $('.woocommerce-error').remove();
                                    }, 2500);
                                }

                                // Scroll to top if any error
                                $('html, body').animate({ scrollTop: 0 }, 'slow');
                            }
                        }
                    }, 
                }); 
            });
        }

        // Script to enable and disable sample button based on the max qty
        $(document).on('change', '.quantity .input-text.qty.text', function() {
            var sample_product_max_qty = $('#fps-sample-product-data').attr('sample-max-qty');
            if ( '' === sample_product_max_qty || parseInt(dsfps_coditional_vars.max_sample_qty) < parseInt(sample_product_max_qty) ) {
                var sample_add_to_cart_btn = $('.single_add_to_cart_button.dsfps-free-sample-btn');
                if ( parseInt($(this).val()) > parseInt(dsfps_coditional_vars.max_sample_qty) ) {
                    sample_add_to_cart_btn.addClass('disable-cart-button');
                } else {
                    sample_add_to_cart_btn.removeClass('disable-cart-button');
                }
            }
        });
    });
})(jQuery);
