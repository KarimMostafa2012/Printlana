jQuery(document).ready(function($) {
    $(document).on('click', '.pl-request-btn:not(.pl-requested):not(.pl-approved)', function(e) {
        e.preventDefault();

        const btn = $(this);
        const productId = btn.data('product-id');

        btn.prop('disabled', true).text(plVendorRequests.i18n.requesting);

        $.ajax({
            url: plVendorRequests.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_request_to_sell_product',
                nonce: plVendorRequests.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    btn.text(plVendorRequests.i18n.requested).addClass('pl-requested');
                    alert(response.data.message);
                } else {
                    alert(response.data.message || plVendorRequests.i18n.error);
                    btn.prop('disabled', false).text(plVendorRequests.i18n.request_to_sell);
                }
            },
            error: function() {
                alert(plVendorRequests.i18n.error);
                btn.prop('disabled', false).text(plVendorRequests.i18n.request_to_sell);
            }
        });
    });
});
