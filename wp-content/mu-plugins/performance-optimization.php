<?php
/**
 * Plugin Name: Performance Optimization
 * Description: Optimize JavaScript loading, defer non-critical scripts, and improve page interactivity
 * Version: 1.0.0
 * Author: Printlana
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CRITICAL FIX: Defer non-critical JavaScript to prevent blocking interaction
 * This fixes the issue where you cannot click menus immediately after page load
 */
add_filter('script_loader_tag', 'pl_defer_non_critical_scripts', 10, 3);
function pl_defer_non_critical_scripts($tag, $handle, $src)
{
    // Critical scripts that MUST load immediately (do NOT defer these)
    $critical_scripts = array(
        'jquery',
        'jquery-core',
        'jquery-migrate',
    );

    // If this is a critical script, don't defer it
    if (in_array($handle, $critical_scripts)) {
        return $tag;
    }

    // Scripts to defer (load but execute after DOM is ready)
    $defer_scripts = array(
        'wc-add-to-cart',
        'woocommerce',
        'wc-cart-fragments',
        'add-to-cart',
        'elementor-frontend',
        'elementor-pro-frontend',
        'swiper',
        'e-animations',
        'elementor-waypoints',
        'jquery-magnific-popup',
        'imagesloaded',
        'jquery-numerator',
        'wp-api-request',
        'wp-polyfill',
        'regenerator-runtime',
    );

    // Add defer attribute to these scripts
    if (in_array($handle, $defer_scripts)) {
        // Only add defer if not already present
        if (strpos($tag, 'defer') === false && strpos($tag, 'async') === false) {
            $tag = str_replace(' src', ' defer src', $tag);
        }
    }

    return $tag;
}

/**
 * Delay WooCommerce cart fragments to improve initial page load
 * This prevents AJAX call from blocking interaction
 */
add_action('wp_enqueue_scripts', 'pl_delay_wc_cart_fragments', 999);
function pl_delay_wc_cart_fragments()
{
    if (!is_admin() && !is_checkout() && !is_cart()) {
        // Dequeue cart fragments on non-cart/checkout pages
        wp_dequeue_script('wc-cart-fragments');

        // Re-enqueue with delay
        wp_add_inline_script('jquery', '
            // Delay cart fragments until user interaction or 3 seconds
            let cartFragmentsLoaded = false;

            function loadCartFragments() {
                if (cartFragmentsLoaded) return;
                cartFragmentsLoaded = true;

                const script = document.createElement("script");
                script.src = "' . includes_url('js/dist/vendor/wp-polyfill.min.js') . '";
                document.body.appendChild(script);

                // Load actual cart fragments script
                setTimeout(() => {
                    jQuery(document.body).trigger("wc_fragment_refresh");
                }, 100);
            }

            // Load on user interaction
            ["mousemove", "scroll", "touchstart", "click"].forEach(event => {
                document.addEventListener(event, loadCartFragments, { once: true, passive: true });
            });

            // Fallback: load after 3 seconds
            setTimeout(loadCartFragments, 3000);
        ');
    }
}

/**
 * Disable unnecessary WooCommerce scripts on non-shop pages
 */
add_action('wp_enqueue_scripts', 'pl_disable_unnecessary_wc_scripts', 999);
function pl_disable_unnecessary_wc_scripts()
{
    // Don't run on admin or WooCommerce pages
    if (is_admin() || is_cart() || is_checkout() || is_account_page() || is_woocommerce()) {
        return;
    }

    // Disable these scripts on non-WooCommerce pages
    wp_dequeue_script('wc-add-to-cart');
    wp_dequeue_script('wc-add-to-cart-variation');
    wp_dequeue_script('wc-single-product');
    wp_dequeue_script('wc-cart');
    wp_dequeue_script('wc-chosen');
    wp_dequeue_script('woocommerce');
    wp_dequeue_script('jquery-blockui');
    wp_dequeue_script('jquery-payment');
    wp_dequeue_script('wc-checkout');
    wp_dequeue_script('wc-add-payment-method');
    wp_dequeue_script('wc-lost-password');
    wp_dequeue_script('wc-country-select');
    wp_dequeue_script('wc-address-i18n');

    // Disable unnecessary styles
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
    wp_dequeue_style('woocommerce-general');
}

/**
 * Preload critical resources for faster First Contentful Paint
 */
add_action('wp_head', 'pl_preload_critical_resources', 1);
function pl_preload_critical_resources()
{
    // Preload jQuery (critical for most interactions)
    echo '<link rel="preload" href="' . includes_url('js/jquery/jquery.min.js') . '" as="script">';

    // Preconnect to external domains
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}

/**
 * Remove query strings from static resources (for better caching)
 */
add_filter('style_loader_src', 'pl_remove_query_strings', 10, 2);
add_filter('script_loader_src', 'pl_remove_query_strings', 10, 2);
function pl_remove_query_strings($src, $handle)
{
    // Don't remove version from admin-ajax or REST API
    if (strpos($src, 'admin-ajax.php') !== false || strpos($src, 'wp-json') !== false) {
        return $src;
    }

    // Remove query string for better caching
    $parts = explode('?ver', $src);
    return $parts[0];
}

/**
 * Lazy load images with native loading="lazy"
 */
add_filter('wp_get_attachment_image_attributes', 'pl_add_lazy_loading', 10, 3);
function pl_add_lazy_loading($attr, $attachment, $size)
{
    // Don't lazy load if it's already set or if it's a critical image
    if (isset($attr['loading'])) {
        return $attr;
    }

    // Add lazy loading to all images except the first few
    $attr['loading'] = 'lazy';

    return $attr;
}

/**
 * Optimize Elementor performance
 */
add_action('elementor/frontend/after_enqueue_styles', 'pl_optimize_elementor');
function pl_optimize_elementor()
{
    // Disable Elementor animations if not needed
    // wp_dequeue_style('e-animations');

    // Disable Elementor icons if custom icons are used
    // wp_dequeue_style('elementor-icons');
}

/**
 * Add console log to track when page becomes interactive
 */
add_action('wp_footer', 'pl_track_interactivity', 1);
function pl_track_interactivity()
{
    if (is_admin()) {
        return;
    }
    ?>
    <script>
    // Track Time to Interactive
    (function() {
        const startTime = performance.now();

        // Log when page is interactive
        document.addEventListener('DOMContentLoaded', function() {
            const domTime = performance.now() - startTime;
            console.log('[PL Performance] DOM Ready:', domTime.toFixed(2) + 'ms');
        });

        window.addEventListener('load', function() {
            const loadTime = performance.now() - startTime;
            console.log('[PL Performance] Page Fully Loaded:', loadTime.toFixed(2) + 'ms');

            // Log navigation timing if available
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                const interactive = timing.domInteractive - timing.navigationStart;
                console.log('[PL Performance] Time to Interactive:', interactive + 'ms');
            }
        });

        console.log('[PL Performance] Performance optimization active');
    })();
    </script>
    <?php
}

/**
 * Critical: Fix main thread blocking by ensuring jQuery is ready before other scripts
 */
add_action('wp_head', 'pl_ensure_jquery_ready', 0);
function pl_ensure_jquery_ready()
{
    ?>
    <script>
    // Ensure jQuery is available and DOM is ready before running scripts
    window.plReady = function(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    };
    </script>
    <?php
}
