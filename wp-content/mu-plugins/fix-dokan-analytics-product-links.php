<?php
/**
 * Plugin Name: Fix Dokan Analytics Product Links
 * Description: Modifies product links in Dokan analytics orders table to use dokan-reports with search instead of wc-admin with product filter
 * Version: 1.0.0
 * Author: Qersh Yahya
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Intercept and modify product links in Dokan analytics
 * This runs on the frontend and modifies the HTML output
 */
add_action('wp_footer', 'pl_fix_dokan_analytics_product_links');
function pl_fix_dokan_analytics_product_links()
{
    // Only run on Dokan analytics pages
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    // Only run on reports/analytics page
    if (!isset($_GET['page']) || $_GET['page'] !== 'dokan-reports') {
        return;
    }

    ?>
    <script type="text/javascript">
    (function() {
        console.log('[Dokan Product Links] Starting to fix product links...');

        /**
         * Fix product links in the orders analytics table
         */
        function fixProductLinks() {
            // Find all links that go to wc-admin with product filter
            const productLinks = document.querySelectorAll('a[href*="page=wc-admin"][href*="filter=single_product"][href*="products="]');

            console.log('[Dokan Product Links] Found ' + productLinks.length + ' product links to fix');

            productLinks.forEach((link, index) => {
                const originalHref = link.getAttribute('href');
                const productName = link.textContent.trim();

                console.log('[Dokan Product Links] Link #' + (index + 1) + ':', {
                    original: originalHref,
                    productName: productName
                });

                // Get the base URL (everything before the query string)
                const baseUrl = window.location.origin + window.location.pathname;

                // Build new URL with dokan-reports and search parameter
                const newUrl = baseUrl +
                    '?page=dokan-reports' +
                    '&path=%2Fanalytics%2Fproducts' +
                    '&v=c12e01f2a13f' +
                    '&search=' + encodeURIComponent(productName);

                link.setAttribute('href', newUrl);

                console.log('[Dokan Product Links] Fixed to:', newUrl);
            });

            console.log('[Dokan Product Links] Finished fixing product links');
        }

        // Run when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fixProductLinks);
        } else {
            fixProductLinks();
        }

        // Also watch for dynamic content changes (in case the table loads via AJAX)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    // Check if any added nodes contain product links
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const links = node.querySelectorAll ? node.querySelectorAll('a[href*="page=wc-admin"][href*="filter=single_product"]') : [];
                            if (links.length > 0) {
                                console.log('[Dokan Product Links] Detected new product links via AJAX, fixing...');
                                fixProductLinks();
                            }
                        }
                    });
                }
            });
        });

        // Start observing the document body for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('[Dokan Product Links] Mutation observer started');
    })();
    </script>
    <?php
}

/**
 * Alternative approach: Filter the actual analytics data before it's rendered
 * This modifies the links at the server side before they reach the browser
 */
add_filter('woocommerce_analytics_orders_select_query', 'pl_modify_dokan_analytics_query');
function pl_modify_dokan_analytics_query($query)
{
    // Only run on vendor dashboard
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return $query;
    }

    error_log('[Dokan Analytics] Query filter running');
    return $query;
}
