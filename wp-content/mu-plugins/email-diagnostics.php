<?php
/**
 * Plugin Name: Email Diagnostics & Test
 * Description: Test email delivery and diagnose email configuration issues
 * Version: 1.0
 * Author: Performance Optimization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for email diagnostics
 */
add_action('admin_menu', 'pl_email_diagnostics_menu');
function pl_email_diagnostics_menu() {
    add_submenu_page(
        'tools.php',
        'Email Diagnostics',
        'Email Test',
        'manage_options',
        'email-diagnostics',
        'pl_email_diagnostics_page'
    );
}

/**
 * Render the diagnostics page
 */
function pl_email_diagnostics_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $test_result = null;
    $test_email = '';

    // Handle test email request
    if (isset($_POST['send_test_email']) && check_admin_referer('pl_email_test', 'pl_email_nonce')) {
        $test_email = sanitize_email($_POST['test_email']);
        if (!empty($test_email)) {
            $test_result = pl_send_test_email($test_email);
        }
    }

    // Get current email configuration
    $from_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    $smtp_configured = false;

    // Check if WP Mail SMTP is active and configured
    if (function_exists('wp_mail_smtp')) {
        $smtp_configured = true;
        $smtp_options = get_option('wp_mail_smtp', array());
    }

    ?>
    <div class="wrap">
        <h1>📧 Email Diagnostics & Test</h1>

        <?php if (isset($_GET['page']) && $_GET['page'] === 'email-diagnostics'): ?>

            <!-- Current Configuration -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>Current Email Configuration</h2>
                <table class="widefat">
                    <tr>
                        <td style="width: 200px;"><strong>Site Name:</strong></td>
                        <td><?php echo esc_html($site_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Admin Email:</strong></td>
                        <td><?php echo esc_html($from_email); ?></td>
                    </tr>
                    <tr>
                        <td><strong>WP Mail SMTP:</strong></td>
                        <td>
                            <?php if ($smtp_configured): ?>
                                <span style="color: #00a32a;">✅ Installed</span>
                            <?php else: ?>
                                <span style="color: #d63638;">❌ Not installed or not active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>PHP Mail Function:</strong></td>
                        <td>
                            <?php if (function_exists('mail')): ?>
                                <span style="color: #00a32a;">✅ Available</span>
                            <?php else: ?>
                                <span style="color: #d63638;">❌ Disabled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Error 5.7.1 Explanation -->
            <div class="notice notice-error" style="padding: 15px; max-width: 800px;">
                <h3 style="margin-top: 0;">🚨 Error 5.7.1 - Authentication Required</h3>
                <p><strong>What this error means:</strong></p>
                <p>Your emails are being rejected because they're not properly authenticated. Gmail (and other providers) require proper SMTP authentication to prevent spam.</p>

                <p><strong>Why this happens:</strong></p>
                <ul>
                    <li>WordPress is trying to send emails using PHP mail() function without authentication</li>
                    <li>Gmail requires SMTP authentication (username + password or App Password)</li>
                    <li>Your server's email isn't authorized to send on behalf of printlanasa@gmail.com</li>
                </ul>

                <p><strong>Solution:</strong> You MUST configure WP Mail SMTP with proper credentials below ⬇️</p>
            </div>

            <!-- WP Mail SMTP Configuration Guide -->
            <div class="card" style="max-width: 800px; margin: 20px 0; background: #e7f5fe; border-left: 4px solid #0073aa;">
                <h2 style="margin-top: 0;">🔧 Fix: Configure WP Mail SMTP</h2>

                <p><strong>Step 1: Go to WP Mail SMTP Settings</strong></p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wp-mail-smtp'); ?>" class="button button-primary button-large" target="_blank">
                        Open WP Mail SMTP Settings →
                    </a>
                </p>

                <hr style="margin: 20px 0;">

                <p><strong>Step 2: Choose "Gmail" as your mailer</strong></p>
                <ol>
                    <li>In WP Mail SMTP settings, select <strong>"Gmail"</strong></li>
                    <li>From Email: <code>printlanasa@gmail.com</code></li>
                    <li>From Name: <code>Printlana</code></li>
                    <li>Check both "Force From Email" and "Force From Name"</li>
                </ol>

                <hr style="margin: 20px 0;">

                <p><strong>Step 3: Get Google App Password</strong></p>
                <ol>
                    <li>Go to <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                    <li>Enable <strong>2-Step Verification</strong> (required for App Passwords)</li>
                    <li>After enabling 2FA, go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                    <li>Create a new App Password:
                        <ul>
                            <li>Select app: "Mail"</li>
                            <li>Select device: "Other (Custom name)" → type "Printlana WordPress"</li>
                            <li>Click "Generate"</li>
                        </ul>
                    </li>
                    <li>Copy the 16-character password (it looks like: <code>xxxx xxxx xxxx xxxx</code>)</li>
                </ol>

                <hr style="margin: 20px 0;">

                <p><strong>Step 4: Enter App Password in WP Mail SMTP</strong></p>
                <ol>
                    <li>Paste the 16-character App Password in WP Mail SMTP</li>
                    <li>Click "Save Settings"</li>
                    <li>Come back here and test your email below</li>
                </ol>

                <hr style="margin: 20px 0;">

                <div style="background: #fff; padding: 15px; border-left: 3px solid #00a32a;">
                    <strong>Alternative: Use SMTP.com (Recommended for Production)</strong>
                    <p>If you want a more professional solution:</p>
                    <ol>
                        <li>Sign up at <a href="https://www.smtp.com/" target="_blank">SMTP.com</a> (1000 free emails/month)</li>
                        <li>Get your API credentials</li>
                        <li>In WP Mail SMTP, select "SMTP.com" and enter your credentials</li>
                    </ol>
                </div>
            </div>

            <!-- Test Email Form -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>📨 Send Test Email</h2>
                <p>After configuring WP Mail SMTP, use this form to test if emails are working:</p>

                <form method="post" action="">
                    <?php wp_nonce_field('pl_email_test', 'pl_email_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_email">Send test email to:</label>
                            </th>
                            <td>
                                <input type="email"
                                       id="test_email"
                                       name="test_email"
                                       value="<?php echo esc_attr($test_email ?: $from_email); ?>"
                                       class="regular-text"
                                       required>
                                <p class="description">Enter the email address where you want to receive the test email</p>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="send_test_email" class="button button-primary button-large">
                            Send Test Email
                        </button>
                    </p>
                </form>

                <?php if ($test_result !== null): ?>
                    <div class="notice <?php echo $test_result['success'] ? 'notice-success' : 'notice-error'; ?>" style="margin: 20px 0;">
                        <h3><?php echo $test_result['success'] ? '✅ Test Email Sent!' : '❌ Test Email Failed'; ?></h3>
                        <p><?php echo esc_html($test_result['message']); ?></p>

                        <?php if (isset($test_result['details'])): ?>
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; font-weight: bold;">Technical Details</summary>
                                <pre style="background: #f5f5f5; padding: 10px; overflow: auto; margin-top: 10px;"><?php echo esc_html($test_result['details']); ?></pre>
                            </details>
                        <?php endif; ?>

                        <?php if (!$test_result['success']): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107;">
                                <strong>Troubleshooting:</strong>
                                <ul style="margin: 5px 0;">
                                    <li>Make sure you've configured WP Mail SMTP properly</li>
                                    <li>Double-check your Gmail App Password is correct</li>
                                    <li>Verify 2-Step Verification is enabled on your Google account</li>
                                    <li>Try using SMTP.com instead of Gmail</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- WP Mail SMTP Logs Link -->
            <?php if ($smtp_configured): ?>
                <div class="card" style="max-width: 800px; margin: 20px 0;">
                    <h2>📊 Check Email Logs</h2>
                    <p>WP Mail SMTP keeps logs of all emails sent. Check them for errors:</p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=wp-mail-smtp-logs'); ?>" class="button button-secondary" target="_blank">
                            View Email Logs →
                        </a>
                    </p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    <?php
}

/**
 * Send a test email
 */
function pl_send_test_email($to_email) {
    // Capture email errors
    $phpmailer_error = '';

    add_action('wp_mail_failed', function($error) use (&$phpmailer_error) {
        $phpmailer_error = $error->get_error_message();
    });

    $subject = 'Test Email from ' . get_bloginfo('name');
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; padding: 20px;'>
        <h2 style='color: #0073aa;'>✅ Email Configuration Test</h2>
        <p>This is a test email from your WordPress site: <strong>" . get_bloginfo('name') . "</strong></p>
        <p>If you're seeing this, your email configuration is working correctly!</p>
        <hr>
        <p style='font-size: 12px; color: #666;'>
            Sent at: " . current_time('Y-m-d H:i:s') . "<br>
            From: " . get_bloginfo('url') . "
        </p>
    </body>
    </html>
    ";

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
    );

    // Try to send email
    $sent = wp_mail($to_email, $subject, $message, $headers);

    if ($sent) {
        return array(
            'success' => true,
            'message' => "Test email sent successfully to {$to_email}. Check your inbox (and spam folder).",
            'details' => "Email sent using WordPress wp_mail() function.\nTo: {$to_email}\nSubject: {$subject}"
        );
    } else {
        return array(
            'success' => false,
            'message' => "Failed to send test email. " . ($phpmailer_error ?: 'Unknown error occurred.'),
            'details' => "Error: " . ($phpmailer_error ?: 'No detailed error available.') . "\n\nMake sure WP Mail SMTP is properly configured with authentication."
        );
    }
}

/**
 * Add quick link to admin bar
 */
add_action('admin_bar_menu', 'pl_email_test_admin_bar', 999);
function pl_email_test_admin_bar($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $wp_admin_bar->add_node(array(
        'id' => 'email-test',
        'title' => '📧 Email Test',
        'href' => admin_url('tools.php?page=email-diagnostics'),
        'meta' => array(
            'title' => 'Test email delivery'
        )
    ));
}
