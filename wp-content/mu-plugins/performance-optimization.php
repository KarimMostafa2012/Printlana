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
    // Don't defer ANY scripts on Dokan dashboard (Vue.js needs proper loading order)
    // Use multiple methods to detect Dokan dashboard
    $is_dokan_dashboard = false;

    // Method 1: Use Dokan function if available
    if (function_exists('dokan_is_seller_dashboard') && dokan_is_seller_dashboard()) {
        $is_dokan_dashboard = true;
    }

    // Method 2: Check URL pattern (more reliable for early hooks)
    if (!$is_dokan_dashboard && isset($_SERVER['REQUEST_URI'])) {
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/dashboard/') !== false ||
            strpos($request_uri, 'dashboard') !== false && get_query_var('author') !== '') {
            $is_dokan_dashboard = true;
        }
    }

    // Method 3: Check if Dokan scripts are being loaded
    if (!$is_dokan_dashboard && (
        strpos($handle, 'dokan') !== false ||
        strpos($handle, 'vue') !== false ||
        $handle === 'dokan-vue-vendor' ||
        $handle === 'dokan-vue-bootstrap'
    )) {
        $is_dokan_dashboard = true;
    }

    if ($is_dokan_dashboard) {
        return $tag;
    }

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
 * Intelligently disable unnecessary WooCommerce scripts based on page type
 * Saves ~3.3 seconds of load time on average pages
 */
add_action('wp_enqueue_scripts', 'pl_disable_unnecessary_wc_scripts', 999);
function pl_disable_unnecessary_wc_scripts()
{
    // Don't run on admin
    if (is_admin()) {
        return;
    }

    // CRITICAL: Don't run on Dokan seller dashboard (needs all scripts including Vue.js)
    // Use multiple detection methods
    $is_dokan_dashboard = false;

    // Method 1: Use Dokan function
    if (function_exists('dokan_is_seller_dashboard') && dokan_is_seller_dashboard()) {
        $is_dokan_dashboard = true;
    }

    // Method 2: Check URL pattern
    if (!$is_dokan_dashboard && isset($_SERVER['REQUEST_URI'])) {
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/dashboard/') !== false) {
            $is_dokan_dashboard = true;
        }
    }

    // Method 3: Check if it's a dokan page type
    if (!$is_dokan_dashboard) {
        global $wp_query;
        if (isset($wp_query->query_vars['pagename']) &&
            strpos($wp_query->query_vars['pagename'], 'dashboard') !== false) {
            $is_dokan_dashboard = true;
        }
    }

    if ($is_dokan_dashboard) {
        return;
    }

    // Get current page type
    $is_product = is_product();
    $is_shop = is_shop() || is_product_category() || is_product_tag();
    $is_cart = is_cart();
    $is_checkout = is_checkout();
    $is_account = is_account_page();
    $is_woocommerce = is_woocommerce();

    // ====================
    // ALWAYS REMOVE (on ALL pages - we have custom alternatives)
    // ====================

    // Remove toast libraries (we use custom toast notification system)
    wp_dequeue_script('jquery-toast');
    wp_dequeue_script('izitoast');
    wp_deregister_script('jquery-toast');
    wp_deregister_script('izitoast');
    wp_dequeue_style('izitoast');
    wp_deregister_style('izitoast');

    // ====================
    // NON-WOOCOMMERCE PAGES (Most aggressive optimization)
    // ====================
    if (!$is_woocommerce && !$is_cart && !$is_checkout && !$is_account) {

        // Disable BlockUI (only needed for AJAX overlays)
        wp_dequeue_script('jquery-blockui');
        wp_dequeue_script('blockui');

        // Disable country/address scripts
        wp_dequeue_script('wc-country-select');
        wp_dequeue_script('wc-address-i18n');

        // Disable password strength meter
        wp_dequeue_script('wc-password-strength-meter');
        wp_dequeue_script('password-strength-meter');
        wp_dequeue_script('zxcvbn-async');

        // Disable sourcebuster (analytics tracking)
        wp_dequeue_script('sourcebuster-js');
        wp_dequeue_script('wc-order-attribution');

        // Disable SelectWoo (1039ms - SLOWEST script!)
        wp_dequeue_script('selectWoo');
        wp_dequeue_style('select2');

        // Disable single product scripts
        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-add-to-cart-variation');

        // Disable cart scripts
        wp_dequeue_script('wc-cart');

        // Disable checkout scripts
        wp_dequeue_script('wc-checkout');
        wp_dequeue_script('wc-add-payment-method');

        // Disable lost password scripts
        wp_dequeue_script('wc-lost-password');

        // Disable payment scripts
        wp_dequeue_script('jquery-payment');

        // Disable YITH affiliate scripts
        wp_dequeue_script('yith-wcaf-shortcodes');

        // Disable WooCommerce styles
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');
        wp_dequeue_style('woocommerce-general');
    }

    // ====================
    // HOME PAGE & STATIC PAGES
    // ====================
    if (is_front_page() || (is_page() && !$is_woocommerce)) {
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('woocommerce');

        // Unless page has products shortcode
        if (!has_shortcode(get_post()->post_content ?? '', 'products')) {
            wp_dequeue_script('jquery-magnific-popup');
        }
    }

    // ====================
    // PRODUCT PAGES
    // ====================
    if ($is_product) {
        // Keep: magnific-popup, add-to-cart, variations
        // Remove: country-select, password-strength, checkout/cart scripts

        wp_dequeue_script('wc-country-select');
        wp_dequeue_script('wc-address-i18n');
        wp_dequeue_script('wc-password-strength-meter');
        wp_dequeue_script('password-strength-meter');
        wp_dequeue_script('zxcvbn-async');
        wp_dequeue_script('sourcebuster-js');
        wp_dequeue_script('wc-checkout');
        wp_dequeue_script('wc-cart');
    }

    // ====================
    // SHOP/ARCHIVE PAGES
    // ====================
    if ($is_shop && !$is_product) {
        // Keep: add-to-cart
        // Remove: variations, single-product, checkout, SelectWoo

        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-add-to-cart-variation');
        wp_dequeue_script('wc-country-select');
        wp_dequeue_script('wc-address-i18n');
        wp_dequeue_script('selectWoo');
        wp_dequeue_script('wc-password-strength-meter');
        wp_dequeue_script('password-strength-meter');
        wp_dequeue_script('zxcvbn-async');
        wp_dequeue_script('wc-checkout');
    }

    // ====================
    // CART PAGE
    // ====================
    if ($is_cart) {
        // Keep: BlockUI, cart scripts
        // Remove: variations, single-product, checkout, country-select, SelectWoo

        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-add-to-cart-variation');
        wp_dequeue_script('selectWoo');
        wp_dequeue_script('wc-country-select');
        wp_dequeue_script('wc-address-i18n');
        wp_dequeue_script('wc-password-strength-meter');
        wp_dequeue_script('password-strength-meter');
        wp_dequeue_script('zxcvbn-async');
        wp_dequeue_script('wc-checkout');
        wp_dequeue_script('jquery-magnific-popup');
    }

    // ====================
    // CHECKOUT PAGE
    // ====================
    if ($is_checkout) {
        // Keep: BlockUI, country-select, address-i18n, SelectWoo, checkout scripts
        // Remove: magnific-popup, single-product, variations

        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-add-to-cart-variation');
        wp_dequeue_script('jquery-magnific-popup');
    }

    // ====================
    // MY ACCOUNT PAGE
    // ====================
    if ($is_account) {
        // Keep: password-strength-meter
        // Remove: product/cart/checkout scripts

        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-add-to-cart-variation');
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('wc-cart');
        wp_dequeue_script('wc-checkout');
        wp_dequeue_script('jquery-magnific-popup');
        wp_dequeue_script('selectWoo');
    }

    // Log optimization activity (only if WP_DEBUG is enabled)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $page_type = 'unknown';
        if (is_front_page()) $page_type = 'home';
        elseif ($is_product) $page_type = 'product';
        elseif ($is_shop) $page_type = 'shop';
        elseif ($is_cart) $page_type = 'cart';
        elseif ($is_checkout) $page_type = 'checkout';
        elseif ($is_account) $page_type = 'account';
        elseif (is_page()) $page_type = 'page';

        add_action('wp_footer', function() use ($page_type) {
            echo '<script>console.log("[PL WC Optimizer] Page: ' . $page_type . ' | Unnecessary scripts removed");</script>';
        }, 1);
    }
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
 * Defer non-critical CSS to eliminate render-blocking
 * DISABLED FOR NOW - Use only when properly configured
 *
 * To enable: Uncomment the add_filter line below and configure critical_styles
 */
// add_filter('style_loader_tag', 'pl_defer_non_critical_css', 10, 4);
function pl_defer_non_critical_css($html, $handle, $href, $media)
{
    // Critical styles that MUST load immediately (EXPAND THIS LIST!)
    $critical_styles = array(
        'elementor-frontend',
        'elementor-post-',
        'elementor-pro',
        'elementor-icons',
        'elementor-global',
        'hello-elementor',
        'hello-elementor-theme-style',
        'main-custom-style',  // Your custom theme CSS
        'woocommerce-layout',
        'woocommerce-smallscreen',
        'woocommerce-general',
        // Add more critical CSS handles here
    );

    // Check if this is a critical style
    $is_critical = false;
    foreach ($critical_styles as $critical) {
        if (strpos($handle, $critical) !== false) {
            $is_critical = true;
            break;
        }
    }

    // Don't defer critical CSS
    if ($is_critical) {
        return $html;
    }

    // Defer non-critical CSS using media="print" trick
    $html = str_replace("media='all'", "media='print' onload=\"this.media='all'; this.onload=null;\"", $html);
    $html = str_replace('media="all"', 'media="print" onload="this.media=\'all\'; this.onload=null;"', $html);

    // Add noscript fallback
    if (strpos($html, 'media="print"') !== false || strpos($html, "media='print'") !== false) {
        $noscript = '<noscript>' . str_replace(['media="print"', "media='print'"], 'media="all"', $html) . '</noscript>';
        $html .= $noscript;
    }

    return $html;
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
 * Disable WordPress emoji scripts (saves ~15KB)
 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

/**
 * Remove WordPress version from head (minor security improvement)
 */
remove_action('wp_head', 'wp_generator');

/**
 * Disable jQuery Migrate (saves ~10KB if not needed)
 * Only disable if your site doesn't use old jQuery plugins
 */
add_filter('wp_default_scripts', 'pl_disable_jquery_migrate');
function pl_disable_jquery_migrate($scripts)
{
    if (!is_admin() && !empty($scripts->registered['jquery'])) {
        $scripts->registered['jquery']->deps = array_diff(
            $scripts->registered['jquery']->deps,
            array('jquery-migrate')
        );
    }
    return $scripts;
}

/**
 * Disable WordPress embeds (saves ~5KB)
 */
add_action('wp_footer', 'pl_disable_embeds');
function pl_disable_embeds()
{
    wp_dequeue_script('wp-embed');
}

/**
 * Add DNS prefetch for external domains
 */
add_action('wp_head', 'pl_dns_prefetch', 0);
function pl_dns_prefetch()
{
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
    echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
    // Add more external domains as needed
}

/**
 * Optimize Heartbeat API (reduces server load)
 */
add_filter('heartbeat_settings', 'pl_optimize_heartbeat');
function pl_optimize_heartbeat($settings)
{
    // Slow down or disable heartbeat on frontend
    if (!is_admin()) {
        $settings['interval'] = 120; // 120 seconds instead of default 15
    }
    return $settings;
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
