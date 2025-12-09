<?php
/**
 * Plugin Name: Printlana – Vendor Product Requests
 * Description: Allows vendors to request to sell admin products. Admins can approve/reject requests and vendors receive email notifications.
 * Version: 1.0.0
 * Author: Printlana
 */

if (!defined('ABSPATH')) {
    exit;
}

class Printlana_Vendor_Product_Requests
{
    const TABLE_NAME = 'pl_vendor_product_requests';

    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Log that plugin is loaded
        error_log("[PLUGIN LOADED] Printlana Vendor Product Requests plugin initialized");

        // Admin menu and page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers for admin
        add_action('wp_ajax_pl_approve_product_request', [$this, 'ajax_approve_request']);
        add_action('wp_ajax_pl_reject_product_request', [$this, 'ajax_reject_request']);

        // Vendor front-end button
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'add_request_button'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // AJAX handler for vendors
        add_action('wp_ajax_pl_request_to_sell_product', [$this, 'ajax_vendor_request']);

        // AJAX handler to check request status
        add_action('wp_ajax_pl_check_request_status', [$this, 'ajax_check_request_status']);

        // Test endpoint to verify plugin is working
        add_action('wp_ajax_pl_test_plugin', function () {
            wp_send_json_success(['message' => 'Plugin is active and AJAX is working!']);
        });
    }

    /**
     * Create database table on activation
     */
    public function activate()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            vendor_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            requested_at DATETIME NOT NULL,
            processed_at DATETIME NULL,
            processed_by BIGINT(20) UNSIGNED NULL,
            PRIMARY KEY (id),
            KEY product_vendor (product_id, vendor_id),
            KEY vendor_id (vendor_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        // Get pending count for badge
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'");

        $menu_title = __('Product Requests', 'printlana');
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod">' . $pending_count . '</span>';
        }

        add_submenu_page(
            'printlana-vendor-assign',
            __('Vendor Product Requests', 'printlana'),
            $menu_title,
            'manage_woocommerce',
            'pl-product-requests',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Debug: Log the hook to find the correct one
        error_log('[Product Requests] Admin hook: ' . $hook);

        // Check if we're on the product requests page
        // The hook format for a submenu is: {parent_slug}_page_{menu_slug}
        // Or we can check the page parameter
        $is_requests_page = (
            isset($_GET['page']) && $_GET['page'] === 'pl-product-requests'
        );

        if (!$is_requests_page) {
            return;
        }

        wp_enqueue_style('pl-product-requests-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], '1.0.2');
        wp_enqueue_script('pl-product-requests-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], '1.0.2', true);

        wp_localize_script('pl-product-requests-admin', 'plProductRequests', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pl_product_requests'),
        ]);

        error_log('[Product Requests] Admin assets enqueued');
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        if (!dokan_is_user_seller($user_id)) {
            return;
        }

        wp_enqueue_style('pl-product-requests-frontend', plugin_dir_url(__FILE__) . 'assets/css/frontend.css', [], '1.0.1');
        wp_enqueue_script('pl-product-requests-frontend', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', ['jquery'], '1.0.1', true);

        wp_localize_script('pl-product-requests-frontend', 'plVendorRequests', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pl_vendor_request'),
            'i18n' => [
                'requesting' => __('Requesting...', 'printlana'),
                'requested' => __('Request Sent', 'printlana'),
                'request_to_sell' => __('Request to Sell', 'printlana'),
                'error' => __('Error sending request', 'printlana'),
            ],
        ]);
    }

    /**
     * Add request button to product loops for vendors
     */
    public function add_request_button($button, $product)
    {
        if (!is_user_logged_in()) {
            return $button;
        }

        $user_id = get_current_user_id();
        error_log("[Product Request] Checking request button for user {$user_id} on product {$product->get_id()}");
        error_log("[Product Request] User is " . (dokan_is_user_seller($user_id) ? "" : "not ") . "a vendor");
        if (!dokan_is_user_seller($user_id)) {
            return $button;
        }

        // Only show for admin products
        $product_author = get_post_field('post_author', $product->get_id());
        if (!user_can($product_author, 'manage_woocommerce')) {
            return $button;
        }

        // Check if already assigned in the mapping table
        global $wpdb;
        $mapping_table = $wpdb->prefix . 'pl_product_vendors';
        $already_assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$mapping_table} WHERE product_id = %d AND vendor_id = %d",
            $product->get_id(),
            $user_id
        ));

        if ($already_assigned) {
            return '<button class="button pl-request-btn pl-approved" disabled>' . __('Already Assigned', 'printlana') . '</button>';
        }

        // Check if already requested
        $table = $wpdb->prefix . self::TABLE_NAME;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$table} WHERE product_id = %d AND vendor_id = %d",
            $product->get_id(),
            $user_id
        ));

        if ($existing) {
            if ($existing->status === 'pending') {
                return '<button class="button pl-request-btn pl-requested" disabled>' . __('Request Pending', 'printlana') . '</button>';
            } elseif ($existing->status === 'approved') {
                return '<button class="button pl-request-btn pl-approved" disabled>' . __('Approved', 'printlana') . '</button>';
            }
        }

        return '<button class="button pl-request-btn" data-product-id="' . esc_attr($product->get_id()) . '">' . __('Request to Sell', 'printlana') . '</button>';
    }

    /**
     * AJAX: Vendor requests to sell a product
     */
    public function ajax_vendor_request()
    {
        check_ajax_referer('pl_vendor_request', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'printlana')], 403);
        }

        $user_id = get_current_user_id();
        error_log("[Product Request] Vendor {$user_id} is attempting to request a product");
        error_log("[Product Request] User is " . (dokan_is_user_seller($user_id) ? "" : "not ") . "a vendor");
        if (!dokan_is_user_seller($user_id)) {
            wp_send_json_error(['message' => __('Only vendors can request products.', 'printlana')], 403);
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        if (!$product_id) {
            wp_send_json_error(['message' => __('Invalid product.', 'printlana')], 400);
        }

        // Check if already assigned
        global $wpdb;
        $mapping_table = $wpdb->prefix . 'pl_product_vendors';
        $already_assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$mapping_table} WHERE product_id = %d AND vendor_id = %d",
            $product_id,
            $user_id
        ));

        if ($already_assigned) {
            error_log("[Product Request] Vendor {$user_id} tried to request product {$product_id} but is already assigned");
            wp_send_json_error(['message' => __('You are already assigned to this product.', 'printlana')], 400);
        }

        // Check if already requested
        $table = $wpdb->prefix . self::TABLE_NAME;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %d AND vendor_id = %d",
            $product_id,
            $user_id
        ));

        if ($existing) {
            error_log("[Product Request] Vendor {$user_id} tried to request product {$product_id} but already has a request");
            wp_send_json_error(['message' => __('You already requested this product.', 'printlana')], 400);
        }

        // Insert request
        $inserted = $wpdb->insert(
            $table,
            [
                'product_id' => $product_id,
                'vendor_id' => $user_id,
                'status' => 'pending',
                'requested_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s']
        );

        if (!$inserted) {
            error_log("[Product Request] Failed to insert request for vendor {$user_id} and product {$product_id}");
            wp_send_json_error(['message' => __('Failed to create request.', 'printlana')], 500);
        }

        error_log("[Product Request] SUCCESS - Vendor {$user_id} requested product {$product_id}");
        wp_send_json_success(['message' => __('Request sent successfully!', 'printlana')]);
    }

    /**
     * AJAX: Check request status for products
     * Returns which products have pending/approved requests for the current vendor
     */
    public function ajax_check_request_status()
    {
        check_ajax_referer('pl_vendor_request', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'printlana')], 403);
        }

        $user_id = get_current_user_id();
        if (!dokan_is_user_seller($user_id)) {
            wp_send_json_error(['message' => __('Only vendors can check request status.', 'printlana')], 403);
        }

        $product_ids = isset($_POST['product_ids']) ? array_map('absint', (array) $_POST['product_ids']) : [];
        if (empty($product_ids)) {
            wp_send_json_error(['message' => __('No product IDs provided.', 'printlana')], 400);
        }

        // Limit to prevent abuse
        $product_ids = array_slice($product_ids, 0, 50);

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        // Get all requests for this vendor and these products
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
        $params = array_merge([$user_id], $product_ids);

        $requests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id, status FROM {$table}
                 WHERE vendor_id = %d
                 AND product_id IN ({$placeholders})",
                $params
            ),
            OBJECT_K
        );

        $pending_products = [];
        $approved_products = [];

        foreach ($requests as $product_id => $request) {
            if ($request->status === 'pending') {
                $pending_products[] = (int) $product_id;
            } elseif ($request->status === 'approved') {
                $approved_products[] = (int) $product_id;
            }
        }

        wp_send_json_success([
            'pending_products' => $pending_products,
            'approved_products' => $approved_products,
        ]);
    }

    /**
     * AJAX: Approve request
     */
    public function ajax_approve_request()
    {
        error_log('[Product Request] Approve request AJAX called');

        check_ajax_referer('pl_product_requests', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            error_log('[Product Request] Approve denied - not admin');
            wp_send_json_error(['message' => __('Permission denied.', 'printlana')], 403);
        }

        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        error_log('[Product Request] Approving request ID: ' . $request_id);

        if (!$request_id) {
            wp_send_json_error(['message' => __('Invalid request.', 'printlana')], 400);
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        // Get request details
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $request_id
        ));

        if (!$request) {
            error_log('[Product Request] Request not found for ID: ' . $request_id);
            wp_send_json_error(['message' => __('Request not found.', 'printlana')], 404);
        }

        error_log('[Product Request] Found request - Product: ' . $request->product_id . ', Vendor: ' . $request->vendor_id);

        // Update request status
        $wpdb->update(
            $table,
            [
                'status' => 'approved',
                'processed_at' => current_time('mysql'),
                'processed_by' => get_current_user_id(),
            ],
            ['id' => $request_id],
            ['%s', '%s', '%d'],
            ['%d']
        );

        // Assign product to vendor using the mapping table
        $mapping_table = $wpdb->prefix . 'pl_product_vendors';
        $wpdb->replace(
            $mapping_table,
            [
                'product_id' => $request->product_id,
                'vendor_id' => $request->vendor_id,
            ],
            ['%d', '%d']
        );

        error_log('[Product Request] Product ' . $request->product_id . ' assigned to vendor ' . $request->vendor_id);

        // Send email to vendor
        $this->send_approval_email($request->vendor_id, $request->product_id);

        error_log('[Product Request] Request approved successfully');
        wp_send_json_success(['message' => __('Request approved!', 'printlana')]);
    }

    /**
     * AJAX: Reject request
     */
    public function ajax_reject_request()
    {
        error_log('[Product Request] Reject request AJAX called');

        check_ajax_referer('pl_product_requests', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            error_log('[Product Request] Reject denied - not admin');
            wp_send_json_error(['message' => __('Permission denied.', 'printlana')], 403);
        }

        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        error_log('[Product Request] Rejecting request ID: ' . $request_id);

        if (!$request_id) {
            wp_send_json_error(['message' => __('Invalid request.', 'printlana')], 400);
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $wpdb->update(
            $table,
            [
                'status' => 'rejected',
                'processed_at' => current_time('mysql'),
                'processed_by' => get_current_user_id(),
            ],
            ['id' => $request_id],
            ['%s', '%s', '%d'],
            ['%d']
        );

        error_log('[Product Request] Request rejected successfully');
        wp_send_json_success(['message' => __('Request rejected.', 'printlana')]);
    }

    /**
     * Send approval email to vendor
     */
    private function send_approval_email($vendor_id, $product_id)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $vendor = get_user_by('id', $vendor_id);
        if (!$vendor) {
            return;
        }

        $vendor_email = $vendor->user_email;
        $vendor_name = $vendor->display_name;
        $product_name = $product->get_name();
        $product_url = get_permalink($product_id);
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        // Get logo
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

        $subject = sprintf(__('Your request to sell "%s" has been approved!', 'printlana'), $product_name);

        $message = $this->build_approval_email_html([
            'logo_url' => $logo_url,
            'site_name' => $site_name,
            'vendor_name' => $vendor_name,
            'product_name' => $product_name,
            'product_url' => $product_url,
        ]);

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wc_mail($vendor_email, $subject, $message, $headers);
    }

    /**
     * Build approval email HTML
     */
    private function build_approval_email_html($d)
    {
        $logo_url = esc_url($d['logo_url']);
        $site_name = esc_html($d['site_name']);
        $vendor_name = esc_html($d['vendor_name']);
        $product_name = esc_html($d['product_name']);
        $product_url = esc_url($d['product_url']);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Approved</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f5f7fb; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f7fb; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td align="center" style="background-color:#10b981; padding:20px;">
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
                                Your request to sell <strong>"{$product_name}"</strong> has been approved!
                            </p>
                            <p style="margin:12px 0 0 0; font-size:14px; line-height:1.6; color:#4b5563;">
                                You can now start selling this product from your vendor dashboard.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:20px 24px;">
                            <a href="{$product_url}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; background-color:#10b981; text-decoration:none; border-radius:999px;">
                                View Product
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 24px 24px;">
                            <p style="margin:0; font-size:13px; color:#111827;">
                                Best regards,<br>
                                <strong>{$site_name} Team</strong><br>
                                <span style="color:#6b7280;">Vendor Relations</span>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color:#f9fafb; padding:14px;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                This email was sent because your request to sell a product was approved on {$site_name}.
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

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        // Get filter
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';

        // Get requests
        $where = $wpdb->prepare("WHERE status = %s", $status_filter);
        $requests = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY requested_at DESC");

        // Count by status
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'");
        $approved_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'approved'");
        $rejected_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'rejected'");

        ?>
        <div class="wrap">
            <h1><?php _e('Vendor Product Requests', 'printlana'); ?></h1>

            <ul class="subsubsub">
                <li><a href="?page=pl-product-requests&status=pending"
                        class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        <?php _e('Pending', 'printlana'); ?> <span class="count">(<?php echo $pending_count; ?>)</span>
                    </a> |</li>
                <li><a href="?page=pl-product-requests&status=approved"
                        class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        <?php _e('Approved', 'printlana'); ?> <span class="count">(<?php echo $approved_count; ?>)</span>
                    </a> |</li>
                <li><a href="?page=pl-product-requests&status=rejected"
                        class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                        <?php _e('Rejected', 'printlana'); ?> <span class="count">(<?php echo $rejected_count; ?>)</span>
                    </a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'printlana'); ?></th>
                        <th><?php _e('Vendor', 'printlana'); ?></th>
                        <th><?php _e('Requested', 'printlana'); ?></th>
                        <?php if ($status_filter !== 'pending'): ?>
                            <th><?php _e('Processed', 'printlana'); ?></th>
                        <?php endif; ?>
                        <th><?php _e('Actions', 'printlana'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="<?php echo $status_filter !== 'pending' ? '5' : '4'; ?>">
                                <?php _e('No requests found.', 'printlana'); ?>
                            </td>
                        </tr>
                    <?php else:
                        foreach ($requests as $request):
                            $product = wc_get_product($request->product_id);
                            $vendor = get_user_by('id', $request->vendor_id);
                            ?>
                            <tr>
                                <td>
                                    <?php if ($product): ?>
                                        <strong><a href="<?php echo get_edit_post_link($request->product_id); ?>" target="_blank">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </a></strong><br>
                                        <small>ID: <?php echo $request->product_id; ?></small>
                                    <?php else: ?>
                                        <em><?php _e('Product not found', 'printlana'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($vendor): ?>
                                        <?php echo esc_html($vendor->display_name); ?><br>
                                        <small><?php echo esc_html($vendor->user_email); ?></small>
                                    <?php else: ?>
                                        <em><?php _e('Vendor not found', 'printlana'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($request->requested_at)); ?>
                                </td>
                                <?php if ($status_filter !== 'pending'): ?>
                                    <td>
                                        <?php echo $request->processed_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($request->processed_at)) : '-'; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($request->status === 'pending'): ?>
                                        <button class="button button-primary pl-approve-btn" data-request-id="<?php echo $request->id; ?>">
                                            <?php _e('Approve', 'printlana'); ?>
                                        </button>
                                        <button class="button pl-reject-btn" data-request-id="<?php echo $request->id; ?>">
                                            <?php _e('Reject', 'printlana'); ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="pl-status-<?php echo esc_attr($request->status); ?>">
                                            <?php echo ucfirst($request->status); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new Printlana_Vendor_Product_Requests();
