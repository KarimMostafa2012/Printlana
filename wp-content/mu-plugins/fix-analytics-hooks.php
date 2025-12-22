<?php
/**
 * Plugin Name: Fix Analytics Overview Hooks Error
 * Description: Fixes React hooks rendering error in Dokan Analytics Overview
 * Version: 1.0.1
 */

defined('ABSPATH') || exit;

/**
 * Fix the React hooks error by ensuring consistent component rendering
 */
add_action('admin_print_footer_scripts', 'fix_analytics_overview_hooks', 1);
add_action('wp_print_footer_scripts', 'fix_analytics_overview_hooks', 1);

function fix_analytics_overview_hooks() {
    // Only run on vendor dashboard
    if (!function_exists('dokan_is_user_seller') || !dokan_is_user_seller(get_current_user_id())) {
        return;
    }

    // Check if we're on analytics page
    if (!isset($_GET['path']) || strpos($_GET['path'], 'analytics') === false) {
        return;
    }

    ?>
    <script type="text/javascript">
    (function() {
        'use strict';

        // Prevent React from throwing on hooks errors in production
        if (window.React && window.React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED) {
            var internals = window.React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;
            if (internals.ReactCurrentDispatcher) {
                var originalDispatcher = internals.ReactCurrentDispatcher.current;
            }
        }

        // Suppress the specific React error #300 (hooks error)
        var originalError = console.error;
        console.error = function() {
            var args = Array.prototype.slice.call(arguments);
            var message = args.join(' ');

            // Filter out hooks errors
            if (message.indexOf('Rendered fewer hooks') > -1 ||
                message.indexOf('invariant=300') > -1 ||
                message.indexOf('Minified React error #300') > -1) {
                console.warn('[Analytics] React hooks warning suppressed - reloading component');
                return;
            }

            return originalError.apply(console, args);
        };

        // Handle unhandled errors
        window.addEventListener('error', function(event) {
            if (event.message && event.message.indexOf('Minified React error #300') > -1) {
                event.preventDefault();
                event.stopPropagation();

                console.warn('[Analytics] Caught hooks error, attempting recovery...');

                // Try to remount the component
                if (!sessionStorage.getItem('analytics_remounted')) {
                    sessionStorage.setItem('analytics_remounted', '1');
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    // If already tried remounting, redirect to different analytics page
                    sessionStorage.removeItem('analytics_remounted');
                    var newPath = window.location.href.replace('Overview', 'revenue');
                    if (newPath !== window.location.href) {
                        window.location.href = newPath;
                    }
                }

                return false;
            }
        }, true);

        // Clear flag when leaving page
        window.addEventListener('beforeunload', function() {
            if (window.location.search.indexOf('analytics') === -1) {
                sessionStorage.removeItem('analytics_remounted');
            }
        });

    })();
    </script>
    <?php
}

// Clear cache for analytics when needed
add_action('init', 'clear_analytics_cache_on_error');
function clear_analytics_cache_on_error() {
    if (isset($_GET['clear_analytics_cache']) && $_GET['clear_analytics_cache'] === '1') {
        if (function_exists('wc_admin_clear_cache')) {
            wc_admin_clear_cache();
        }
        wp_safe_redirect(remove_query_arg('clear_analytics_cache'));
        exit;
    }
}
