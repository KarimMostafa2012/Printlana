<?php
/**
 * Plugin Name: Custom Toast Notification
 * Description: Beautiful toast notifications for WooCommerce and WordPress actions
 * Version: 1.0.0
 * Author: Printlana
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add toast notification HTML and scripts to footer
 */
add_action('wp_footer', 'pl_toast_notification_footer', 999);
function pl_toast_notification_footer()
{
    // Don't show on admin pages
    if (is_admin()) {
        return;
    }
    ?>
    <!-- Toast Notification Container -->
    <div id="pl-toast-wrap" style="position:fixed;z-index:999999;right:20px;bottom:20px;display:flex;flex-direction:column;gap:10px;max-width:360px;"></div>

    <style>
        .pl-toast {
            background: #111;
            color: #fff;
            padding: 12px 14px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
            font-size: 14px;
            line-height: 1.4;
            opacity: 0;
            transform: translateY(8px);
            transition: all .2s ease;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .pl-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .pl-toast .x {
            margin-left: auto;
            cursor: pointer;
            opacity: .8;
            flex-shrink: 0;
        }

        .pl-toast .x:hover {
            opacity: 1;
        }

        .pl-toast.success {
            background: #0f5132;
        }

        .pl-toast.error {
            background: #842029;
        }

        .pl-toast.info {
            background: #084298;
        }

        .pl-toast.warning {
            background: #664d03;
        }

        /* Mobile responsive */
        @media (max-width: 480px) {
            #pl-toast-wrap {
                right: 10px;
                bottom: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>

    <script>
        (function () {
            const wrap = document.getElementById('pl-toast-wrap');

            /**
             * Create a toast notification
             * @param {string} html - HTML content to display
             * @param {string} type - Type of toast (success, error, info, warning)
             * @param {number} timeout - Auto-dismiss timeout in ms (0 = no auto-dismiss)
             */
            function toast(html, type = 'info', timeout = 4500) {
                const t = document.createElement('div');
                t.className = 'pl-toast ' + type;
                t.innerHTML = `<div>${html}</div><div class="x" aria-label="Close">✕</div>`;
                wrap.appendChild(t);

                // Trigger animation
                requestAnimationFrame(() => t.classList.add('show'));

                // Remove toast
                const kill = () => {
                    t.classList.remove('show');
                    setTimeout(() => t.remove(), 200);
                };

                // Close button
                t.querySelector('.x').addEventListener('click', kill);

                // Auto-dismiss
                if (timeout) {
                    setTimeout(kill, timeout);
                }
            }

            /**
             * Convert WooCommerce notices to toast notifications
             */
            function grabWooNotices() {
                const container = document.querySelector('.woocommerce-notices-wrapper');
                if (!container) return;

                // Error messages - <ul class="woocommerce-error"><li>...</li></ul>
                container.querySelectorAll('.woocommerce-error li').forEach(li => {
                    toast(li.innerHTML, 'error');
                });

                // Success messages
                container.querySelectorAll('.woocommerce-message').forEach(el => {
                    toast(el.innerHTML, 'success');
                });

                // Info messages
                container.querySelectorAll('.woocommerce-info').forEach(el => {
                    toast(el.innerHTML, 'info');
                });

                // Clear original notices so they don't duplicate
                container.innerHTML = '';
            }

            // On initial page load
            document.addEventListener('DOMContentLoaded', grabWooNotices);

            // After AJAX add-to-cart / cart fragments refresh
            document.body.addEventListener('added_to_cart', grabWooNotices);
            document.body.addEventListener('wc_fragments_refreshed', grabWooNotices);

            // Checkout and cart AJAX notices
            document.body.addEventListener('updated_wc_div', grabWooNotices);
            document.body.addEventListener('updated_checkout', grabWooNotices);

            // Make toast function globally available for custom use
            window.plToast = toast;

            console.log('[PL Toast] Notification system initialized');
        })();
    </script>
    <?php
}

/**
 * Example: Add toast notification programmatically from PHP
 * Usage in your theme/plugin:
 *
 * pl_add_toast_notification('Product added to cart!', 'success');
 * pl_add_toast_notification('Please fill all required fields', 'error');
 * pl_add_toast_notification('Processing your order...', 'info');
 */
function pl_add_toast_notification($message, $type = 'info', $timeout = 4500)
{
    if (is_admin()) {
        return;
    }

    add_action('wp_footer', function () use ($message, $type, $timeout) {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.plToast === 'function') {
                    window.plToast(<?php echo json_encode($message); ?>, <?php echo json_encode($type); ?>, <?php echo intval($timeout); ?>);
                }
            });
        </script>
        <?php
    }, 1000);
}
