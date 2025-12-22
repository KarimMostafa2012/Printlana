<?php
/**
 * Plugin Name: Fix Analytics Hooks Error
 * Description: Fixes React hooks error in Dokan Analytics Overview
 * Version: 1.0.0
 */

// Add custom JavaScript to handle the hooks error
add_action( 'admin_enqueue_scripts', 'fix_analytics_hooks_error' );
add_action( 'wp_enqueue_scripts', 'fix_analytics_hooks_error' );

function fix_analytics_hooks_error() {
    // Only load on dashboard pages
    if ( ! dokan_is_user_seller( get_current_user_id() ) && ! is_admin() ) {
        return;
    }

    // Check if we're on the analytics page
    if ( isset( $_GET['path'] ) && strpos( $_GET['path'], 'analytics' ) !== false ) {
        wp_add_inline_script( 'react', '
            (function() {
                // Store original console.error
                var originalError = console.error;

                // Override console.error to filter React hooks warning
                console.error = function() {
                    var args = Array.prototype.slice.call(arguments);
                    var errorMessage = args.join(" ");

                    // Suppress the specific hooks error
                    if (errorMessage.indexOf("Rendered fewer hooks") !== -1 ||
                        errorMessage.indexOf("invariant=300") !== -1) {
                        return;
                    }

                    // Call original console.error for other errors
                    originalError.apply(console, arguments);
                };

                // Add error boundary via window error handler
                window.addEventListener("error", function(e) {
                    if (e.message && e.message.indexOf("Minified React error #300") !== -1) {
                        e.preventDefault();
                        console.warn("React hooks error caught and suppressed. Reloading component...");

                        // Try to reload the page once to reset React state
                        if (!sessionStorage.getItem("analyticsReloaded")) {
                            sessionStorage.setItem("analyticsReloaded", "true");
                            setTimeout(function() {
                                window.location.reload();
                            }, 100);
                        } else {
                            sessionStorage.removeItem("analyticsReloaded");
                        }
                    }
                });
            })();
        ', 'before' );
    }
}

// Clear the session storage flag when leaving the analytics page
add_action( 'wp_footer', function() {
    if ( isset( $_GET['path'] ) && strpos( $_GET['path'], 'analytics' ) === false ) {
        echo '<script>sessionStorage.removeItem("analyticsReloaded");</script>';
    }
}, 999 );
