<?php
/**
 * Plugin Name: Printlana - Dokan Sub-Order Vendor Reassignment Emails
 * Description: Emails the new and old vendor when a Dokan sub-order's vendor is changed.
 * Author: Printlana
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Status-change email for Dokan sub-orders
 * Type #4: order status changed (Customer + Admin + Vendor)
 */

if (!function_exists('pl_get_email_logo_url')) {
    function pl_get_email_logo_url(): string
    {
        $logo_url = '';
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $image = wp_get_attachment_image_src($logo_id, 'full');
            if (!empty($image[0])) {
                $logo_url = esc_url($image[0]);
            }
        }
        if (!$logo_url) {
            $logo_url = esc_url(home_url('/'));
        }
        return $logo_url;
    }
}

if (!function_exists('pl_get_support_email')) {
    function pl_get_support_email(): string
    {
        $email = get_option('admin_email');
        if (!$email) {
            $host = wp_parse_url(home_url(), PHP_URL_HOST);
            $email = 'support@' . $host;
        }
        return sanitize_email($email);
    }
}

if (!function_exists('pl_generate_order_items_table')) {
    /**
     * Simple products table for emails (email-safe layout)
     */
    function pl_generate_order_items_table(WC_Order $order): string
    {
        $rows = '';

        foreach ($order->get_items('line_item') as $item) {
            $name = esc_html($item->get_name());
            $qty = (int) $item->get_quantity();
            $total = $item->get_total();
            $total = wc_price($total);

            $rows .= '<tr>'
                . '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">' . $name . '</td>'
                . '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; text-align:center;">' . $qty . '</td>'
                . '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; text-align:right;">' . $total . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="3" style="padding:8px 6px; font-size:13px; color:#6b7280;">'
                . esc_html__('No items found in this order.', 'printlana')
                . '</td></tr>';
        }

        $table = '
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <thead>
                    <tr>
                        <th align="left" style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">' . esc_html__('Product', 'printlana') . '</th>
                        <th align="center" style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">' . esc_html__('Qty', 'printlana') . '</th>
                        <th align="right" style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">' . esc_html__('Total', 'printlana') . '</th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>';

        return $table;
    }
}

if (!function_exists('pl_get_vendor_name')) {
    function pl_get_vendor_name(int $vendor_id): string
    {
        try {
            if (function_exists('dokan')) {
                $vendor = dokan()->vendor->get($vendor_id);
                if ($vendor && method_exists($vendor, 'get_name')) {
                    $name = $vendor->get_name();
                    if (!empty($name)) {
                        return $name;
                    }
                }
            }
            $u = get_user_by('id', $vendor_id);
            if ($u && !empty($u->display_name)) {
                return $u->display_name;
            }
        } catch (Throwable $e) {
            // swallow for email
        }
        return __('Vendor', 'printlana');
    }
}

if (!function_exists('pl_build_status_email_html')) {
    /**
     * Build the full HTML email for order status change.
     *
     * @param array $d {
     *   @type string $recipient_type 'customer'|'admin'|'vendor'
     *   @type string $logo_url
     *   @type string $site_name
     *   @type string $order_number
     *   @type string $product_label
     *   @type string $status_old_label
     *   @type string $status_new_label
     *   @type string $status_change_date
     *   @type string $order_total_html
     *   @type string $payment_method
     *   @type string $order_items_html
     *   @type string $order_link
     *   @type string $customer_name
     *   @type string $support_email
     *   @type string $signature_name
     *   @type string $signature_title
     *   @type string $vendor_name
     * }
     */
    function pl_build_status_email_html(array $d): string
    {
        $recipient_type = $d['recipient_type']; // customer|admin|vendor
        $logo_url = esc_url($d['logo_url']);
        $site_name = esc_html($d['site_name']);
        $order_number = esc_html($d['order_number']);
        $product_label = esc_html($d['product_label']);
        $status_old_label = esc_html($d['status_old_label']);
        $status_new_label = esc_html($d['status_new_label']);
        $status_change_date = esc_html($d['status_change_date']);
        $order_total_html = wp_kses_post($d['order_total_html']);
        $payment_method = esc_html($d['payment_method']);
        $order_items_html = $d['order_items_html']; // already HTML table
        $order_link = esc_url($d['order_link']);
        $customer_name = esc_html($d['customer_name']);
        $support_email = esc_html($d['support_email']);
        $signature_name = esc_html($d['signature_name']);
        $signature_title = esc_html($d['signature_title']);
        $vendor_name = esc_html($d['vendor_name']);

        $support_email_link = esc_attr($support_email);

        // Intro text per recipient type
        if ($recipient_type === 'admin') {
            $intro = sprintf(
                /* translators: 1: order number */
                esc_html__('A customer order #%s has changed status. Please review the details below.', 'printlana'),
                $order_number
            );
        } elseif ($recipient_type === 'vendor') {
            $intro = sprintf(
                esc_html__('Dear %1$s, the status of the sub-order #%2$s assigned to you has been updated.', 'printlana'),
                $vendor_name,
                $order_number
            );
        } else {
            // customer
            $intro = sprintf(
                esc_html__('Dear %1$s, the status of your order #%2$s has been updated.', 'printlana'),
                $customer_name,
                $order_number
            );
        }

        $html_intro = '<p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">'
            . $intro
            . '</p>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Status Updated</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f5f7fb; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f7fb; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td align="center" style="background-color:#0044F1; padding:20px;">
                            <img src="{$logo_url}" alt="{$site_name} Logo" style="max-width:160px; height:auto; display:block;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 24px 8px 24px;">
                            <h1 style="margin:0; font-size:20px; color:#111827; text-align:left;">
                                Sub-order #{$order_number} status has changed.
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 12px 24px;">
                            {$html_intro}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb; border-radius:6px; width:100%;">
                                <tr>
                                    <td style="padding:12px 16px; background-color:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                        <strong style="font-size:14px; color:#111827;">Status Update</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px;">
                                        <p style="margin:0; font-size:13px; color:#111827;">
                                            Product: <strong>{$product_label}</strong><br>
                                            Previous Status: <strong>{$status_old_label}</strong><br>
                                            New Status: <strong>{$status_new_label}</strong><br>
                                            Updated On: <strong>{$status_change_date}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb; border-radius:6px; width:100%;">
                                <tr>
                                    <td style="padding:12px 16px; background-color:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                        <strong style="font-size:14px; color:#111827;">Order Summary</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px;">
                                        {$order_items_html}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px; border-top:1px solid #e5e7eb;">
                                        <p style="margin:0; font-size:13px; color:#4b5563;">
                                            Total Amount: <strong>{$order_total_html}</strong><br>
                                            Payment Method: <strong>{$payment_method}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:0 24px 20px 24px;">
                            <a href="{$order_link}" style="display:inline-block; padding:10px 20px; font-size:14px; font-weight:bold; color:#ffffff; background-color:#0044F1; text-decoration:none; border-radius:999px;">
                                View Order Details
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#6b7280;">
                                If you have any questions regarding this change, please contact us at
                                <a href="mailto:{$support_email_link}" style="color:#0044F1; text-decoration:none;">{$support_email}</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 24px 24px;">
                            <p style="margin:0; font-size:13px; color:#111827;">
                                Kind regards,<br>
                                <strong>{$signature_name}</strong><br>
                                <span style="color:#6b7280;">{$signature_title}</span><br>
                                <span style="color:#6b7280;">{$site_name}</span>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color:#f9fafb; padding:14px;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                This message was generated automatically. Please do not share your order details with untrusted parties.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        return $html;
    }
}

/**
 * MAIN HOOK
 */
add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status, $order) {

    if (!$order instanceof WC_Order) {
        return;
    }

    // 🔒 Only act on Dokan SUB-ORDERS (child orders)
    $parent_id = (int) $order->get_parent_id();
    if ($parent_id <= 0) {
        return; // ignore parent orders and standalone orders
    }

    // Who is acting now?
    $actor_id = get_current_user_id();
    if (!$actor_id) {
        return; // ignore programmatic/cron/webhook changes
    }

    $actor_user = get_user_by('id', $actor_id);
    if (!$actor_user) {
        return;
    }

    // Helper: is vendor?
    $is_vendor = function ($user) {
        if (function_exists('dokan_is_user_seller')) {
            return dokan_is_user_seller($user->ID);
        }
        return in_array('seller', (array) $user->roles, true) || in_array('vendor', (array) $user->roles, true);
    };

    $actor_is_vendor = $is_vendor($actor_user);
    $actor_is_admin = user_can($actor_user, 'manage_woocommerce') || user_can($actor_user, 'administrator');

    // Common context
    $order_number = $order->get_order_number();
    $order_link = admin_url('post.php?post=' . $order_id . '&action=edit');
    $customer_email = $order->get_billing_email();
    $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

    // 🔍 Primary product label/url for this sub-order (your existing helper)
    [$product_label, $product_url] = pl_suborder_primary_product($order);

    // 🛍️ Resolve the single vendor for this sub-order
    $vendor_emails = [];
    $vendor_id = (int) $order->get_meta('_dokan_vendor_id'); // HPOS-safe

    if ($vendor_id <= 0) {
        // Fallback: derive from first line item product author
        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            if ($product) {
                $vendor_id = (int) get_post_field('post_author', $product->get_id());
                if ($vendor_id > 0) {
                    break;
                }
            }
        }
    }

    if ($vendor_id > 0) {
        $email = null;
        if (function_exists('dokan')) {
            $vendor = dokan()->vendor->get($vendor_id);
            if ($vendor) {
                $email = $vendor->get_email(); // store email (falls back to user email)
            }
        }
        if (!$email) {
            $u = get_user_by('id', $vendor_id);
            if ($u && !empty($u->user_email)) {
                $email = $u->user_email;
            }
        }
        if ($email) {
            $vendor_emails[] = sanitize_email($email);
        }
    }

    // Admin recipients (from WooCommerce setting; fallback to site admin)
    $admin_recipients = [];
    $wc_new_order_settings = get_option('woocommerce_new_order_settings');
    if (is_array($wc_new_order_settings) && !empty($wc_new_order_settings['recipient'])) {
        $admin_recipients = array_map('trim', explode(',', $wc_new_order_settings['recipient']));
    }
    if (empty($admin_recipients)) {
        $admin_recipients[] = get_option('admin_email');
    }
    $admin_recipients = array_filter(array_unique(array_map('sanitize_email', $admin_recipients)));

    // 🧠 Friendly status labels
    if (function_exists('wc_get_order_status_name')) {
        $status_old_label = wc_get_order_status_name('wc-' . $old_status);
        $status_new_label = wc_get_order_status_name('wc-' . $new_status);
    } else {
        $status_old_label = $old_status;
        $status_new_label = $new_status;
    }

    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    $logo_url = pl_get_email_logo_url();
    $support_email = pl_get_support_email();
    $signature_name = $site_name . ' Team';
    $signature_title = __('Customer Care', 'printlana');
    $order_items_html = pl_generate_order_items_table($order);
    $order_total_html = $order->get_formatted_order_total();
    $payment_method = $order->get_payment_method_title();
    $status_change_date = date_i18n(
        get_option('date_format') . ' ' . get_option('time_format'),
        current_time('timestamp')
    );
    $vendor_name = $vendor_id > 0 ? pl_get_vendor_name($vendor_id) : __('Vendor', 'printlana');

    // Common subject
    $subject = sprintf(
        /* translators: 1: order number, 2: product label, 3: old status, 4: new status */
        __('Sub-order #%1$s (%2$s) status changed: %3$s → %4$s', 'printlana'),
        $order_number,
        $product_label,
        $status_old_label,
        $status_new_label
    );

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Build HTML for each recipient type ONCE
    $customer_html = pl_build_status_email_html([
        'recipient_type' => 'customer',
        'logo_url' => $logo_url,
        'site_name' => $site_name,
        'order_number' => $order_number,
        'product_label' => $product_label,
        'status_old_label' => $status_old_label,
        'status_new_label' => $status_new_label,
        'status_change_date' => $status_change_date,
        'order_total_html' => $order_total_html,
        'payment_method' => $payment_method,
        'order_items_html' => $order_items_html,
        'order_link' => $order_link,
        'customer_name' => $customer_name,
        'support_email' => $support_email,
        'signature_name' => $signature_name,
        'signature_title' => $signature_title,
        'vendor_name' => $vendor_name,
    ]);

    $admin_html = pl_build_status_email_html([
        'recipient_type' => 'admin',
        'logo_url' => $logo_url,
        'site_name' => $site_name,
        'order_number' => $order_number,
        'product_label' => $product_label,
        'status_old_label' => $status_old_label,
        'status_new_label' => $status_new_label,
        'status_change_date' => $status_change_date,
        'order_total_html' => $order_total_html,
        'payment_method' => $payment_method,
        'order_items_html' => $order_items_html,
        'order_link' => $order_link,
        'customer_name' => $customer_name,
        'support_email' => $support_email,
        'signature_name' => $signature_name,
        'signature_title' => $signature_title,
        'vendor_name' => $vendor_name,
    ]);

    $vendor_html = pl_build_status_email_html([
        'recipient_type' => 'vendor',
        'logo_url' => $logo_url,
        'site_name' => $site_name,
        'order_number' => $order_number,
        'product_label' => $product_label,
        'status_old_label' => $status_old_label,
        'status_new_label' => $status_new_label,
        'status_change_date' => $status_change_date,
        'order_total_html' => $order_total_html,
        'payment_method' => $payment_method,
        'order_items_html' => $order_items_html,
        'order_link' => $order_link,
        'customer_name' => $customer_name,
        'support_email' => $support_email,
        'signature_name' => $signature_name,
        'signature_title' => $signature_title,
        'vendor_name' => $vendor_name,
    ]);

    // 🔀 Routing
    if ($actor_is_vendor) {
        // Vendor changed → Customer + Admin
        if ($customer_email) {
            wc_mail($customer_email, $subject, $customer_html, $headers);
        }
        foreach ($admin_recipients as $admin_email) {
            wc_mail($admin_email, $subject, $admin_html, $headers);
        }

    } elseif ($actor_is_admin) {
        // Admin changed → Customer + Vendor
        if ($customer_email) {
            wc_mail($customer_email, $subject, $customer_html, $headers);
        }
        foreach ($vendor_emails as $vendor_email) {
            wc_mail($vendor_email, $subject, $vendor_html, $headers);
        }
    }

}, 10, 4);


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

        /** AFTER _dokan_vendor_id updated, notify vendors (New vendor = email type #2, Old vendor = email type #3) */
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

                // Only for sub-orders
                if ((int) $order->get_parent_id() <= 0) {
                    return;
                }

                $new_vendor_id = (int) get_metadata('post', $order_id, '_dokan_vendor_id', true);
                if ($new_vendor_id <= 0 || $new_vendor_id === $old_vendor_id) {
                    return;
                }

                // Compose item info (kept for subject / old body text if needed)
                list($product_label) = self::primary_product_for_suborder($order);
                $order_number = $order->get_order_number();

                // Debug
                self::log('Debug Data (order on vendor change): ' . print_r($order, true));

                // Resolve vendor emails
                $new_email = self::get_vendor_email($new_vendor_id);
                $old_email = self::get_vendor_email($old_vendor_id);

                $headers = ['Content-Type: text/html; charset=UTF-8'];

                // General shared vars for HTML templates
                $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
                $logo_url = self::get_logo_url();
                $support_email = self::get_support_email();
                $signature_name = $site_name . ' Team';
                $signature_title = __('Vendor Relations', 'printlana');

                // Vendor names
                $new_vendor_name = self::get_vendor_name($new_vendor_id);
                $old_vendor_name = self::get_vendor_name($old_vendor_id);

                // Order data
                $order_date = $order->get_date_created()
                    ? wc_format_datetime($order->get_date_created())
                    : '';
                $order_total = $order->get_formatted_order_total();

                // Customer info (for new vendor email)
                $cust_name = $order->get_formatted_billing_full_name();
                $cust_phone = $order->get_billing_phone();
                $cust_email = $order->get_billing_email();

                // Shipping formatted address (HTML)
                $shipping_address = $order->get_formatted_shipping_address();
                if (!$shipping_address) {
                    $shipping_address = $order->get_formatted_billing_address();
                }

                $order_items_table = self::generate_order_items_table($order);

                // ------------- NEW VENDOR EMAIL (Type #2: Assigned) -------------
                if ($new_email) {
                    $subject_new = sprintf(
                        /* translators: 1: order number */
                        __('You received sub-order #%s', 'printlana'),
                        $order_number
                    );

                    $body_new = self::build_new_vendor_html_email(
                        [
                            'logo_url' => $logo_url,
                            'site_name' => $site_name,
                            'vendor_name' => $new_vendor_name,
                            'order_number' => $order_number,
                            'order_date' => $order_date,
                            'order_total' => $order_total,
                            'order_items_html' => $order_items_table,
                            'shipping_address' => $shipping_address,
                            'customer_name' => $cust_name,
                            'customer_phone' => $cust_phone,
                            'customer_email' => $cust_email,
                            'support_email' => $support_email,
                            'signature_name' => $signature_name,
                            'signature_title' => $signature_title,
                        ]
                    );

                    wc_mail($new_email, $subject_new, $body_new, $headers);
                    self::log("Sent NEW vendor assignment email for order {$order_number} to vendor {$new_vendor_id} ({$new_email})");
                } else {
                    self::log("No email for NEW vendor {$new_vendor_id}");
                }

                // ------------- OLD VENDOR EMAIL (Type #3: Removed) -------------
                if ($old_email) {
                    $subject_old = sprintf(
                        /* translators: 1: order number, 2: product label */
                        __('Sub-order #%1$s reassigned — %2$s', 'printlana'),
                        $order_number,
                        $product_label
                    );

                    $body_old = self::build_old_vendor_html_email(
                        [
                            'logo_url' => $logo_url,
                            'site_name' => $site_name,
                            'vendor_name' => $old_vendor_name,
                            'order_number' => $order_number,
                            'product_label' => $product_label,
                            'support_email' => $support_email,
                            'signature_name' => $signature_name,
                            'signature_title' => $signature_title,
                        ]
                    );

                    wc_mail($old_email, $subject_old, $body_old, $headers);
                    self::log("Sent OLD vendor unassign email for order {$order_number} to vendor {$old_vendor_id} ({$old_email})");
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
                        if ($email) {
                            return sanitize_email($email);
                        }
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

        /** Vendor name for greeting */
        private static function get_vendor_name($vendor_id): string
        {
            try {
                if (function_exists('dokan')) {
                    $vendor = dokan()->vendor->get((int) $vendor_id);
                    if ($vendor && method_exists($vendor, 'get_name')) {
                        $name = $vendor->get_name();
                        if (!empty($name)) {
                            return $name;
                        }
                    }
                }
                $u = get_user_by('id', (int) $vendor_id);
                if ($u && !empty($u->display_name)) {
                    return $u->display_name;
                }
            } catch (Throwable $e) {
                self::log('get_vendor_name error: ' . $e->getMessage());
            }
            return __('Vendor', 'printlana');
        }

        /** First line item label/url/qty with strong Dokan/HPOS fallbacks */
        private static function primary_product_for_suborder(WC_Order $order): array
        {
            try {
                foreach ($order->get_items('line_item') as $item_id => $item) {

                    // 1) Pull snapshot basics
                    $name = $item->get_name();
                    $qty = (int) $item->get_quantity();

                    // Prefer explicit getters first
                    $product_id = (int) $item->get_product_id();
                    $variation_id = (int) $item->get_variation_id();

                    // Meta fallbacks
                    if (!$product_id) {
                        $product_id = (int) $item->get_meta('_product_id', true);
                    }
                    if (!$variation_id) {
                        $variation_id = (int) $item->get_meta('_variation_id', true);
                    }

                    // 2) Rebuild product
                    $product = $item->get_product();
                    if (!$product) {
                        $load_id = $variation_id ?: $product_id;
                        if ($load_id) {
                            $product = wc_get_product($load_id);
                            if (!$product && $variation_id && $product_id) {
                                $product = wc_get_product($product_id);
                            }
                        }
                    }

                    // 3) SKU
                    $sku = (string) $item->get_meta('_sku', true);
                    if ($sku === '' && $product && method_exists($product, 'get_sku')) {
                        $sku = (string) $product->get_sku();
                    }

                    // 4) Variation text
                    $va = $item->get_variation_attributes();
                    $variation_text = !empty($va) ? wc_get_formatted_variation($va, true) : '';

                    // 5) URL
                    $url = '';
                    if ($variation_id) {
                        $url = get_permalink($variation_id);
                    }
                    if (!$url && $product_id) {
                        $url = get_permalink($product_id);
                    }
                    if (!$url) {
                        $url = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
                    }

                    // 6) Label
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

            return [
                __('(no product)', 'printlana'),
                admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
                0,
            ];
        }

        /** Build responsive-ish order items table HTML (simple, email-safe) */
        private static function generate_order_items_table(WC_Order $order): string
        {
            $rows = '';

            foreach ($order->get_items('line_item') as $item) {
                $name = esc_html($item->get_name());
                $qty = (int) $item->get_quantity();
                $total = $item->get_total();
                $total = wc_price($total);

                $rows .= '<tr>';
                $rows .= '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">' . $name . '</td>';
                $rows .= '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; text-align:center;">' . $qty . '</td>';
                $rows .= '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; text-align:right;">' . $total . '</td>';
                $rows .= '</tr>';
            }

            if ($rows === '') {
                $rows = '<tr><td colspan="3" style="padding:8px 6px; font-size:13px; color:#6b7280;">' .
                    esc_html__('No items found in this order.', 'printlana') .
                    '</td></tr>';
            }

            $table = '
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <thead>
                        <tr>
                            <th align="left" style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">' . esc_html__('Product', 'printlana') . '</th>
                            <th align="center" style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">' . esc_html__('Qty', 'printlana') . '</th>
                            <th align="right" style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">' . esc_html__('Total', 'printlana') . '</th>
                        </tr>
                    </thead>
                    <tbody>' . $rows . '</tbody>
                </table>';

            return $table;
        }

        /** Get logo URL from custom logo or fallback to site URL */
        private static function get_logo_url(): string
        {
            $logo_url = '';
            try {
                $logo_id = get_theme_mod('custom_logo');
                if ($logo_id) {
                    $image = wp_get_attachment_image_src($logo_id, 'full');
                    if (!empty($image[0])) {
                        $logo_url = esc_url($image[0]);
                    }
                }
            } catch (Throwable $e) {
                self::log('get_logo_url error: ' . $e->getMessage());
            }

            if (!$logo_url) {
                $logo_url = esc_url(home_url('/'));
            }

            return $logo_url;
        }

        /** Support email (fallback to admin_email) */
        private static function get_support_email(): string
        {
            $email = get_option('admin_email');
            if (!$email) {
                $email = 'support@' . wp_parse_url(home_url(), PHP_URL_HOST);
            }
            return sanitize_email($email);
        }

        /** NEW VENDOR full HTML email (Type #2) */
        private static function build_new_vendor_html_email(array $d): string
        {
            $logo_url = esc_url($d['logo_url']);
            $site_name = esc_html($d['site_name']);
            $vendor_name = esc_html($d['vendor_name']);
            $order_number = esc_html($d['order_number']);
            $order_date = esc_html($d['order_date']);
            $order_total = wp_kses_post($d['order_total']); // already formatted
            $items_html = $d['order_items_html']; // table HTML
            $shipping_address = wp_kses_post($d['shipping_address']);
            $customer_name = esc_html($d['customer_name']);
            $customer_phone = esc_html($d['customer_phone']);
            $customer_email = esc_html($d['customer_email']);
            $support_email = esc_html($d['support_email']);
            $signature_name = esc_html($d['signature_name']);
            $signature_title = esc_html($d['signature_title']);

            $support_email_link = esc_attr($support_email);

            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order Assigned</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f5f7fb; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f7fb; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td align="center" style="background-color:#111827; padding:20px;">
                            <img src="{$logo_url}" alt="{$site_name} Logo" style="max-width:160px; height:auto; display:block;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 24px 8px 24px;">
                            <h1 style="margin:0; font-size:20px; color:#111827; text-align:left;">
                                Congratulations, {$vendor_name}!
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 12px 24px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">
                                You have been assigned a new sub-order <strong>#{$order_number}</strong> on <strong>{$site_name}</strong>.
                                Please review the details below and start processing it in accordance with our service standards.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb; border-radius:6px;">
                                <tr>
                                    <td style="background-color:#f9fafb; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
                                        <strong style="font-size:14px; color:#111827;">Order Details</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px;">
                                        {$items_html}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px; border-top:1px solid #e5e7eb;">
                                        <p style="margin:0; font-size:13px; color:#4b5563;">
                                            Order Number: <strong>#{$order_number}</strong><br>
                                            Order Date: <strong>{$order_date}</strong><br>
                                            Total Assigned to You: <strong>{$order_total}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <div style="border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px;">
                                <p style="margin:0 0 6px 0; font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">
                                    Customer Information
                                </p>
                                <p style="margin:0; font-size:13px; color:#111827;">
                                    {$customer_name}<br>
                                    {$shipping_address}<br>
                                    Phone: {$customer_phone}<br>
                                    Email: {$customer_email}
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#6b7280;">
                                Please confirm receipt and start processing the order as soon as possible.
                                If you are unable to fulfill this order or need clarification, contact us at
                                <a href="mailto:{$support_email_link}" style="color:#0044F1; text-decoration:none;">{$support_email}</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 24px 24px;">
                            <p style="margin:0; font-size:13px; color:#111827;">
                                Sincerely,<br>
                                <strong>{$signature_name}</strong><br>
                                <span style="color:#6b7280;">{$signature_title}</span><br>
                                <span style="color:#6b7280;">{$site_name} – Vendor Relations</span>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color:#f9fafb; padding:14px;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                This email was sent to you because you are registered as a vendor on {$site_name}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

            return $html;
        }

        /** OLD VENDOR full HTML email (Type #3) */
        private static function build_old_vendor_html_email(array $d): string
        {
            $logo_url = esc_url($d['logo_url']);
            $site_name = esc_html($d['site_name']);
            $vendor_name = esc_html($d['vendor_name']);
            $order_number = esc_html($d['order_number']);
            $product_label = esc_html($d['product_label']);
            $support_email = esc_html($d['support_email']);
            $signature_name = esc_html($d['signature_name']);
            $signature_title = esc_html($d['signature_title']);

            $support_email_link = esc_attr($support_email);

            $unassigned_date = esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')));

            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Unassigned Notice</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f5f7fb; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f7fb; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td align="center" style="background-color:#b91c1c; padding:20px;">
                            <img src="{$logo_url}" alt="{$site_name} Logo" style="max-width:160px; height:auto; display:block;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 24px 8px 24px;">
                            <h1 style="margin:0; font-size:20px; color:#111827; text-align:left;">
                                Order #{$order_number} has been unassigned.
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 12px 24px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">
                                Dear {$vendor_name},<br><br>
                                We would like to inform you that sub-order <strong>#{$order_number}</strong> ({$product_label}) is no longer assigned to your account.
                                This decision has been taken by our operations team.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb; border-radius:6px;">
                                <tr>
                                    <td style="background-color:#f9fafb; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
                                        <strong style="font-size:14px; color:#111827;">Order Snapshot</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px;">
                                        <p style="margin:0; font-size:13px; color:#111827;">
                                            Order: <strong>#{$order_number}</strong><br>
                                            Items: <strong>{$product_label}</strong><br>
                                            Unassigned On: <strong>{$unassigned_date}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
                                If you would like more information about this decision or believe there has been a misunderstanding,
                                please contact us at
                                <a href="mailto:{$support_email_link}" style="color:#b91c1c; text-decoration:none;">{$support_email}</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 24px 24px;">
                            <p style="margin:0; font-size:13px; color:#111827;">
                                Best regards,<br>
                                <strong>{$signature_name}</strong><br>
                                <span style="color:#6b7280;">{$signature_title}</span><br>
                                <span style="color:#6b7280;">{$site_name} – Vendor Support</span>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color:#f9fafb; padding:14px;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                This message is for {$vendor_name} and relates to your vendor account on {$site_name}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

            return $html;
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
}
