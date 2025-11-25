<?php
/**
 * Plugin Name: Printlana - Custom New Order Emails
 * Description: Sends custom HTML "order received" emails to customer and admin.
 * Author: Printlana
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared helpers (re-used by other email code too)
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
    function pl_generate_order_items_table(WC_Order $order): string
    {
        $rows = '';

        foreach ($order->get_items('line_item') as $item) {
            $name = esc_html($item->get_name());
            $qty = (int) $item->get_quantity();
            $total = wc_price($item->get_total());

            $rows .= '<tr>';
            $rows .= '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">' . $name . '</td>';
            $rows .= '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; text-align:center;">' . $qty . '</td>';
            $rows .= '<td style="padding:8px 6px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; text-align:right;">' . $total . '</td>';
            $rows .= '</tr>';
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

/**
 * Build the "Order received" HTML email (Template #1) for customer/admin
 */
if (!function_exists('pl_build_new_order_email_html')) {
    /**
     * @param array $d {
     *   @type string $recipient_type 'customer'|'admin'
     *   @type string $logo_url
     *   @type string $site_name
     *   @type string $order_number
     *   @type string $order_date
     *   @type string $order_items_html
     *   @type string $subtotal
     *   @type string $shipping_total
     *   @type string $tax_total
     *   @type string $order_total
     *   @type string $billing_address_html
     *   @type string $shipping_address_html
     *   @type string $customer_name
     *   @type string $support_email
     *   @type string $signature_name
     *   @type string $signature_title
     * }
     */
    function pl_build_new_order_email_html(array $d): string
    {
        $recipient_type = $d['recipient_type']; // customer|admin
        $logo_url = esc_url($d['logo_url']);
        $site_name = esc_html($d['site_name']);
        $order_number = esc_html($d['order_number']);
        $order_date = esc_html($d['order_date']);
        $order_items_html = $d['order_items_html'];
        $subtotal = wp_kses_post($d['subtotal']);
        $shipping_total = wp_kses_post($d['shipping_total']);
        $tax_total = wp_kses_post($d['tax_total']);
        $order_total = wp_kses_post($d['order_total']);
        $billing_address_html = $d['billing_address_html'];  // already HTML
        $shipping_address_html = $d['shipping_address_html']; // already HTML
        $customer_name = esc_html($d['customer_name']);
        $support_email = esc_html($d['support_email']);
        $signature_name = esc_html($d['signature_name']);
        $signature_title = esc_html($d['signature_title']);

        $support_email_link = esc_attr($support_email);

        // Intro per recipient type
        if ($recipient_type === 'admin') {
            $intro = sprintf(
                esc_html__('A new order #%1$s has been placed on %2$s. Customer details are included below.', 'printlana'),
                $order_number,
                $site_name
            );
        } else {
            // customer
            $intro = sprintf(
                esc_html__('Dear %1$s, we have received your order #%2$s placed on %3$s.', 'printlana'),
                $customer_name,
                $order_number,
                $order_date
            );
        }

        // Extra sentence for customer only
        $customer_extra = '';
        if ($recipient_type === 'customer') {
            $customer_extra = '<p style="margin:8px 0 0 0; font-size:13px; line-height:1.6; color:#4b5563;">'
                . esc_html__('If there is any issue with your order, our team will contact you using the details you provided.', 'printlana')
                . '</p>';
        }

        $html_intro = '<p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">'
            . $intro
            . '</p>'
            . $customer_extra;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f5f7fb; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f7fb; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <!-- Header / Logo -->
                    <tr>
                        <td align="center" style="background-color:#0044F1; padding:20px;">
                            <img src="{$logo_url}" alt="{$site_name} Logo" style="max-width:160px; height:auto; display:block;">
                        </td>
                    </tr>

                    <!-- Title -->
                    <tr>
                        <td style="padding:24px 24px 8px 24px;">
                            <h1 style="margin:0; font-size:20px; color:#111827; text-align:left;">
                                {$site_name} – Order #{$order_number}
                            </h1>
                        </td>
                    </tr>

                    <!-- Intro Text -->
                    <tr>
                        <td style="padding:0 24px 12px 24px;">
                            {$html_intro}
                        </td>
                    </tr>

                    <!-- Order Summary -->
                    <tr>
                        <td style="padding:16px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb; border-radius:6px;">
                                <tr>
                                    <td style="background-color:#f9fafb; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
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
                                            Subtotal: <strong>{$subtotal}</strong><br>
                                            Shipping: <strong>{$shipping_total}</strong><br>
                                            Taxes: <strong>{$tax_total}</strong><br>
                                            <span style="font-size:14px; color:#111827;">Total: <strong>{$order_total}</strong></span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Billing / Shipping -->
                    <tr>
                        <td style="padding:4px 24px 20px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td valign="top" style="width:50%; padding:8px 8px 8px 0;">
                                        <div style="border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px;">
                                            <p style="margin:0 0 6px 0; font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">
                                                Billing Details
                                            </p>
                                            <p style="margin:0; font-size:13px; color:#111827;">
                                                {$billing_address_html}
                                            </p>
                                        </div>
                                    </td>
                                    <td valign="top" style="width:50%; padding:8px 0 8px 8px;">
                                        <div style="border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px;">
                                            <p style="margin:0 0 6px 0; font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:0.03em;">
                                                Shipping Details
                                            </p>
                                            <p style="margin:0; font-size:13px; color:#111827;">
                                                {$shipping_address_html}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Help Text -->
                    <tr>
                        <td style="padding:0 24px 16px 24px;">
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
                                If you have any questions about this order, please reply to this email or contact us at
                                <a href="mailto:{$support_email_link}" style="color:#0044F1; text-decoration:none;">{$support_email}</a>.
                            </p>
                        </td>
                    </tr>

                    <!-- Signature -->
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

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color:#f9fafb; padding:14px;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                © {$site_name}. All rights reserved.
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
 * MAIN SENDER – hook to the same *_notification actions as WC_Email_New_Order
 */
if (!function_exists('pl_send_custom_new_order_emails')) {
    function pl_send_custom_new_order_emails($order_id, $order = false)
    {
        if ($order_id && !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        if (!$order instanceof WC_Order) {
            return;
        }

        // ❌ Only parent orders (avoid Dokan sub-orders & duplicates)
        if ((int) $order->get_parent_id() > 0) {
            return;
        }

        // Avoid sending multiple times
        if ($order->get_meta('_pl_new_order_email_sent') === 'yes') {
            return;
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $logo_url = pl_get_email_logo_url();
        $support = pl_get_support_email();
        $order_num = $order->get_order_number();
        $order_date = $order->get_date_created()
            ? wc_format_datetime($order->get_date_created())
            : '';
        $customer_email = $order->get_billing_email();
        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        if ($customer_name === '') {
            $customer_name = __('Customer', 'printlana');
        }

        // Totals
        $subtotal = wc_price($order->get_subtotal());
        $shipping_total = wc_price($order->get_shipping_total());
        $tax_total = wc_price($order->get_total_tax());
        $order_total = wc_price($order->get_total());

        $order_items_html = pl_generate_order_items_table($order);

        // Formatted addresses (already HTML with <br/>)
        $billing_address_html = $order->get_formatted_billing_address();
        $shipping_address_html = $order->get_formatted_shipping_address();
        if (!$shipping_address_html) {
            $shipping_address_html = $billing_address_html;
        }

        /**
         * 🔍 Admin recipients
         *
         * 1) Try to get them from the WC_Email_New_Order object (same as core email).
         * 2) Fallback: use woocommerce_new_order_settings['recipient'].
         * 3) Fallback: use site admin_email.
         */
        $admin_recipients = [];

        // 1) From WooCommerce mailer (preferred)
        if (function_exists('WC')) {
            $mailer = WC()->mailer();
            if ($mailer && method_exists($mailer, 'get_emails')) {
                $emails = $mailer->get_emails();
                if (is_array($emails) && isset($emails['WC_Email_New_Order'])) {
                    $new_order_email = $emails['WC_Email_New_Order'];
                    if ($new_order_email instanceof WC_Email) {
                        $raw_recipient = $new_order_email->get_recipient();
                        if (!empty($raw_recipient)) {
                            $admin_recipients = array_map('trim', explode(',', $raw_recipient));
                        }
                    }
                }
            }
        }

        // 2) Fallback: read the option directly
        if (empty($admin_recipients)) {
            $wc_new_order_settings = get_option('woocommerce_new_order_settings');
            if (is_array($wc_new_order_settings) && !empty($wc_new_order_settings['recipient'])) {
                $admin_recipients = array_map('trim', explode(',', $wc_new_order_settings['recipient']));
            }
        }

        // 3) Fallback: site admin email
        if (empty($admin_recipients)) {
            $admin_recipients[] = get_option('admin_email');
        }

        // Sanitize and dedupe
        $admin_recipients = array_filter(array_unique(array_map('sanitize_email', $admin_recipients)));

        // Debug log – you can remove this after confirming
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[pl_new_order_email] Admin recipients: ' . print_r($admin_recipients, true));
        }

        $signature_name = $site_name . ' Team';
        $signature_title = __('Customer Support', 'printlana');

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Subjects
        $subject_customer = sprintf(
            __('We received your order #%s', 'printlana'),
            $order_num
        );
        $subject_admin = sprintf(
            __('New order #%1$s from %2$s', 'printlana'),
            $order_num,
            $customer_name
        );

        // Build HTML for customer
        $customer_html = pl_build_new_order_email_html([
            'recipient_type' => 'customer',
            'logo_url' => $logo_url,
            'site_name' => $site_name,
            'order_number' => $order_num,
            'order_date' => $order_date,
            'order_items_html' => $order_items_html,
            'subtotal' => $subtotal,
            'shipping_total' => $shipping_total,
            'tax_total' => $tax_total,
            'order_total' => $order_total,
            'billing_address_html' => $billing_address_html,
            'shipping_address_html' => $shipping_address_html,
            'customer_name' => $customer_name,
            'support_email' => $support,
            'signature_name' => $signature_name,
            'signature_title' => $signature_title,
        ]);

        // Build HTML for admin
        $admin_html = pl_build_new_order_email_html([
            'recipient_type' => 'admin',
            'logo_url' => $logo_url,
            'site_name' => $site_name,
            'order_number' => $order_num,
            'order_date' => $order_date,
            'order_items_html' => $order_items_html,
            'subtotal' => $subtotal,
            'shipping_total' => $shipping_total,
            'tax_total' => $tax_total,
            'order_total' => $order_total,
            'billing_address_html' => $billing_address_html,
            'shipping_address_html' => $shipping_address_html,
            'customer_name' => $customer_name,
            'support_email' => $support,
            'signature_name' => $signature_name,
            'signature_title' => $signature_title,
        ]);

        // Send to customer
        if ($customer_email) {
            $sent_customer = wc_mail($customer_email, $subject_customer, $customer_html, $headers);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[pl_new_order_email] Customer mail result: ' . var_export($sent_customer, true) . ' to ' . $customer_email);
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[pl_new_order_email] No customer email address found for order ' . $order_num);
            }
        }

        // Send to admin(s)
        foreach ($admin_recipients as $admin_email) {
            if (!empty($admin_email)) {
                $sent_admin = wc_mail($admin_email, $subject_admin, $admin_html, $headers);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[pl_new_order_email] Admin mail result: ' . var_export($sent_admin, true) . ' to ' . $admin_email);
                }
            }
        }

        // Mark as sent
        $order->update_meta_data('_pl_new_order_email_sent', 'yes');
        $order->save();
    }


    // Hook into the same *_notification actions as WC core new order email
    $pl_new_order_hooks = [
        'woocommerce_order_status_pending_to_processing_notification',
        'woocommerce_order_status_pending_to_completed_notification',
        'woocommerce_order_status_pending_to_on-hold_notification',
        'woocommerce_order_status_failed_to_processing_notification',
        'woocommerce_order_status_failed_to_completed_notification',
        'woocommerce_order_status_failed_to_on-hold_notification',
        'woocommerce_order_status_cancelled_to_processing_notification',
        'woocommerce_order_status_cancelled_to_completed_notification',
        'woocommerce_order_status_cancelled_to_on-hold_notification',
    ];

    foreach ($pl_new_order_hooks as $hook) {
        // priority 20 so it runs after core, but you can change
        add_action($hook, 'pl_send_custom_new_order_emails', 20, 2);
    }
}

