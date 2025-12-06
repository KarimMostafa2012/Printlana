(function($) {
    $(document).ready(function() {
    	// Script for sample compatibility with cart/checkout block
        if (window.wc && window.wc.blocksCheckout) {
            const { registerCheckoutFilters } = window.wc.blocksCheckout;
            const modifyItemName = ( defaultValue, extensions, args ) => {
                const isCartContext = args?.context === 'cart';
                const dsfps_product_type = args?.cartItem?.extensions?.dsfps_free_product_sample?.dsfps_product_type;

                if ( 'sample' === dsfps_product_type ) {
                    return args?.cartItem?.extensions?.dsfps_free_product_sample?.name;
                }
                if ( ! isCartContext ) {
                    return defaultValue;
                }
                return `${ defaultValue }`;
            };

            registerCheckoutFilters( 'dsfps-sample-extension', {
                itemName: modifyItemName,
            } );
        }

        // Script for submit sample add to cart form
        $('.dsfps-free-sample-submit-btn').on('click', function() {
            var id = $(this).val();
            $('.dsfps-add-to-cart-id').val(id);
            $('.dsfps-add-to-cart-id').attr('name', 'dsfps-simple-add-to-cart');
            $(this).closest('form').submit();
        });
	});
})(jQuery);