/**
 * Mini cart block customizations for sample products
 */
(function() {
    // Fallback DOM manipulation for cases where the filter doesn't work
    function updateMiniCartNames() {
        const miniCartItems = document.querySelectorAll('.wc-block-mini-cart__products-table .wc-block-components-cart-item');
        if (!miniCartItems || !miniCartItems.length) {
            return;
        }
        
        miniCartItems.forEach(item => {
            // Check if this is a sample product
            const isSample = item.innerHTML.includes('fps_free_sample') || 
                           item.innerHTML.includes('dsfps_product_type') || 
                           item.innerHTML.includes('sample');
            
            if (isSample) {
                const nameElement = item.querySelector('.wc-block-components-product-name');
                if (nameElement && !nameElement.innerHTML.includes('Sample -')) {
                    nameElement.innerHTML = 'Sample - ' + nameElement.innerHTML;
                }
            }
        });
    }

    // Run the update when the mini cart updates
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList' && 
                (mutation.target.classList.contains('wc-block-mini-cart__drawer') ||
                 mutation.target.classList.contains('wc-block-components-drawer__content'))) {
                updateMiniCartNames();
            }
        });
    });

    // Start observing when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            updateMiniCartNames();
        });
    } else {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        updateMiniCartNames();
    }

    // Also update when mini cart button is clicked
    document.addEventListener('click', (e) => {
        if (e.target.closest('.wc-block-mini-cart__button')) {
            setTimeout(updateMiniCartNames, 300);
        }
    });
})();
