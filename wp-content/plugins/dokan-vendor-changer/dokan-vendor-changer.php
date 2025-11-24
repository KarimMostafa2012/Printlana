<?php
/**
 * Plugin Name: Printlana Order Fulfillment Assigner
 * Description: Allows admin to assign an order to an eligible vendor for fulfillment.
 * Version: 6.0
 * Author: Printlana
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load plugin text domain for translations
 */
function pl_order_assigner_load_textdomain()
{
    load_plugin_textdomain('printlana-order-assigner', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'pl_order_assigner_load_textdomain');

class Printlana_Order_Assigner
{

    // ----------------------------------------------------------------------------------------------------------------
    public function __construct()
    {
        // Add the UI to the WooCommerce order edit page (HPOS compatible)
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_vendor_change_section'));

        // Handle the vendor assignment via AJAX
        add_action('wp_ajax_pl_assign_order_vendor', array($this, 'handle_vendor_assignment'));

        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete'], 20);
        add_action('woocommerce_order_status_processing', [$this, 'handle_order_processing'], 20);
    }


    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order)
            return;

        if ($order->get_parent_id()) {
            return;
        }
        $assigner = isset($GLOBALS['pl_assigner_singleton']) ? $GLOBALS['pl_assigner_singleton'] : $this;
        if (!$assigner->has_per_product_children($order)) {
            $default_vendor = (int) get_post_field('post_author', $order_id);
            $assigner->create_per_product_suborders($order, $default_vendor);
        }
    }


    public function handle_order_processing($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order)
            return;

        if ($order->get_parent_id()) {
            return;
        }

        $assigner = isset($GLOBALS['pl_assigner_singleton']) ? $GLOBALS['pl_assigner_singleton'] : $this;
        if (!$assigner->has_per_product_children($order)) {
            $default_vendor = (int) get_post_field('post_author', $order_id);
            $assigner->create_per_product_suborders($order, $default_vendor);
        }
    }




    /**
     * Does this parent already have per-product children?
     */
    private function has_per_product_children(WC_Order $parent): bool
    {
        $children = wc_get_orders([
            'parent' => $parent->get_id(),
            'type' => 'shop_order',
            'limit' => -1,
            'return' => 'ids',
            'status' => array_keys(wc_get_order_statuses()),
        ]);
        return !empty($children);
    }

    /**
     * Sync a Dokan order row for a given order id (safe across Dokan versions).
     */
    private function dokan_sync_order(int $order_id): void
    {
        // Preferred: official Dokan sync helper (exists in Dokan Lite/Pro)
        if (function_exists('dokan_sync_insert_order')) {
            dokan_sync_insert_order($order_id);
            return;
        }

        // Fallback to older internal API if present
        if (function_exists('dokan') && isset(dokan()->order)) {
            if (isset(dokan()->order->sync_table) && method_exists(dokan()->order->sync_table, 'sync')) {
                dokan()->order->sync_table->sync($order_id);
                return;
            }
        }

        // Last resort: fire Woo hooks that Dokan might be listening to
        $order = wc_get_order($order_id);
        if ($order) {
            do_action('woocommerce_new_order', $order_id, $order);
            do_action('woocommerce_update_order', $order_id, $order);
        }
    }


    /**
     * Update a child order's vendor (post_author + Dokan meta) and sync Dokan tables.
     */
    private function update_child_vendor(int $child_id, int $vendor_id): void
    {
        if ($vendor_id > 0) {
            error_log('showing vendor ID:',3, $vendor_id);
            update_post_meta($child_id, '_dokan_vendor_id', $vendor_id);
            wp_update_post([
                'ID' => $child_id,
                'post_author' => $vendor_id,
            ]);
        }
        $this->dokan_sync_order($child_id);
    }

    /**
     * Ensure parent shows it has sub orders (used by Dokan UI in some builds).
     */
    private function flag_parent_has_children(WC_Order $parent): void
    {
        $parent->update_meta_data('_has_sub_order', 1);           // legacy
        $parent->update_meta_data('_dokan_order_has_sub_order', 1);// some Dokan builds
        $parent->save();
    }



    // ----------------------------------------------------------------------------------------------------------------





    /**
     * Add the vendor assignment section to the order edit page.
     */
    // Inside class Printlana_Order_Assigner

    // Hook this ONCE (you already hook add_vendor_change_section here; we can call our renderer from there or add another hook)
    public function add_vendor_change_section($order)
    {
        if (!$order)
            return;

        if ($order->get_parent_id()) {
            echo '<div class="order_data_column" style="width:100%;clear:both;margin-top:20px;">';
            echo '<h3>' . esc_html__('Assign Order for Fulfillment', 'printlana-order-assigner') . '</h3>';
            $this->render_vendor_assignment_content($order);
            echo '</div>';
        }

        if (!$order->get_parent_id()) {
            // NEW: Sub-orders panel
            echo '<div class="order_data_column" style="width:100%;clear:both;margin-top:20px;">';
            echo '<h3>' . esc_html__('Sub-Orders (Per Product)', 'printlana-order-assigner') . '</h3>';
            $this->render_suborders_panel($order);
            echo '</div>';
        }
        echo '<style>' . esc_html__('#order_data .order_data_column{width: 100% !important;}', 'printlana-order-assigner') . '</style>';
    }

    private function render_suborders_panel(WC_Order $parent): void
    {
        $parent_id = $parent->get_id();

        // Get children
        $children = wc_get_orders([
            'parent' => $parent_id,
            'type' => 'shop_order',
            'limit' => -1,
            'return' => 'objects',
            'status' => array_keys(wc_get_order_statuses()),
        ]);

        // Pull the item→child map if present
        $item_map = (array) $parent->get_meta(self::META_ITEM_CHILD_MAP, true);

        if (empty($children)) {
            echo '<p style="color:#999;margin:6px 0;">' . esc_html__('No sub-orders found yet for this order.', 'printlana-order-assigner') . '</p>';
            return;
        }

        // Table header
        echo '<table class="widefat striped" style="margin-top:10px">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Sub-Order', 'printlana-order-assigner') . '</th>';
        echo '<th>' . esc_html__('Status', 'printlana-order-assigner') . '</th>';
        echo '<th>' . esc_html__('Vendor', 'printlana-order-assigner') . '</th>';
        echo '<th>' . esc_html__('Items', 'printlana-order-assigner') . '</th>';
        echo '<th>' . esc_html__('Total', 'printlana-order-assigner') . '</th>';
        echo '<th>' . esc_html__('Date', 'printlana-order-assigner') . '</th>';
        echo '<th style="text-align:right;">' . esc_html__('Actions', 'printlana-order-assigner') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($children as $child) {
            $cid = $child->get_id();
            $status_key = 'wc-' . $child->get_status();
            $status_name = wc_get_order_status_name($child->get_status());
            $total = $child->get_formatted_order_total();
            $date = $child->get_date_created() ? $child->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format')) : '—';

            $vendor_id = (int) $child->get_meta('_dokan_vendor_id');
            $vendor = $vendor_id ? get_user_by('id', $vendor_id) : null;
            $vendor_name = $vendor ? $vendor->display_name : '—';

            // One-line items summary
            $items_summary = [];
            foreach ($child->get_items('line_item') as $li) {

                $product_id = $li->get_product_id();
                $product_name = $li->get_name();

                // Get normal edit link
                $edit_link = get_edit_post_link($product_id);

                // Force WPML Arabic version (change 'ar' if needed)
                if ($edit_link) {
                    $edit_link .= '&lang=ar';
                }

                $items_summary[] = sprintf(
                    "<a href='%s' target='_blank'>%s × %d</a>",
                    esc_url($edit_link),
                    esc_html($product_name),
                    (int) $li->get_quantity()
                );
            }

            $items_txt = $items_summary
                ? wp_kses(implode(', ', $items_summary), [
                    'a' => [
                        'href' => [],
                        'target' => [],
                    ]
                ])
                : '—';

            // Get first product in this child order (each child has only one item)
            $view_link = '';
            $items = $child->get_items('line_item');

            if (!empty($items)) {
                $first_item = reset($items);
                if ($first_item) {
                    $product = $first_item->get_product();
                    if ($product) {
                        $view_link = get_permalink($product->get_id());
                    }
                }
            }


            // Links
            $edit_link = get_edit_post_link($cid);

            echo '<tr>';
            echo '<td><a href="' . esc_url($edit_link) . '">#' . (int) $cid . '</a></td>';
            echo '<td><mark class="order-status status-' . esc_attr($status_key) . '"><span>' . esc_html($status_name) . '</span></mark></td>';
            echo '<td>' . esc_html($vendor_name) . ' ' . ($vendor_id ? '(ID: ' . (int) $vendor_id . ')' : '') . '</td>';
            echo '<td>' . $items_txt . '</td>';
            echo '<td>' . wp_kses_post($total) . '</td>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td style="text-align:right;">';
            echo '<a class="button" href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'printlana-order-assigner') . '</a> ';
            echo '<a class="button" href="' . esc_url($view_link) . '" target="_blank">' . esc_html__('View Product', 'printlana-order-assigner') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Optional: chips under each parent line item to jump straight to its sub-order
        if (!empty($item_map)) {
            echo '<div style="margin-top:12px">';
            echo '<strong>' . esc_html__('Per-item Links:', 'printlana-order-assigner') . '</strong><br>';
            foreach ($parent->get_items('line_item') as $item_id => $item) {
                $cid = isset($item_map[$item_id]) ? (int) $item_map[$item_id] : 0;
                if ($cid) {
                    $link = get_edit_post_link($cid);
                    printf(
                        '<span class="tag" style="display:inline-block;margin:4px 6px 0 0;background:#f2f2f2;border:1px solid #ddd;border-radius:3px;padding:2px 6px;">
                        %s → <a href="%s">#%d</a>
                     </span>',
                        esc_html($item->get_name()),
                        esc_url($link),
                        $cid
                    );
                }
            }
            echo '</div>';
        }
    }


    // Inside class Printlana_Order_Assigner

    private const META_ITEM_CHILD_MAP = '_pl_item_child_map';

    private function create_per_product_suborders(WC_Order $parent, int $assign_vendor_id): array
    {
        if ($parent->get_meta('_pl_per_product_children_done')) {
            return [];
        }

        $created = [];
        $item_map = []; // parent line_item_id => child_order_id

        foreach ($parent->get_items('line_item') as $item_id => $item) {
            $child = wc_create_order(['parent' => $parent->get_id()]);
            if (!$child) {
                continue;
            }

            // Copy core context
            $child->set_customer_id($parent->get_customer_id());
            $child->set_created_via($parent->get_created_via());
            $child->set_currency($parent->get_currency());
            $child->set_payment_method($parent->get_payment_method());
            $child->set_payment_method_title($parent->get_payment_method_title());
            $child->set_address($parent->get_address('billing'), 'billing');
            $child->set_address($parent->get_address('shipping'), 'shipping');

            // Mirror current status (so it shows up properly in UI)
            $child->set_status($parent->get_status());

            // Copy product/variation with same amounts for this line only
            $product = $item->get_product();
            $variation_id = (int) $item->get_variation_id();
            $variation_attrs = [];

            if ($variation_id) {
                $variation_attrs = wc_get_product_variation_attributes($variation_id);
                $product = wc_get_product($variation_id) ?: $product;
            }

            $qty = (int) $item->get_quantity();
            $subtotal = (float) $item->get_subtotal();
            $total = (float) $item->get_total();

            $args = [
                'subtotal' => $subtotal,
                'total' => $total,
            ];
            if ($variation_id) {
                $args['variation_id'] = $variation_id;
                $args['variation'] = $variation_attrs;
            }

            $new_item_id = $child->add_product($product, $qty, $args);

            // Copy meta and taxes for that line
            $src_item = $parent->get_item($item_id);
            $dst_item = $child->get_item($new_item_id);

            foreach ($src_item->get_meta_data() as $md) {
                if (in_array($md->key, ['_line_subtotal', '_line_total', '_line_tax', '_line_subtotal_tax'], true)) {
                    continue;
                }
                $dst_item->update_meta_data($md->key, $md->value);
            }

            $line_taxes = $src_item->get_taxes();
            if (!empty($line_taxes)) {
                $dst_item->set_taxes($line_taxes);
            }
            $dst_item->save();

            // Tag vendor + author (visibility for Dokan)
            if ($assign_vendor_id > 0) {
                $child->update_meta_data('_dokan_vendor_id', $assign_vendor_id);
                wp_update_post([
                    'ID' => $child->get_id(),
                    'post_author' => $assign_vendor_id,
                ]);
            }

            // Linkage for UI
            $child->update_meta_data('_pl_parent_order_id', $parent->get_id());
            $child->update_meta_data('_pl_parent_item_id', $item_id);

            $child->calculate_totals(true);
            $child->save();

            // Sync Dokan safely
            $this->dokan_sync_order($child->get_id());

            $created[] = $child->get_id();
            $item_map[$item_id] = $child->get_id();
        }

        // Flag and store the map on the parent
        $this->flag_parent_has_children($parent);
        $parent->update_meta_data('_pl_per_product_children_done', time());
        $parent->update_meta_data(self::META_ITEM_CHILD_MAP, $item_map);
        $parent->save();

        // Admin note with quick links
        if (!empty($created)) {
            $links = array_map(fn($cid) => sprintf('<a href="%s" target="_blank">#%d</a>', get_edit_post_link($cid), $cid), $created);
            $parent->add_order_note(sprintf('Per-product sub-orders created: %s', implode(', ', $links)));
        }

        return $created;
    }



    /** 
     * 
     * Render the actual vendor assignment content
     */
    private function render_vendor_assignment_content($order)
    {
        if (!function_exists('dokan')) {
            echo '<p style="color: red;">' . __('Dokan plugin is not active.', 'printlana-order-assigner') . '</p>';
            return;
        }

        $order_id = $order->get_id();
        $current_vendor_id = get_post_field('post_author', $order_id);
        // $current_vendor = $current_vendor_id ? get_user_by('id', $current_vendor_id) : false;
        $vendor = get_user_by('id', $current_vendor_id);
        // MODIFIED: Get eligible vendors using our new custom metadata logic.
        $eligible_vendors = $this->get_eligible_vendors_for_order($order);

        wp_nonce_field('pl_assign_order_vendor_nonce', 'change_vendor_nonce');
        ?>
        <div class="pl-assign-vendor-wrapper" style="padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
            <p>
                <?php
                $assigned_vendor_id = (int) $order->get_meta('_pl_fulfillment_vendor_id');
                $assigned_vendor = $assigned_vendor_id ? get_user_by('id', $assigned_vendor_id) : null;
                echo '<strong>' . esc_html__('Current Assigned Vendor:', 'printlana-order-assigner') . "</strong><br>";
                if ($assigned_vendor) {
                    echo esc_html($assigned_vendor->display_name) . ' (ID: ' . (int) $assigned_vendor_id . ')';
                } else {
                    echo '<span style="color:#999;">' . esc_html__('Not yet assigned', 'printlana-order-assigner') . '</span>';
                } ?>
            </p>

            <?php if (empty($eligible_vendors)): ?>
                <p style="color: red;">
                    <?php _e('No single vendor is assigned to all products in this order.', 'printlana-order-assigner'); ?>
                </p>
            <?php else: ?>
                <p>
                    <label
                        for="new_vendor_id"><strong><?php _e('Select Vendor for Fulfillment:', 'printlana-order-assigner'); ?></strong></label><br>
                    <!--<input class="hidden" id="change_vendor_nonce" name="change_vendor_nonce" />-->
                    <select id="new_vendor_id" name="new_vendor_id" style="width: 100%; max-width: 300px;">
                        <option value=""><?php _e('-- Select a Vendor --', 'printlana-order-assigner'); ?></option>
                        <?php foreach ($eligible_vendors as $vendor_id => $vendor_name): ?>
                            <option value="<?php echo esc_attr($vendor_id); ?>">
                                <?php echo esc_html($vendor_name); ?> (ID: <?php echo $vendor_id; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <button type="button" id="pl_assign_vendor_btn" class="button button-primary">
                        <?php _e('Assign to this Vendor', 'printlana-order-assigner'); ?>
                    </button>
                </p>
            <?php endif; ?>

            <div id="pl_vendor_assign_message" style="margin-top: 10px;"></div>
        </div>

        <script type="text/javascript">

            jQuery(document).ready(function ($) {
                console.log('=== Dokan Vendor Change Script Loaded ===');
                console.log('Order ID:', <?php echo intval($order_id); ?>);
                console.log('Current Vendor ID:', '<?php echo $current_vendor_id; ?>');
                console.log('Button found:', $('#pl_assign_vendor_btn').length > 0);
                console.log('Select found:', $('#new_vendor_id').length > 0);

                // Try multiple binding methods to ensure it works
                $('#pl_assign_vendor_btn').on('click', function (e) {
                    e.preventDefault();
                    console.log('Method 1: Direct binding triggered');
                    changeVendor();
                });

                // Also add inline onclick for debugging
                $('#pl_assign_vendor_btn').attr('onclick', 'console.log("Inline onclick triggered"); return false;');

                // Test if jQuery is working
                $('#pl_assign_vendor_btn').css('cursor', 'pointer');

                // Main function to change vendor
                function changeVendor() {
                    console.log('=== changeVendor Function Called ===');

                    // Try multiple ways to get the value
                    var newVendorId = $('#new_vendor_id').val();
                    var newVendorIdNative = document.getElementById('new_vendor_id').value;
                    var newVendorIdSelected = $('#new_vendor_id option:selected').val();

                    console.log('Getting vendor value - Method 1 (jQuery val):', newVendorId);
                    console.log('Getting vendor value - Method 2 (Native JS):', newVendorIdNative);
                    console.log('Getting vendor value - Method 3 (Selected option):', newVendorIdSelected);

                    // Use native JS value as primary
                    newVendorId = newVendorIdNative || newVendorId || newVendorIdSelected;

                    var orderId = <?php echo intval($order_id); ?>;
                    var updateProduct = $('#update_product_vendor').is(':checked') ? 1 : 0;
                    var debugMode = $('#enable_debug_mode').is(':checked') ? 1 : 0;
                    var nonce = $('#change_vendor_nonce').val();

                    console.log('Form Data:', {
                        newVendorId: newVendorId,
                        orderId: orderId,
                        updateProduct: updateProduct,
                        debugMode: debugMode,
                        nonce: nonce
                    });

                    if (!newVendorId || newVendorId === '') {
                        console.warn('No vendor selected');
                        alert('<?php _e('Please select a vendor', 'dokan-change-vendor'); ?>');
                        return;
                    }

                    var currentVendorId = <?php echo intval($current_vendor_id); ?>;
                    console.log('=== Vendor Comparison ===');
                    console.log('Current Vendor ID (from PHP):', currentVendorId);
                    console.log('New Vendor ID (from select):', newVendorId);
                    console.log('New Vendor ID as integer:', parseInt(newVendorId));
                    console.log('Are they the same?:', parseInt(newVendorId) === currentVendorId);

                    // Force bypass for testing - remove this after testing
                    var forceBypass = false; // Set to true to skip the check temporarily

                    // Convert to integers for comparison
                    if (!forceBypass && parseInt(newVendorId) === currentVendorId) {
                        console.warn('Same vendor selected - showing alert');
                        alert('<?php _e('You selected the current vendor (ID: ', 'dokan-change-vendor'); ?>' + currentVendorId + '). <?php _e('Please select a different vendor.', 'dokan-change-vendor'); ?>');

                        // Show what's in the select for debugging
                        console.log('Current select element state:');
                        $('#new_vendor_id option').each(function () {
                            if ($(this).is(':selected')) {
                                console.log('SELECTED:', $(this).val(), $(this).text());
                            }
                        });
                        return;
                    }

                    console.log('Different vendor selected - proceeding...');

                    var confirmMsg = '<?php _e('Are you sure you want to change the vendor for this order?', 'dokan-change-vendor'); ?>';
                    if (!confirm(confirmMsg)) {
                        console.log('User cancelled the action');
                        return;
                    }

                    console.log('Disabling button and showing processing message...');
                    $('#pl_assign_vendor_btn').prop('disabled', true);
                    $('#pl_vendor_assign_message').html('<span style="color: blue;">⏳ Processing...</span>');

                    console.log('=== Sending AJAX Request ===');
                    console.log('AJAX URL:', ajaxurl);
                    console.log('Request Data:', {
                        action: 'pl_assign_order_vendor',
                        order_id: orderId,
                        new_vendor_id: newVendorId,
                        old_vendor_id: currentVendorId,
                        update_product: updateProduct,
                        debug_mode: debugMode,
                        nonce: nonce
                    });

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'pl_assign_order_vendor',
                            order_id: orderId,
                            new_vendor_id: newVendorId,
                            old_vendor_id: currentVendorId,
                            update_product: updateProduct,
                            debug_mode: debugMode,
                            nonce: nonce
                        },
                        beforeSend: function (xhr) {
                            console.log('AJAX request starting...');
                        },
                        success: function (response) {
                            console.log('=== AJAX Success Response ===');
                            console.log('Full Response:', response);
                            console.log('Success:', response.success);
                            console.log('Message:', response.data ? response.data.message : 'No message');

                            if (response.data && response.data.debug_info) {
                                console.log('Debug Info from Server:');
                                console.log(response.data.debug_info);
                            }

                            if (response.success) {
                                console.log('✓ Vendor change successful!');
                                var message = '<span style="color: green;">✓ ' + response.data.message + '</span>';
                                message += '<br><button onclick="location.reload();" class="button" style="margin-top: 5px;">Reload Page Now</button>';
                                if (response.data.debug_info) {
                                    message += '<br><br><strong>Debug Info:</strong><br>';
                                    message += '<pre style="font-size: 11px; background: #fff; padding: 5px;">' + response.data.debug_info + '</pre>';
                                }
                                $('#pl_vendor_assign_message').html(message);

                                console.log('Reloading page now...');
                                location.reload();
                            } else {
                                console.error('✗ Vendor change failed!');
                                console.error('Error message:', response.data ? response.data.message : 'Unknown error');

                                var errorMsg = '<span style="color: red;">✗ Error: ' + response.data.message + '</span>';
                                if (response.data.debug_info) {
                                    errorMsg += '<br><br><strong>Debug Info:</strong><br>';
                                    errorMsg += '<pre style="font-size: 11px; background: #fff; padding: 5px;">' + response.data.debug_info + '</pre>';
                                }
                                $('#pl_vendor_assign_message').html(errorMsg);
                                $('#pl_assign_vendor_btn').prop('disabled', false);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('=== AJAX Error ===');
                            console.error('Status:', status);
                            console.error('Error:', error);
                            console.error('Response Status:', xhr.status);
                            console.error('Response Text:', xhr.responseText);
                            console.error('Full XHR Object:', xhr);

                            $('#pl_vendor_assign_message').html('<span style="color: red;">✗ Ajax error: ' + error + '<br>Check console for details.</span>');
                            $('#pl_assign_vendor_btn').prop('disabled', false);
                        },
                        complete: function (xhr, status) {
                            console.log('=== AJAX Request Complete ===');
                            console.log('Final Status:', status);
                        }
                    });
                }

                // Make function globally available as fallback
                window.dokanChangeVendor = changeVendor;

                // Also try native JavaScript change event
                document.getElementById('new_vendor_id').addEventListener('change', function () {
                    console.log('Native JS - Vendor changed to:', this.value);
                });
            });

        </script>
        <?php
    }

    /**
     * NEW: Get vendors who are eligible for all products in the order.
     */
    private function get_eligible_vendors_for_order($order)
    {
        if (!$order) {
            return array();
        }

        $eligible_vendor_lists = array();
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                // Use our custom metadata field to find assigned vendors
                $assigned_vendors = get_post_meta($product_id, '_assigned_vendor_ids', true);
                if (is_array($assigned_vendors) && !empty($assigned_vendors)) {
                    $eligible_vendor_lists[] = $assigned_vendors;
                } else {
                    // If any product has NO assigned vendors, then no vendor can fulfill the whole order.
                    return array();
                }
            }
        }

        if (empty($eligible_vendor_lists)) {
            return array();
        }

        // Find the vendors that are common to ALL product lists.
        $common_vendor_ids = call_user_func_array('array_intersect', $eligible_vendor_lists);
        $common_vendor_ids = array_unique(array_filter(array_map('intval', $common_vendor_ids)));

        if (empty($common_vendor_ids)) {
            return array();
        }

        // Get user objects for the final list of eligible vendors
        $vendor_users = get_users(['include' => $common_vendor_ids, 'fields' => ['ID', 'display_name']]);

        $vendors = [];
        foreach ($vendor_users as $user) {
            $vendors[$user->ID] = $user->display_name;
        }

        return $vendors;
    }

    /**
     * MODIFIED: Handle vendor assignment by creating a Dokan sub-order.
     */
    public function handle_vendor_assignment()
    {
        try {
            // 1) Security & capability
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'pl_assign_order_vendor_nonce')) {
                wp_send_json_error(['message' => __('Security check failed.', 'printlana-order-assigner')]);
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error(['message' => __('You do not have permission.', 'printlana-order-assigner')]);
            }

            // 2) Inputs
            $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
            $new_vendor_id = isset($_POST['new_vendor_id']) ? (int) $_POST['new_vendor_id'] : 0;

            if (!$order_id || !$new_vendor_id) {
                wp_send_json_error(['message' => __('Invalid order or vendor ID.', 'printlana-order-assigner')]);
            }

            // 3) Load THIS order (we treat it as the sub-order to be updated)
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(['message' => __('Order not found.', 'printlana-order-assigner')]);
            }

            // 4) Update vendor on THIS order only
            $this->update_child_vendor($order_id, $new_vendor_id);

            // 5) Also store fulfillment vendor meta on THIS order (for your "Current Assigned Vendor" UI)
            $order->update_meta_data('_pl_fulfillment_vendor_id', $new_vendor_id);
            $order->save();

            // 6) Optional: add a note on the parent for traceability
            $parent_id = $order->get_parent_id();
            $new_vendor = get_user_by('id', $new_vendor_id);

            if ($parent_id) {
                $parent = wc_get_order($parent_id);
                if ($parent) {
                    $parent->add_order_note(sprintf(
                        /* translators: 1: sub-order ID, 2: vendor name or #id, 3: vendor id */
                        __('Sub-order #%1$d assigned to vendor %2$s (user #%3$d).', 'printlana-order-assigner'),
                        $order_id,
                        $new_vendor ? $new_vendor->display_name : ('#' . $new_vendor_id),
                        $new_vendor_id
                    ));
                    $parent->save();
                }
            }

            wp_send_json_success([
                'message' => __('Vendor updated on this sub-order.', 'printlana-order-assigner'),
                'child_ids' => [$order_id],
            ]);

        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => sprintf(__('Server error: %s', 'printlana-order-assigner'), $e->getMessage()),
            ], 500);
        }
    }



}

// Initialize the plugin
function init_printlana_order_assigner()
{
    if (class_exists('WooCommerce') && function_exists('dokan')) {
        $GLOBALS['pl_assigner_singleton'] = new Printlana_Order_Assigner();
    }
}
add_action('plugins_loaded', 'init_printlana_order_assigner');

