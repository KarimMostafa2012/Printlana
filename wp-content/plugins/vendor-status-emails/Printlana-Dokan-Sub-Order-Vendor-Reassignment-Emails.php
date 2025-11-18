<?php
/**
 * Plugin Name: Printlana - Dokan Sub-Order Vendor Reassignment Emails
 * Description: Emails the new and old vendor when a Dokan sub-order's vendor is changed.
 * Author: Printlana
 * Version: 1.0.4
 */

if (!defined('ABSPATH'))
    exit;

if (!class_exists('Printlana_DokanVendorReassign_Emails')) {

    final class Printlana_DokanVendorReassign_Emails
    {

        /** @var array<int,int> order_id => old_vendor_id */
        private static $old_vendor = [];

        public static function init()
        {
            add_action('plugins_loaded', [__CLASS__, 'maybe_boot'], 11);
        }

        public static function maybe_boot()
        {
            if (!function_exists('wc_get_order')) {
                self::log('WooCommerce not active; plugin idle.');
                return;
            }
            if (!function_exists('dokan')) {
                self::log('Dokan not active; plugin idle.');
                return;
            }

            add_filter('update_post_metadata', [__CLASS__, 'capture_old_vendor'], 10, 5);
            add_action('updated_post_meta', [__CLASS__, 'maybe_email_after_change'], 10, 4);
        }

        /** Capture old vendor BEFORE _dokan_vendor_id changes */
        public static function capture_old_vendor($check, $object_id, $meta_key, $meta_value, $prev_value)
        {
            try {
                if ('_dokan_vendor_id' !== $meta_key) {
                    return $check;
                }

                $order_id = (int) $object_id;
                $order = wc_get_order($order_id);
                if (!$order || !is_a($order, 'WC_Order')) {
                    return $check;
                }

                // Sub-orders only
                if ((int) $order->get_parent_id() <= 0) {
                    return $check;
                }

                $current_vendor = (int) get_metadata('post', $order_id, '_dokan_vendor_id', true);
                $new_vendor = (int) $meta_value;

                if ($current_vendor && $new_vendor && $current_vendor !== $new_vendor) {
                    self::$old_vendor[$order_id] = $current_vendor;
                    self::log("Captured old vendor for order {$order_id}: {$current_vendor} -> {$new_vendor}");
                }

                return $check; // never short-circuit core
            } catch (Throwable $e) {
                self::log('capture_old_vendor error: ' . $e->getMessage());
                return $check;
            }
        }

        /** AFTER _dokan_vendor_id updated, notify vendors */
        public static function maybe_email_after_change($meta_id, $object_id, $meta_key, $meta_value)
        {
            try {
                if ('_dokan_vendor_id' !== $meta_key) {
                    return;
                }

                $order_id = (int) $object_id;
                if (empty(self::$old_vendor[$order_id])) {
                    return;
                }

                $old_vendor_id = (int) self::$old_vendor[$order_id];
                unset(self::$old_vendor[$order_id]);

                $order = wc_get_order($order_id);
                if (!$order || !is_a($order, 'WC_Order')) {
                    self::log("Order {$order_id} not a WC_Order.");
                    return;
                }

                if ((int) $order->get_parent_id() <= 0) {
                    return; // not a sub-order
                }

                $new_vendor_id = (int) get_metadata('post', $order_id, '_dokan_vendor_id', true);
                if ($new_vendor_id <= 0 || $new_vendor_id === $old_vendor_id) {
                    return;
                }

                // Compose item info
                list($product_label, $product_url) = self::primary_product_for_suborder($order);
                $order_number = $order->get_order_number();
                error_log('Debug Data: ' . print_r($order, true));
                // Resolve emails
                $new_email = self::get_vendor_email($new_vendor_id);
                $old_email = self::get_vendor_email($old_vendor_id);

                $headers = ['Content-Type: text/html; charset=UTF-8'];

                $subject_new = sprintf('You received sub-order #%s', $order_number);
                $items = $order->get_items();
                $item = reset($items); // get the first item

                $product_name = $item->get_name();
                $qty = $item->get_quantity();

                $body_new = sprintf(
                    '<p>Hello,</p>
                 <p>You have been assigned <strong>order #%s</strong>.</p>
                 <p><strong>Product:</strong> %s%s<br/>
                    <strong>Quantity:</strong> %d<br/>
                    <strong>Order Total:</strong> %s
                 </p>
                 <p><a href="%s">View sub-order</a></p>',
                    esc_html($order_number),
                    esc_html($product_name),
                    $product_url ? ' — <a href="' . esc_url($product_url) . '">View product</a>' : '',
                    (int) $qty,
                    wp_kses_post($order->get_formatted_order_total()),
                    esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit'))
                );

                $subject_old = sprintf('Sub-order #%s reassigned — %s', $order_number, $product_label);
                $body_old = sprintf(
                    '<p>Hello,</p>
                 <p>We reassigned <strong>sub-order #%s — %s</strong> to another vendor.</p>
                 <p>Thank you for your understanding.</p>',
                    esc_html($order_number),
                    esc_html($product_label)
                );

                if ($new_email) {
                    wc_mail($new_email, $subject_new, $body_new, $headers);
                } else {
                    self::log("No email for NEW vendor {$new_vendor_id}");
                }

                if ($old_email) {
                    wc_mail($old_email, $subject_old, $body_old, $headers);
                } else {
                    self::log("No email for OLD vendor {$old_vendor_id}");
                }

            } catch (Throwable $e) {
                self::log('maybe_email_after_change error: ' . $e->getMessage());
            }
        }

        /** Vendor email (store email preferred; fallback user email) */
        private static function get_vendor_email($vendor_id): string
        {
            try {
                if (function_exists('dokan')) {
                    $vendor = dokan()->vendor->get((int) $vendor_id);
                    if ($vendor) {
                        $email = $vendor->get_email();
                        if ($email)
                            return sanitize_email($email);
                    }
                }
                $u = get_user_by('id', (int) $vendor_id);
                if ($u && !empty($u->user_email)) {
                    return sanitize_email($u->user_email);
                }
            } catch (Throwable $e) {
                self::log('get_vendor_email error: ' . $e->getMessage());
            }
            return '';
        }

        /** First line item label/url/qty with strong Dokan/HPOS fallbacks */
        private static function primary_product_for_suborder(WC_Order $order): array
        {
            try {
                foreach ($order->get_items('line_item') as $item_id => $item) {

                    // ---- 1) Pull snapshot basics from the order item ----
                    $name = $item->get_name();
                    $qty = (int) $item->get_quantity();

                    // Prefer explicit getters first
                    $product_id = (int) $item->get_product_id();
                    $variation_id = (int) $item->get_variation_id();

                    // If those are zero, try stored meta (common when product got removed or custom importers)
                    if (!$product_id) {
                        $product_id = (int) $item->get_meta('_product_id', true);
                    }
                    if (!$variation_id) {
                        $variation_id = (int) $item->get_meta('_variation_id', true);
                    }

                    // ---- 2) Rebuild product object even if $item->get_product() is null ----
                    $product = $item->get_product();
                    if (!$product) {
                        $load_id = $variation_id ?: $product_id;
                        if ($load_id) {
                            $product = wc_get_product($load_id);
                            // If variation missing but parent exists, try the parent
                            if (!$product && $variation_id && $product_id) {
                                $product = wc_get_product($product_id);
                            }
                        }
                    }

                    // ---- 3) SKU: snapshot first, then live product ----
                    $sku = (string) $item->get_meta('_sku', true);
                    if ($sku === '' && $product && method_exists($product, 'get_sku')) {
                        $sku = (string) $product->get_sku();
                    }

                    // ---- 4) Variation text: ALWAYS from order item’s saved attributes ----
                    $va = $item->get_variation_attributes(); // e.g. ['attribute_pa_size' => 'L']
                    $variation_text = !empty($va) ? wc_get_formatted_variation($va, true) : '';

                    // ---- 5) Build a product URL with graceful fallback ----
                    $url = '';
                    // try variation permalink first (when it exists)
                    if ($variation_id) {
                        $url = get_permalink($variation_id);
                    }
                    // else or if empty, try parent
                    if (!$url && $product_id) {
                        $url = get_permalink($product_id);
                    }
                    // final fallback to order edit screen so the link is never empty
                    if (!$url) {
                        $url = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
                    }

                    // ---- 6) Compose a robust label ----
                    $parts = array_filter([
                        $name,
                        $variation_text ?: null,
                        $sku ? 'SKU: ' . $sku : null,
                    ]);
                    $label = implode(' — ', $parts);
                    if ($qty > 0) {
                        $label .= ' × ' . $qty;
                    }
                    if ($label === '') {
                        $label = __('(no product)', 'printlana');
                    }

                    return [$label, $url, $qty];
                }
            } catch (Throwable $e) {
                self::log('primary_product_for_suborder error: ' . $e->getMessage());
            }

            return [__('(no product)', 'printlana'), admin_url('post.php?post=' . $order->get_id() . '&action=edit'), 0];
        }


        /** Logger */
        private static function log(string $msg): void
        {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[printlana-dokan] ' . $msg);
            }
        }
    }

    Printlana_DokanVendorReassign_Emails::init();

} // class_exists guard
