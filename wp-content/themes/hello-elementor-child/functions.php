<?php



// ------------------------------

// functions.php
add_filter('wp_get_attachment_image_attributes', function ($attr, $att, $size) {
    // Replace 12345 with your hero/LCP attachment ID
    if ((int) $att->ID === 12345) {
        $attr['fetchpriority'] = 'high';
        $attr['loading'] = 'eager';
        $attr['decoding'] = 'async';
        // Set a tight sizes to prevent massive srcset downloads
        $attr['sizes'] = '(max-width: 1024px) 100vw, 1280px';
    }
    return $attr;
}, 10, 3);

// implement length in Advanced Custom Fields

add_action('save_post_product', function ($post_id) {
    // Only run on product post type
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    $product = wc_get_product($post_id);
    if (!$product)
        return;

    // Get WooCommerce dimensions
    $length = floatval($product->get_length());
    $width = floatval($product->get_width());
    $height = floatval($product->get_height());

    // Save into ACF or custom meta fields
    update_post_meta($post_id, 'pl_length', $length);
    update_post_meta($post_id, 'pl_width', $width);
    update_post_meta($post_id, 'pl_height', $height);
});



function get_last_added_product()
{
    $products = wc_get_products([
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    return $products ? $products[0] : null;
}

add_action('save_post_product', function ($post_id, $post, $update) {
    if ($update)
        return; // Only for new products

    $product = wc_get_product($post_id);
    if (!$product)
        return;

    $prefix = 'P126';
    $number_length = 5; // Always keep 5 digits
    $last_product = get_last_added_product();

    if ($last_product) {
        $last_sku = $last_product->get_sku();

        // Remove the prefix to get numeric part
        $number_part = str_replace($prefix, '', strtoupper($last_sku));

        // Increment the number
        $new_number = intval($number_part) + 1;

        // Format with leading zeros to fixed width
        $new_sku = $prefix . str_pad($new_number, $number_length, '0', STR_PAD_LEFT);
    }

    $product->set_sku($new_sku);
    $product->save();
}, 10, 3);






/**
 * Re-order product categories for the Loop Grid widget with Query ID = home_categories
 * (Elementor Source: Product categories).
 */
add_filter(
    'elementor/loop_taxonomy/args',
    function ($args, $taxonomy, $settings) {

        // Custom order
        $args['include'] = [20, 19, 21, 32, 30, 316, 24, 26, 34, 27, 25, 427, 215, 227, 230, 226, 217, 317, 223, 218, 222, 228, 221, 426];
        $args['orderby'] = 'include';
        $args['hide_empty'] = false;

        // Elementor may add parent/child_of which conflict with include
        unset($args['parent'], $args['child_of']);

        return $args;
    },
    10,
    3
);




// ------------------------------



// Include parent styles
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_styles');
function hello_elementor_child_enqueue_styles()
{
    // Include parent theme style
    wp_enqueue_style('hello-elementor-style', get_template_directory_uri() . '/style.css');

    // Include main CSS, all CSS files includes in main-style.css file
    wp_enqueue_style(
        'main-custom-style',
        get_stylesheet_directory_uri() . '/assets/style/main-style.css',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/style/main-style.css') // Actual version for cache
    );

    // Include custom JavaScript for Favorites
    wp_enqueue_script(
        'custom-favorites',
        get_stylesheet_directory_uri() . '/assets/js/custom-favorites.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/custom-favorites.js'),
        true
    );

    // Pass image URL to JS, this code works to change html in Favorites Page
    wp_localize_script('custom-favorites', 'my_favorite_icon', array(
        'cart_icon' => get_stylesheet_directory_uri() . '/assets/img/fav-cart.png',
    ));

    // Include custom scripts
    wp_enqueue_script(
        'custom-scripts',
        get_stylesheet_directory_uri() . '/assets/js/custom-script.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/custom-script.js'),
        true
    );

    // Include custom JavaScript for Reviews single product
    wp_enqueue_script(
        'reviews-ajax',
        get_stylesheet_directory_uri() . '/assets/js/reviews-ajax.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/reviews-ajax.js'),
        true
    );
}

// Shortcodes Order Confirmation
require get_stylesheet_directory() . '/inc/shortcodes/order-confirmation.php';

// Shortcode to show Single Product Reviews with AJAX
require get_stylesheet_directory() . '/inc/shortcodes/reviews-product.php';

// Custom dashboard for Dokan
require get_stylesheet_directory() . '/inc/dokan/custom-dashboard.php';

// Dokan add custom content to "My Wishlist" endpoint
require get_stylesheet_directory() . '/inc/hooks/dokan-wishlist-inspiration.php';


/**
 * Add profile image to WooCommerce account page
 */

// Add profile image field
function action_woocommerce_edit_account_form_start()
{
    $user_id = get_current_user_id();
    $attachment_id = get_user_meta($user_id, 'profile_image', true);
    $upload_error = get_user_meta($user_id, 'profile_image_error', true);

    // Display error if exists
    if ($upload_error) {
        wc_print_notice($upload_error, 'error');
    }

    ?>
    <div class="profile-picture-section">
        <div class="profile-image-container" id="profile-image-container">
            <?php if ($attachment_id && wp_attachment_is_image($attachment_id)): ?>
                <?php echo wp_get_attachment_image($attachment_id, 'thumbnail', false, array(
                    'class' => 'profile-picture',
                    'id' => 'profile-picture',
                    'alt' => 'Profile Picture'
                )); ?>
            <?php else: ?>
                <div class="profile-image-placeholder" id="profile-image-placeholder">
                    <span class="image-icon">Upload Image</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-info-container">
            <label for="image-upload" class="profile-picture-title">Profile Picture</label>
            <div class="profile-picture-instructions">PNG or JPEG, max 15MB</div>
        </div>
        <p class="woocommerce-form-row form-row image-upload-field">
            <input type="file" name="profile_image" id="image-upload" accept="image/png,image/jpeg">
            <input type="hidden" name="profile_image_nonce" value="<?php echo wp_create_nonce('profile_image_upload'); ?>">
        </p>
    </div>
    <?php
}
add_action('woocommerce_edit_account_form_start', 'action_woocommerce_edit_account_form_start');

// Add enctype to form
function add_enctype_to_account_form($form)
{
    if (strpos($form, 'enctype="multipart/form-data"') === false) {
        $form = str_replace('<form', '<form enctype="multipart/form-data"', $form);
    }
    return $form;
}
add_filter('woocommerce_edit_account_form', 'add_enctype_to_account_form');

// Validate image upload
function action_woocommerce_save_account_details_errors($errors)
{
    if (isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
        // Debug: Log $_FILES to check if file is received
        error_log('$_FILES: ' . print_r($_FILES, true));

        // Verify nonce
        if (!isset($_POST['profile_image_nonce']) || !wp_verify_nonce($_POST['profile_image_nonce'], 'profile_image_upload')) {
            $errors->add('image_error', __('Security check failed.', 'woocommerce'));
            error_log('Nonce verification failed.');
            return;
        }

        // Check file size
        if ($_FILES['profile_image']['size'] > 15 * 1024 * 1024) {
            $errors->add('image_error', __('Image must be under 15MB.', 'woocommerce'));
            return;
        }

        // Check file type
        $allowed_types = array('image/jpeg', 'image/png');
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors->add('image_error', __('Only PNG or JPEG images are allowed.', 'woocommerce'));
        }
    }
}
add_action('woocommerce_save_account_details_errors', 'action_woocommerce_save_account_details_errors', 10, 1);

// Save image
function action_woocommerce_save_account_details($user_id)
{
    if (isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
        // Debug: Log save attempt
        error_log('Attempting to save profile image for user ' . $user_id);

        // Verify nonce
        if (!isset($_POST['profile_image_nonce']) || !wp_verify_nonce($_POST['profile_image_nonce'], 'profile_image_upload')) {
            update_user_meta($user_id, 'profile_image_error', __('Security check failed.', 'woocommerce'));
            error_log('Nonce verification failed during save.');
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('profile_image', 0);
        if (is_wp_error($attachment_id)) {
            $error_message = $attachment_id->get_error_message();
            update_user_meta($user_id, 'profile_image_error', $error_message);
            error_log('Image upload error: ' . $error_message);
        } else {
            // Delete previous image
            $previous_image_id = get_user_meta($user_id, 'profile_image', true);
            if ($previous_image_id) {
                wp_delete_attachment($previous_image_id, true);
            }
            update_user_meta($user_id, 'profile_image', $attachment_id);
            delete_user_meta($user_id, 'profile_image_error');
            error_log('Image saved successfully: Attachment ID ' . $attachment_id);
        }
    }
}
add_action('woocommerce_save_account_details', 'action_woocommerce_save_account_details', 10, 1);

add_action('wp_enqueue_scripts', 'pl_override_dokan_spmv_add_to_store_for_vendor', 30);
function pl_override_dokan_spmv_add_to_store_for_vendor()
{

    // Only on Dokan seller dashboard (front end)
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    wp_enqueue_script('jquery');

    $nonce = wp_create_nonce('pl_vendor_assign_nonce');
    $ajaxurl = admin_url('admin-ajax.php');
    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_woocommerce') ? '1' : '0';

    $inline_js = "
    jQuery(function($){

        var plNonce   = '{$nonce}';
        var plAjaxUrl = '{$ajaxurl}';
        var plUserId  = {$user_id};
        var plIsAdmin = {$is_admin} === 1; // bool

        console.log('[PL-SPMV] init → nonce:', plNonce, ' ajaxurl:', plAjaxUrl, ' isAdmin:', plIsAdmin, ' userId:', plUserId);

        // Remove Dokan's original handler on the SPMV 'Add To Store' button
        $(document).off('click', '.dokan-spmv-clone-product');

        // Attach our handler: call pl_assign_vendors
        $(document).on('click', '.dokan-spmv-clone-product', function(e){
            e.preventDefault();

            var \$btn     = $(this);
            var productId = \$btn.data('product');

            console.log('[PL-SPMV] Button clicked → productId:', productId);

            if (!productId) {
                console.error('[PL-SPMV] No data-product attribute found.');
                alert('Debug: Missing product ID on button.');
                return;
            }

            var originalText = \$btn.text();
            \$btn.prop('disabled', true).text('Assigning...');

            // Base payload: always send product_ids
            var payload = {
                action:      'pl_assign_vendors',
                nonce:       plNonce,
                product_ids: [ productId ]
            };

            // IMPORTANT:
            // - For vendors: plugin ignores vendor_ids and uses current user.
            // - For admins: plugin REQUIRES vendor_ids, so we send current admin ID.
            if (plIsAdmin) {
                payload.vendor_ids = [ plUserId ];
            }

            console.log('[PL-SPMV] Sending AJAX payload:', payload);

            $.ajax({
                url:      plAjaxUrl,
                method:   'POST',
                data:     payload,
                dataType: 'json'
            })
            .done(function(resp){
                console.log('[PL-SPMV] AJAX success response:', resp);

                if (resp && resp.success) {
                    var msg = resp.data && resp.data.message ? resp.data.message : 'Assigned successfully.';
                    alert(msg);
                    \$btn.text('Assigned').addClass('pl-assigned');
                } else {
                    var msg = (resp && resp.data && resp.data.message)
                        ? resp.data.message
                        : 'Unknown logical failure.';
                    console.error('[PL-SPMV] Logical failure:', resp);
                    alert('Error: ' + msg);
                    \$btn.prop('disabled', false).text(originalText);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown){
                console.error('[PL-SPMV] AJAX error:', {
                    status:       jqXHR.status,
                    statusText:   jqXHR.statusText,
                    responseText: jqXHR.responseText,
                    textStatus:   textStatus,
                    errorThrown:  errorThrown
                });

                alert(
                    'AJAX ERROR ' + jqXHR.status + '\\n' +
                    'Status: ' + textStatus + '\\n' +
                    'Response: ' + jqXHR.responseText
                );

                \$btn.prop('disabled', false).text(originalText);
            });

        });

    });
    ";

    wp_add_inline_script('jquery', $inline_js);
}





/**
 * Add unread message count to Elementor icon
 * Works with "Orders Chat for WooCommerce" plugin
 */

// add_action('woocommerce_order_status_processing', 'printlana_send_order_to_oto', 10, 1);

// function printlana_send_order_to_oto($order_id)
// {
//     $order = wc_get_order($order_id);
//     if (!$order) {
//         return;
//     }

//     // 🔐 Your OTO API endpoint + key – replace with real data
//     $api_url = 'https://api.oto.com/v1/shipments'; // example
//     $api_key = 'YOUR_OTO_API_KEY';

//     // Build payload for OTO (very simplified example)
//     $payload = [
//         'order_id' => $order->get_id(),
//         'reference' => $order->get_order_number(),
//         'recipient' => [
//             'name' => $order->get_formatted_billing_full_name(),
//             'phone' => $order->get_billing_phone(),
//             'email' => $order->get_billing_email(),
//             'address' => $order->get_billing_address_1(),
//             // add city, country, etc...
//         ],
//         // items, COD, etc...
//     ];

//     $response = wp_remote_post($api_url, [
//         'headers' => [
//             'Content-Type' => 'application/json',
//             'Authorization' => 'Bearer ' . $api_key,
//         ],
//         'body' => wp_json_encode($payload),
//         'timeout' => 30,
//     ]);

//     if (is_wp_error($response)) {
//         $order->add_order_note('OTO API error: ' . $response->get_error_message());
//         return;
//     }

//     $body = json_decode(wp_remote_retrieve_body($response), true);

//     // 👇 THIS is the part where we "replace sample data with real response"
//     //   You need to match these keys to OTO's actual API response fields.
//     //   Use error_log(print_r($body, true)); to see the real structure.
//     $tracking_number = isset($body['tracking_number']) ? sanitize_text_field($body['tracking_number']) : '';
//     $tracking_link = isset($body['tracking_url']) ? esc_url_raw($body['tracking_url']) : '';

//     if ($tracking_number && $tracking_link) {
//         $order->update_meta_data('_oto_tracking_number', $tracking_number);
//         $order->update_meta_data('_oto_tracking_link', $tracking_link);
//         $order->save();

//         $order->add_order_note('OTO shipment created. Tracking: ' . $tracking_link);
//     } else {
//         $order->add_order_note('OTO API: no tracking info returned. Raw response saved in log.');
//         error_log('OTO API response (no tracking): ' . print_r($body, true));
//     }
// }



// Add notification badge to Elementor icon
add_action('wp_footer', 'add_message_notification_to_elementor_icon');
function add_message_notification_to_elementor_icon()
{
    if (!is_user_logged_in()) {
        return;
    }

    $unread_messages_count = 0;
    try {
        $container = (new \ReflectionClass('\U2Code\OrderMessenger\Frontend\AccountManager'))
            ->getMethod('getContainer')
            ->invoke(new \U2Code\OrderMessenger\Frontend\AccountManager());

        $message_repository = $container->getMessageRepository();
        $unread_messages_count = (int) $message_repository->getUnreadMessagesCountForUser(get_current_user_id());
    } catch (\Exception $e) {
        $unread_messages_count = 0;
    }

    if ($unread_messages_count <= 0) {
        return;
    }

    $icon_selector = '.elementor-menu-notification__notification_button';

    ?>
    <script>
        jQuery(document).ready(function ($) {
            var $icon = $('<?php echo esc_js($icon_selector); ?>');

            if ($icon.length) {
                $icon.find('.elementor-message-notification').remove();

                $icon.append('<span class="elementor-message-notification"><?php echo esc_js($unread_messages_count); ?></span>');
            }
        });
    </script>
    <?php
}

// Ajax handler for updating unread message count in real-time
add_action('wp_ajax_update_message_notification_count', 'update_message_notification_count');
function update_message_notification_count()
{
    // Verify nonce for security (optional but recommended)
    check_ajax_referer('message_notification_nonce', 'nonce');

    $unread_messages_count = 0;
    try {
        $container = (new \ReflectionClass('\U2Code\OrderMessenger\Frontend\AccountManager'))
            ->getMethod('getContainer')
            ->invoke(new \U2Code\OrderMessenger\Frontend\AccountManager());

        $message_repository = $container->getMessageRepository();
        $unread_messages_count = (int) $message_repository->getUnreadMessagesCountForUser(get_current_user_id());
    } catch (\Exception $e) {
        $unread_messages_count = 0;
    }

    wp_send_json_success(['count' => $unread_messages_count]);
}



/**
 * Filter by price custom query
 */

// Hook into Elementor query for custom price filter
add_action('elementor/query/custom_price_filter', function ($query) {
    if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
        $min_price = floatval($_GET['min_price']);
        $max_price = floatval($_GET['max_price']);

        $meta_query = $query->get('meta_query') ?: [];

        $meta_query[] = [
            'key' => '_price',
            'value' => [$min_price, $max_price],
            'compare' => 'BETWEEN',
            'type' => 'NUMERIC'
        ];

        $query->set('meta_query', $meta_query);
    }
});


add_filter('wpc_posts_query_args', function ($args) {
    // Apply the same price filter logic that Elementor uses
    if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
        $min_price = floatval($_GET['min_price']);
        $max_price = floatval($_GET['max_price']);

        // Initialize meta_query if it doesn't exist
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }

        // Add the same price filter logic
        $args['meta_query'][] = [
            'key' => '_price',
            'value' => [$min_price, $max_price],
            'compare' => 'BETWEEN',
            'type' => 'NUMERIC'
        ];

        // Ensure we're dealing with the right post type
        $args['post_type'] = 'product';
    }

    return $args;
});


add_filter('woocommerce_currency_symbol', 'sar_currency_inline_from_media', 20, 2);
function sar_currency_inline_from_media($currency_symbol, $currency)
{
    if ($currency === 'SAR' && !is_admin() && !is_page(11) && !is_page(4119)) {
        // Return inline SVG with appropriate styling
        $svg = '<span class="sar-currency-svg" style="display: inline-block; width: 1em; height: 1.2em; vertical-align: middle; margin-right: 0.2em;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1124.14 1256.39" style="width: 100%; height: 100%;">
                <path fill="currentColor" d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z"/>
                <path fill="currentColor" d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z"/>
            </svg>
        </span>';
        return $svg;
    }
    return $currency_symbol;
}
/**
 * Adjust price format for SAR currency
 */
add_filter('woocommerce_price_format', 'sar_price_format', 20, 2);
function sar_price_format($format, $currency)
{
    if ($currency === 'SAR' && !is_admin() && !is_page(11) && !is_page(4119)) {
        return '%2$s%1$s'; // Symbol before price (e.g., [SVG]100)
        // Use '%1$s%2$s' for symbol after price (e.g., 100[SVG])
    }
    return $format;
}

/**
 * My Account Custom Navigation Order
 */

add_filter('woocommerce_account_menu_items', 'custom_reorder_my_account_menu');

function custom_reorder_my_account_menu($items)
{
    // echo '<pre>'; print_r( $items ); echo '</pre>';

    // Define your custom order
    return array(
        'dashboard' => __('Dashboard', 'woocommerce'),
        'my-wish-list' => __('Favorites', 'woocommerce'),
        'edit-account' => __('Account', 'woocommerce'),
        'orders' => __('Orders', 'woocommerce'),
        'payment-methods' => __('Payment Information', 'woocommerce'),
        'edit-address' => __('Addresses', 'woocommerce'),
    );
}


/**
 * Shortcode for WooCommerce registration form
 */
function custom_woocommerce_register_form_shortcode()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return '<p>' . esc_html__('WooCommerce is not active.', 'woocommerce') . '</p>';
    }

    // Start output buffering
    ob_start();
    ?>

    <div class="woocommerce-register-form" id="account_registration-form">

        <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>

            <?php do_action('woocommerce_register_form_start'); ?>

            <?php if ('no' === get_option('woocommerce_registration_generate_username')): ?>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="reg_username">
                        <?php esc_html_e('Username', 'woocommerce'); ?>
                        <span class="required" aria-hidden="true">*</span>
                        <span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                    </label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username"
                        id="reg_username" autocomplete="username"
                        value="<?php echo (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
                        required aria-required="true" />
                </p>
            <?php endif; ?>

            <!-- First Name -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"
                style="flex: calc(50% - 20px);min-width: 220px;">
                <label for="reg_first_name">
                    <?php esc_html_e('First Name', 'woocommerce'); ?>
                    <span class="required" aria-hidden="true">*</span>
                    <span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name"
                    id="reg_first_name" autocomplete="given-name"
                    value="<?php echo (!empty($_POST['first_name'])) ? esc_attr(wp_unslash($_POST['first_name'])) : ''; ?>"
                    required aria-required="true" />
            </p>

            <!-- Family Name -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"
                style="flex: calc(50% - 20px);min-width: 220px;">
                <label for="reg_last_name">
                    <?php esc_html_e('Family Name', 'woocommerce'); ?>
                    <span class="required" aria-hidden="true">*</span>
                    <span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="last_name"
                    id="reg_last_name" autocomplete="family-name"
                    value="<?php echo (!empty($_POST['last_name'])) ? esc_attr(wp_unslash($_POST['last_name'])) : ''; ?>"
                    required aria-required="true" />
            </p>

            <!-- Account Type (Radio) -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"
                style="display: flex;flex-wrap: wrap;    column-gap: 40px;">
                <label style="flex:100%;"><?php esc_html_e('Account Type', 'woocommerce'); ?> <span class="required"
                        aria-hidden="true">*</span></label>
                <?php
                $account_type = !empty($_POST['account_type']) ? sanitize_text_field(wp_unslash($_POST['account_type'])) : 'individual';
                ?>
                <label class="woocommerce-form__label woocommerce-form__label-for-radio wapf-radio"
                    style="gap: 4px;flex: calc(50% - 20px);display: flex !important;align-items: center;">
                    <input type="radio" name="account_type" value="individual" <?php checked($account_type, 'individual'); ?> />
                    <span class="wapf-custom"></span>
                    <span><?php esc_html_e('Individual', 'woocommerce'); ?></span>
                </label>
                <label class="woocommerce-form__label woocommerce-form__label-for-radio wapf-radio"
                    style="gap: 4px;flex: calc(50% - 20px);display: flex !important;align-items: center;">
                    <input type="radio" name="account_type" value="company" <?php checked($account_type, 'company'); ?> />
                    <span class="wapf-custom"></span>
                    <span><?php esc_html_e('Company', 'woocommerce'); ?></span>
                </label>
            </p>

            <!-- Sector (Dropdown) -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"
                style="flex: calc(50% - 20px);min-width: 220px;">
                <label for="reg_sector">
                    <?php esc_html_e('Sector', 'woocommerce'); ?>
                </label>
                <?php
                $sector = !empty($_POST['sector']) ? sanitize_text_field(wp_unslash($_POST['sector'])) : '';
                ?>
                <select name="sector" id="reg_sector" class="woocommerce-Input woocommerce-Input--select input-select">
                    <option value=""><?php esc_html_e('Select sector', 'woocommerce'); ?></option>
                    <option value="restaurant_cafe" <?php selected($sector, 'restaurant_cafe'); ?>>
                        <?php esc_html_e('Restaurant / Café', 'woocommerce'); ?>
                    </option>
                    <option value="bakery_sweets" <?php selected($sector, 'bakery_sweets'); ?>>
                        <?php esc_html_e('Bakery / Sweets', 'woocommerce'); ?>
                    </option>
                    <option value="hotel_catering" <?php selected($sector, 'hotel_catering'); ?>>
                        <?php esc_html_e('Hotel / Catering', 'woocommerce'); ?>
                    </option>
                    <option value="corporate" <?php selected($sector, 'corporate'); ?>>
                        <?php esc_html_e('Corporate / Office', 'woocommerce'); ?>
                    </option>
                    <option value="other" <?php selected($sector, 'other'); ?>>
                        <?php esc_html_e('Other', 'woocommerce'); ?>
                    </option>
                </select>
            </p>

            <!-- Company Name (optional / required for company) -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"
                style="flex: calc(50% - 20px);min-width: 220px;">
                <label for="reg_company_name">
                    <?php esc_html_e('Company Name', 'woocommerce'); ?>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="company_name"
                    id="reg_company_name"
                    value="<?php echo (!empty($_POST['company_name'])) ? esc_attr(wp_unslash($_POST['company_name'])) : ''; ?>"
                    placeholder="<?php esc_attr_e('Enter company name', 'woocommerce'); ?>" />
            </p>

            <!-- Email -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_email">
                    <?php esc_html_e('Email address', 'woocommerce'); ?>
                    <span class="required" aria-hidden="true">*</span>
                    <span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                </label>
                <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email"
                    autocomplete="email"
                    value="<?php echo (!empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" required
                    aria-required="true" />
            </p>

            <!-- Phone Number -->
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_phone">
                    <?php esc_html_e('Phone Number', 'woocommerce'); ?>
                    <span class="required" aria-hidden="true">*</span>
                    <span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                </label>
                <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="phone" id="reg_phone"
                    autocomplete="tel"
                    value="<?php echo (!empty($_POST['phone'])) ? esc_attr(wp_unslash($_POST['phone'])) : ''; ?>"
                    placeholder="<?php esc_attr_e('Enter your phone number', 'woocommerce'); ?>" required
                    aria-required="true" />
            </p>

            <?php if ('no' === get_option('woocommerce_registration_generate_password')): ?>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="reg_password">
                        <?php esc_html_e('Create Password', 'woocommerce'); ?>
                        <span class="required" aria-hidden="true">*</span>
                        <span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                    </label>
                    <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password"
                        id="reg_password" autocomplete="new-password" required aria-required="true"
                        placeholder="<?php esc_attr_e('Enter password', 'woocommerce'); ?>" />
                </p>
            <?php else: ?>
                <p><?php esc_html_e('A link to set a new password will be sent to your email address.', 'woocommerce'); ?></p>
            <?php endif; ?>

            <!-- Terms & Conditions -->
            <p class="woocommerce-form-row form-row">
                <?php
                $terms_checked = !empty($_POST['terms']);
                $terms_page_id = wc_get_page_id('terms');
                $terms_url = $terms_page_id > 0 ? get_permalink($terms_page_id) : '#';
                ?>
                <label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
                    <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="terms"
                        id="reg_terms" value="1" <?php checked($terms_checked, true); ?> />
                    <span>
                        <?php esc_html_e('I agree to the terms and conditions', 'woocommerce'); ?>
                        <?php if ($terms_page_id > 0): ?>
                            (<a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('View terms', 'woocommerce'); ?>
                            </a>)
                        <?php endif; ?>
                    </span>
                    <span class="required" aria-hidden="true">*</span>
                </label>
            </p>


            <p class="woocommerce-form-row form-row">
                <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                <button type="submit"
                    class="woocommerce-Button woocommerce-button button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?> woocommerce-form-register__submit"
                    name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>">
                    <?php esc_html_e('Register', 'woocommerce'); ?>
                </button>
            </p>

            <?php do_action('woocommerce_register_form_end'); ?>

        </form>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('custom_woocommerce_register', 'custom_woocommerce_register_form_shortcode');



// Move Checkout payment section under Billing Adress Form

remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
add_action('woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 10);

/**
 *  Override Elementor Pro cart fragments
 */

add_filter('woocommerce_add_to_cart_fragments', 'update_cart_count_fragment');
function update_cart_count_fragment($fragments)
{
    if (class_exists('WooCommerce') && WC()->cart) {
        $unique_count = count(WC()->cart->get_cart());

        // Override the default cart count fragment
        $fragments['.elementor-button-icon-qty'] = '<span class="elementor-button-icon-qty" data-counter="' . $unique_count . '">' . $unique_count . '</span>';

        // Also target common cart counter selectors
        $fragments['.cart-count'] = '<span class="cart-count">' . $unique_count . '</span>';
        $fragments['.elementor-menu-cart__toggle .elementor-button-text'] = '<span class="elementor-button-text">' . $unique_count . '</span>';
    }

    return $fragments;
}

// Initial load script (simplified)
add_action('wp_footer', function () {
    if (class_exists('WooCommerce') && WC()->cart) {
        $unique_count = count(WC()->cart->get_cart());
        ?>
        <script>
            jQuery(document).ready(function ($) {
                // Set initial count
                $('.elementor-button-icon-qty').attr('data-counter', '<?php echo $unique_count; ?>').text('<?php echo $unique_count; ?>');
            });
        </script>
        <?php
    }
});



function custom_dokan_dashboard_logo()
{
    ?>
    <div class="dokan-dashboard-logo">
        <a href="<?php echo esc_url(home_url()); ?>">
            <?php
            if (function_exists('the_custom_logo') && has_custom_logo()) {
                the_custom_logo(); // Use WP custom logo if set
            } else {
                // fallback to site title
                bloginfo('name');
            }
            ?>
        </a>
    </div>
    <?php
}
add_action('dokan_dashboard_sidebar_start', 'custom_dokan_dashboard_logo', 5);

/**
 * Redirect users after login based on their role
 */
add_filter('login_redirect', 'custom_redirect_vendors_after_login', 10, 3);

function custom_redirect_vendors_after_login($redirect_to, $request, $user)
{
    if (isset($user->roles) && is_array($user->roles)) {
        // Check if user is a Dokan vendor
        $is_vendor = in_array('seller', $user->roles) ||
            in_array('vendor', $user->roles) ||
            user_can($user->ID, 'dokandar') ||
            get_user_meta($user->ID, 'dokan_enable_selling', true) === 'yes';

        if ($is_vendor && class_exists('WeDevs_Dokan')) {
            // Redirect vendors to the Dokan dashboard
            return dokan_get_page_url('dashboard', 'dokan');
        } else {
            // Redirect non-vendors to the My Account page
            return 'https://printlana.com/my-account/';
        }
    }

    // Fallback to home page if user roles are not set
    return home_url();
}

/**
 * Redirect vendors away from the My Account page to the Dokan dashboard
 */
add_action('template_redirect', 'custom_redirect_vendors_from_my_account');

function custom_redirect_vendors_from_my_account()
{
    // Only proceed if user is logged in
    if (!is_user_logged_in()) {
        return;
    }

    // Get the current user
    $user = wp_get_current_user();

    // Check if user is a Dokan vendor
    $is_vendor = in_array('seller', $user->roles) ||
        in_array('vendor', $user->roles) ||
        user_can($user->ID, 'dokandar') ||
        get_user_meta($user->ID, 'dokan_enable_selling', true) === 'yes';

    if (!$is_vendor) {
        return; // Non-vendors can access the My Account page
    }

    // Check if the current page is the My Account page
    if (is_page('my-account') || is_wc_endpoint_url()) {
        // Avoid redirecting if already on the Dokan dashboard
        $dashboard_page_id = dokan_get_option('dashboard', 'dokan_pages');
        if ($dashboard_page_id && is_page($dashboard_page_id)) {
            return;
        }

        // Redirect vendors to the Dokan dashboard
        if (class_exists('WeDevs_Dokan')) {
            wp_safe_redirect(dokan_get_page_url('dashboard', 'dokan'));
            exit;
        }
    }
}


/**
 * Simple Login Form Validation - Add to functions.php
 * No custom widgets needed - works with existing Elementor Login widget
 */

// Main validation class
class Simple_Login_Validator
{

    public function __construct()
    {
        add_action('init', [$this, 'init_hooks']);
    }

    public function init_hooks()
    {
        // Hook into WordPress login validation
        add_filter('authenticate', [$this, 'validate_login_form'], 30, 3);

        // Add JavaScript to identify form type
        add_action('wp_footer', [$this, 'add_form_detection_script']);

        // Handle login errors
        add_action('wp_login_failed', [$this, 'handle_login_error']);

        // Display error messages
        add_action('wp_footer', [$this, 'display_error_messages']);
    }

    /**
     * Main validation function - checks user type during login
     */
    public function validate_login_form($user, $username, $password)
    {
        // Skip if already error or empty credentials
        if (is_wp_error($user) || empty($username) || empty($password)) {
            return $user;
        }

        // Check if form type is specified (added by our JavaScript)
        if (!isset($_POST['form_type'])) {
            return $user; // No form type = allow normal login
        }

        $form_type = sanitize_text_field($_POST['form_type']);

        // Get user by username or email
        $user_obj = get_user_by('login', $username);
        if (!$user_obj) {
            $user_obj = get_user_by('email', $username);
        }

        if (!$user_obj) {
            return $user; // Let WordPress handle invalid user
        }

        // Check if user type matches form
        $is_vendor = $this->is_vendor($user_obj);

        if ($form_type === 'vendor' && !$is_vendor) {
            return new WP_Error(
                'wrong_form_type',
                'This login form is for vendors only. Please use the customer login form.'
            );
        }

        if ($form_type === 'customer' && $is_vendor) {
            return new WP_Error(
                'wrong_form_type',
                'This login form is for customers only. Please use the vendor login form.'
            );
        }

        return $user;
    }

    /**
     * Check if user is a vendor
     */
    private function is_vendor($user)
    {
        // Check user roles
        $vendor_roles = ['vendor', 'shop_manager', 'administrator'];
        if (array_intersect($vendor_roles, $user->roles)) {
            return true;
        }

        // Check user meta
        if (get_user_meta($user->ID, 'is_vendor', true)) {
            return true;
        }

        // WooCommerce vendor plugins compatibility
        if (function_exists('dokan_is_user_vendor') && dokan_is_user_vendor($user->ID)) {
            return true;
        }

        if (class_exists('WCMp') && function_exists('is_user_mvx_vendor') && is_user_mvx_vendor($user->ID)) {
            return true;
        }

        if (function_exists('wcfm_is_vendor') && wcfm_is_vendor($user->ID)) {
            return true;
        }

        return false;
    }

    /**
     * Add JavaScript to detect form type based on CSS classes
     */
    public function add_form_detection_script()
    {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Find all Elementor login forms
                var loginForms = document.querySelectorAll('.elementor-login form');

                loginForms.forEach(function (form) {
                    var formType = '';

                    // Check if form is in vendor container
                    if (form.closest('.supplier-login') || form.closest('.vendor-login')) {
                        formType = 'vendor';
                    }
                    // Check if form is in customer container  
                    else if (form.closest('.customer-login')) {
                        formType = 'customer';
                    }

                    // Add hidden field if form type detected
                    if (formType) {
                        var hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = 'form_type';
                        hiddenField.value = formType;
                        form.appendChild(hiddenField);
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Handle login errors and redirect with error message
     */
    public function handle_login_error($username)
    {
        $referrer = wp_get_referer();

        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login_error', '1', $referrer));
            exit;
        }
    }


    /**
     * Display error messages on the page
     */
    public function display_error_messages()
    {
        if (!isset($_GET['login_error'])) {
            return;
        }
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var loginForms = document.querySelectorAll('.elementor-login form');

                loginForms.forEach(function (form) {
                    var errorDiv = document.createElement('div');
                    errorDiv.className = 'login-error-message';
                    errorDiv.innerHTML = 'Please check your credentials and ensure you\'re using the correct login form for your account type.';
                    errorDiv.style.cssText = 'background:#f8d7da;color:#721c24;padding:12px;margin-bottom:15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;';

                    form.insertBefore(errorDiv, form.firstChild);
                });

                // Remove error parameter from URL
                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location);
                    url.searchParams.delete('login_error');
                    window.history.replaceState({}, document.title, url);
                }
            });
        </script>
        <?php
    }
}

// Initialize the validator
new Simple_Login_Validator();
