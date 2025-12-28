<?php



// ------------------------------


// testing cart issue start
add_action('woocommerce_before_calculate_totals', 'fix_wapf_qty_formula', 999);

function fix_wapf_qty_formula($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (did_action('woocommerce_before_calculate_totals') >= 2) return;
    
    foreach ($cart->get_cart() as $cart_item) {
        if (!isset($cart_item['wapf'])) continue;
        
        $current_price = $cart_item['data']->get_price();
        $qty = $cart_item['quantity'];
        
        if ($qty <= 0) continue;
        
        $adjustment = 0;
        $found_qty_formula = false;
        
        // Find the formula field with [qty]
        foreach ($cart_item['wapf'] as $field) {
            if (empty($field['values'])) continue;
            
            foreach ($field['values'] as $value) {
                if (!isset($value['price_type']) || !isset($value['price'])) continue;
                
                if ($value['price_type'] === 'fx') {
                    $formula = $value['price'];
                    
                    // Only adjust formulas with [qty] in them
                    if (strpos($formula, '[qty]') !== false) {
                        $found_qty_formula = true;
                        
                        // Parse the formula to calculate the result
                        if (preg_match('/\[price\.(\w+)\]/', $formula, $matches)) {
                            $ref_id = $matches[1];
                            
                            $divisor = 0;
                            foreach ($cart_item['wapf'] as $ref_field) {
                                if ($ref_field['id'] === $ref_id && !empty($ref_field['values'])) {
                                    $divisor = floatval($ref_field['values'][0]['price']);
                                    break;
                                }
                            }
                            
                            if ($divisor > 0) {
                                // Formula result (what WAPF calculated)
                                $formula_result = $qty / $divisor;
                                
                                // Adjustment needed
                                $adjustment = $formula_result - ($formula_result / $qty);
                                
                                error_log("WAPF Fix Applied: Qty=$qty, Divisor=$divisor, Formula=$formula_result, Adjustment=$adjustment");
                            } else {
                                error_log("WAPF: Cannot apply fix - divisor is zero or not found");
                            }
                        }
                    }
                }
            }
        }
        
        if ($found_qty_formula && $adjustment > 0) {
            $new_price = $current_price - $adjustment;
            $cart_item['data']->set_price($new_price);
            
            error_log("Price adjusted from $current_price to $new_price (removed $adjustment)");
        } elseif ($found_qty_formula) {
            error_log("WAPF: Found qty formula but adjustment=$adjustment (skipped)");
        }
    }
}


add_action('woocommerce_before_calculate_totals', 'debug_cart_prices', 10, 1);

function debug_cart_prices($cart) {
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        error_log('Product: ' . $cart_item['data']->get_name());
        error_log('Price: ' . $cart_item['data']->get_price());
        error_log('Quantity: ' . $cart_item['quantity']);
        error_log('Custom Data: ' . print_r($cart_item, true));
    }
}

// testing cart issue end

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


add_action('init', function () {
    // Ensure Administrator has full WooCommerce Analytics access
    if ($admin = get_role('administrator')) {
        $admin->add_cap('view_woocommerce_reports');
        $admin->add_cap('view_woocommerce_analytics'); // newer WC versions
    }
    // Optional: also ensure Shop Manager has them
    if ($manager = get_role('shop_manager')) {
        $manager->add_cap('view_woocommerce_reports');
        $manager->add_cap('view_woocommerce_analytics');
    }
    // Optional: also ensure Shop Manager has them
    if ($manager = get_role('vendor')) {
        $manager->add_cap('view_woocommerce_reports');
        $manager->add_cap('view_woocommerce_analytics');
    }
    // Optional: also ensure Shop Manager has them
    if ($manager = get_role('seller')) {
        $manager->add_cap('view_woocommerce_reports');
        $manager->add_cap('view_woocommerce_analytics');
    }
    // Optional: also ensure Shop Manager has them
    if ($manager = get_role('dokan_seller')) {
        $manager->add_cap('view_woocommerce_reports');
        $manager->add_cap('view_woocommerce_analytics');
    }
});

add_action('init', function () {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        error_log(
            '[Analytics Cap Check] User ' . $user_id . ' role caps: reports=' .
            (current_user_can('view_woocommerce_reports') ? 'yes' : 'no') .
            ' analytics=' .
            (current_user_can('view_woocommerce_analytics') ? 'yes' : 'no')
        );
    }
});

add_action('wp_enqueue_scripts', function () {
    if (is_account_page()) {
        wp_enqueue_script(
            'pl-saudi-phone-validation',
            get_stylesheet_directory_uri() . '/assets/js/saudi-phone.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
});


/**
 * Fix WooCommerce Analytics REST API authentication for vendor dashboard
 * Add REST nonce to wpApiSettings for frontend vendor dashboard analytics
 */
add_action('wp_enqueue_scripts', 'pl_add_rest_nonce_for_vendor_analytics', 999);
function pl_add_rest_nonce_for_vendor_analytics()
{
    // Only run on Dokan seller dashboard
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    // Only for logged-in users with analytics capabilities
    if (!is_user_logged_in() || !current_user_can('view_woocommerce_analytics')) {
        return;
    }

    // Create nonce for REST API
    $nonce = wp_create_nonce('wp_rest');
    $rest_root = esc_url_raw(rest_url());

    // Add inline script to set up API fetch with nonce BEFORE WooCommerce scripts run
    wp_add_inline_script(
        'wp-api-fetch',
        "
        (function() {
            console.log('[PL Analytics] Setting up REST API authentication');
            console.log('[PL Analytics] REST Root:', '{$rest_root}');
            console.log('[PL Analytics] Nonce:', '{$nonce}');

            // Set wpApiSettings if it doesn't exist
            if (typeof wpApiSettings === 'undefined') {
                window.wpApiSettings = {
                    root: '{$rest_root}',
                    nonce: '{$nonce}',
                    versionString: 'wp/v2/'
                };
                console.log('[PL Analytics] Created wpApiSettings:', wpApiSettings);
            } else {
                // Update existing wpApiSettings
                wpApiSettings.nonce = '{$nonce}';
                wpApiSettings.root = '{$rest_root}';
                console.log('[PL Analytics] Updated wpApiSettings:', wpApiSettings);
            }

            // Also set wcSettings for WooCommerce
            if (typeof wcSettings === 'undefined') {
                window.wcSettings = {};
            }
            if (typeof wcSettings.admin === 'undefined') {
                wcSettings.admin = {};
            }
            wcSettings.admin.nonce = '{$nonce}';
            wcSettings.admin.root = '{$rest_root}';

            console.log('[PL Analytics] WC Settings:', wcSettings);
        })();
        ",
        'before'
    );
}

/**
 * Add REST nonce via wp_head as a fallback
 * This ensures the nonce is available even if wp-api-fetch loads late
 */
add_action('wp_head', 'pl_add_rest_nonce_inline', 1);
function pl_add_rest_nonce_inline()
{
    // Only run on Dokan seller dashboard
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    // Only for logged-in users with analytics capabilities
    if (!is_user_logged_in() || !current_user_can('view_woocommerce_analytics')) {
        return;
    }

    $nonce = wp_create_nonce('wp_rest');
    $rest_root = esc_url_raw(rest_url());
    ?>
    <script>
        console.log('[PL Analytics Early] Initializing REST API settings in HEAD');

        // Initialize wpApiSettings early
        window.wpApiSettings = {
            root: '<?php echo $rest_root; ?>',
            nonce: '<?php echo $nonce; ?>',
            versionString: 'wp/v2/'
        };

        // Initialize wcSettings early
        window.wcSettings = window.wcSettings || {};
        window.wcSettings.admin = window.wcSettings.admin || {};
        window.wcSettings.admin.nonce = '<?php echo $nonce; ?>';
        window.wcSettings.admin.root = '<?php echo $rest_root; ?>';

        console.log('[PL Analytics Early] wpApiSettings:', window.wpApiSettings);
        console.log('[PL Analytics Early] wcSettings.admin:', window.wcSettings.admin);

        // Intercept all XMLHttpRequest calls to wc-analytics and add nonce header
        (function () {
            var XHR = XMLHttpRequest.prototype;
            var open = XHR.open;
            var send = XHR.send;
            var setRequestHeader = XHR.setRequestHeader;

            XHR.open = function (method, url) {
                this._url = url;
                return open.apply(this, arguments);
            };

            XHR.setRequestHeader = function (header, value) {
                this._headers = this._headers || {};
                this._headers[header] = value;
                return setRequestHeader.apply(this, arguments);
            };

            XHR.send = function (postData) {
                // Check if this is a request to wc-analytics or wc-admin API
                if (this._url && (this._url.indexOf('/wc-analytics/') !== -1 || this._url.indexOf('/wc-admin/') !== -1 || this._url.indexOf('/wc/') !== -1)) {
                    console.log('[PL Analytics] Intercepting API request:', this._url);

                    // Add nonce header if not already present
                    if (!this._headers || !this._headers['X-WP-Nonce']) {
                        console.log('[PL Analytics] Adding X-WP-Nonce header:', '<?php echo $nonce; ?>');
                        setRequestHeader.call(this, 'X-WP-Nonce', '<?php echo $nonce; ?>');
                    }
                }
                return send.apply(this, arguments);
            };

            console.log('[PL Analytics] XHR interceptor installed');
        })();

        // Also intercept fetch API calls
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function (url, options) {
                const urlString = typeof url === 'string' ? url : url.url;

                // Check if this is a WooCommerce API request
                if (urlString && (urlString.indexOf('/wc-analytics/') !== -1 || urlString.indexOf('/wc-admin/') !== -1 || urlString.indexOf('/wc/') !== -1)) {
                    console.log('[PL Analytics] Intercepting fetch request:', urlString);

                    options = options || {};
                    options.headers = options.headers || {};

                    // Add nonce if not present
                    if (!options.headers['X-WP-Nonce']) {
                        console.log('[PL Analytics] Adding X-WP-Nonce to fetch:', '<?php echo $nonce; ?>');
                        options.headers['X-WP-Nonce'] = '<?php echo $nonce; ?>';
                    }

                    // Ensure credentials are included
                    options.credentials = options.credentials || 'include';
                }

                return originalFetch(url, options);
            };
            console.log('[PL Analytics] Fetch interceptor installed');
        }
    </script>
    <?php
}




add_action('save_post_product', function ($post_id, $post, $update) {
    // Only run for new products
    if ($update) {
        return;
    }

    // Prevent infinite loops
    static $processing = false;
    if ($processing) {
        return;
    }
    $processing = true;

    $product = wc_get_product($post_id);
    if (!$product) {
        $processing = false;
        return;
    }

    // === SKU GENERATION ===
    if (!$product->get_sku()) {
        $prefix = 'P126';
        $number_length = 5;

        // Get the highest SKU number with this prefix (excluding current product)
        global $wpdb;
        $highest_sku = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_sku'
            AND meta_value LIKE %s
            AND post_id != %d
            ORDER BY meta_value DESC
            LIMIT 1",
            $prefix . '%',
            $post_id
        ));

        if ($highest_sku && strpos($highest_sku, $prefix) === 0) {
            // Extract number from SKU
            $number_part = str_replace($prefix, '', $highest_sku);
            $new_number = intval($number_part) + 1;
        } else {
            // No existing SKU found, start from 1
            $new_number = 1;
        }

        // Generate new SKU with leading zeros
        $new_sku = $prefix . str_pad($new_number, $number_length, '0', STR_PAD_LEFT);

        // Set SKU
        update_post_meta($post_id, '_sku', $new_sku);
        error_log('[Product Init] SKU set: ' . $new_sku);
    }

    // === INITIALIZE WOOCOMMERCE PRODUCT META ===
    // These are essential WooCommerce fields that must exist

    // Price fields - initialize to empty if not set
    if (!metadata_exists('post', $post_id, '_regular_price')) {
        update_post_meta($post_id, '_regular_price', '');
        update_post_meta($post_id, '_price', '');
        error_log('[Product Init] Price fields initialized');
    }

    // Stock/Inventory fields
    if (!metadata_exists('post', $post_id, '_manage_stock')) {
        update_post_meta($post_id, '_manage_stock', 'no');
        update_post_meta($post_id, '_stock_status', 'instock');
        update_post_meta($post_id, '_stock', '');
        error_log('[Product Init] Stock fields initialized');
    }

    // Visibility and featured
    if (!metadata_exists('post', $post_id, '_visibility')) {
        update_post_meta($post_id, '_visibility', 'visible');
        update_post_meta($post_id, '_featured', 'no');
    }

    // Tax and shipping
    if (!metadata_exists('post', $post_id, '_tax_status')) {
        update_post_meta($post_id, '_tax_status', 'taxable');
        update_post_meta($post_id, '_tax_class', '');
    }

    // Virtual and downloadable
    if (!metadata_exists('post', $post_id, '_virtual')) {
        update_post_meta($post_id, '_virtual', 'no');
        update_post_meta($post_id, '_downloadable', 'no');
    }

    // Clear WooCommerce cache
    wc_delete_product_transients($post_id);

    $processing = false;
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

/**
 * Helper function: Check if a product is already assigned to a specific vendor
 * Uses the wp_pl_product_vendors table
 */
function pl_is_product_assigned_to_vendor($product_id, $vendor_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'pl_product_vendors';

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE product_id = %d AND vendor_id = %d",
            $product_id,
            $vendor_id
        )
    );

    return $count > 0;
}

add_action('wp_enqueue_scripts', 'pl_dequeue_dokan_spmv_scripts', 999);
function pl_dequeue_dokan_spmv_scripts()
{
    // Only on Dokan seller dashboard (front end)
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    // Dequeue Dokan's product search script to prevent conflicts
    wp_dequeue_script('dokan-spmv-product-search');
    wp_deregister_script('dokan-spmv-product-search');
}

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
    $vendor_request_nonce = wp_create_nonce('pl_vendor_request');
    $ajaxurl = admin_url('admin-ajax.php');
    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_woocommerce') ? '1' : '0';

    $inline_js = "
    (function(){
        if(!window.location.href.includes('products-search')) {
            return; // Skip on list page
        }

        var plNonce   = '{$nonce}';
        var plVendorRequestNonce = '{$vendor_request_nonce}';
        var plAjaxUrl = '{$ajaxurl}';
        var plUserId  = {$user_id};
        var plIsAdmin = {$is_admin} === 1; // bool

        // Make vendor request nonce available globally for the request plugin
        if (typeof plVendorRequests === 'undefined') {
            window.plVendorRequests = {
                ajaxurl: plAjaxUrl,
                nonce: plVendorRequestNonce
            };
        }

        console.log('[PL-SPMV] Script loaded immediately');
        console.log('[PL-SPMV] init → nonce:', plNonce, ' vendorRequestNonce:', plVendorRequestNonce, ' ajaxurl:', plAjaxUrl, ' isAdmin:', plIsAdmin, ' userId:', plUserId);

        // Wait for jQuery to be available
        function initWhenReady() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initWhenReady, 50);
                return;
            }

            var $ = jQuery;
            console.log('[PL-SPMV] jQuery ready, initializing...');

            // Remove Dokan's original handler immediately
            $(document).off('click', '.dokan-spmv-clone-product');

        /**
         * Check which products are already assigned and update button states
         */
        function checkAssignedProducts() {
            // Find all 'Add To Store' buttons (excluding 'Already Cloned' and 'Edit' buttons)
            var \$buttons = $('.dokan-spmv-clone-product');

            if (\$buttons.length === 0) {
                console.log('[PL-SPMV] No Add To Store buttons found.');
                return;
            }

            // Collect product IDs
            var productIds = [];
            \$buttons.each(function(){
                var productId = $(this).data('product');
                if (productId) {
                    productIds.push(productId);
                }
            });

            if (productIds.length === 0) {
                console.log('[PL-SPMV] No product IDs found on buttons.');
                return;
            }

            console.log('[PL-SPMV] Checking assignment status for products:', productIds);

            // For vendors: Check both assigned products AND request status
            if (!plIsAdmin) {
                // First check assigned products
                $.ajax({
                    url: plAjaxUrl,
                    method: 'POST',
                    data: {
                        action: 'pl_check_assigned_products',
                        nonce: plNonce,
                        product_ids: productIds
                    },
                    dataType: 'json',
                    timeout: 5000
                })
                .done(function(resp){
                    console.log('[PL-SPMV] Assigned products response:', resp);

                    if (resp && resp.success && resp.data && resp.data.assigned_products) {
                        var assignedProducts = resp.data.assigned_products;

                        // Mark assigned products
                        \$buttons.each(function(){
                            var \$btn = $(this);
                            var productId = \$btn.data('product');

                            if (assignedProducts.indexOf(productId) !== -1) {
                                \$btn.text('Added')
                                    .addClass('pl-assigned')
                                    .prop('disabled', true);
                                console.log('[PL-SPMV] Product ' + productId + ' marked as Added');
                            }
                        });
                    }

                    // Then check request status for non-assigned products
                    checkRequestStatus(productIds);
                })
                .fail(function(jqXHR){
                    console.error('[PL-SPMV] Assigned check error:', jqXHR.responseText);
                    // Still check request status even if assigned check fails
                    checkRequestStatus(productIds);
                });
            } else {
                // For admins: Only check assigned products
                $.ajax({
                    url: plAjaxUrl,
                    method: 'POST',
                    data: {
                        action: 'pl_check_assigned_products',
                        nonce: plNonce,
                        product_ids: productIds
                    },
                    dataType: 'json',
                    timeout: 5000
                })
                .done(function(resp){
                    console.log('[PL-SPMV] Admin assigned check:', resp);

                    if (resp && resp.success && resp.data && resp.data.assigned_products) {
                        var assignedProducts = resp.data.assigned_products;

                        \$buttons.each(function(){
                            var \$btn = $(this);
                            var productId = \$btn.data('product');

                            if (assignedProducts.indexOf(productId) !== -1) {
                                \$btn.text('Added')
                                    .addClass('pl-assigned')
                                    .prop('disabled', true);
                            }
                        });
                    }
                })
                .fail(function(jqXHR){
                    console.error('[PL-SPMV] Admin check error:', jqXHR.responseText);
                });
            }
        }

        /**
         * Check request status for vendor (pending/approved requests)
         */
        function checkRequestStatus(productIds) {
            console.log('[PL-SPMV] Checking request status for products:', productIds);

            $.ajax({
                url: plAjaxUrl,
                method: 'POST',
                data: {
                    action: 'pl_check_request_status',
                    nonce: plVendorRequestNonce,
                    product_ids: productIds
                },
                dataType: 'json',
                timeout: 5000
            })
            .done(function(resp){
                console.log('[PL-SPMV] Request status response:', resp);

                if (resp && resp.success && resp.data) {
                    var pendingProducts = resp.data.pending_products || [];
                    var approvedProducts = resp.data.approved_products || [];

                    var \$buttons = $('.dokan-spmv-clone-product');
                    \$buttons.each(function(){
                        var \$btn = $(this);
                        var productId = \$btn.data('product');

                        // Skip if already marked as assigned
                        if (\$btn.hasClass('pl-assigned')) {
                            return;
                        }

                        if (pendingProducts.indexOf(productId) !== -1) {
                            \$btn.text('Request Pending')
                                .addClass('pl-requested')
                                .prop('disabled', true);
                            console.log('[PL-SPMV] Product ' + productId + ' has pending request');
                        } else if (approvedProducts.indexOf(productId) !== -1) {
                            \$btn.text('Request Approved')
                                .addClass('pl-approved')
                                .prop('disabled', true);
                            console.log('[PL-SPMV] Product ' + productId + ' has approved request');
                        }
                    });
                }
            })
            .fail(function(jqXHR){
                console.error('[PL-SPMV] Request status check error:', jqXHR.responseText);
            });
        }

        // Run check when buttons are available
        function waitForButtonsAndCheck() {
            var \$buttons = $('.dokan-spmv-clone-product');
            if (\$buttons.length > 0) {
                console.log('[PL-SPMV] Buttons found, checking assignments...');
                checkAssignedProducts();
            } else {
                console.log('[PL-SPMV] Buttons not ready yet, waiting...');
                setTimeout(waitForButtonsAndCheck, 100);
            }
        }

        waitForButtonsAndCheck();

        // Attach our handler: vendors use request system, admins use direct assignment
        $(document).on('click', '.dokan-spmv-clone-product', function(e){
            e.preventDefault();
            e.stopImmediatePropagation(); // Prevent Dokan's handler from running

            var \$btn     = $(this);
            var productId = \$btn.data('product');

            console.log('[PL-SPMV] Button clicked → productId:', productId, ' isAdmin:', plIsAdmin);

            if (!productId) {
                console.error('[PL-SPMV] No data-product attribute found.');
                alert('Debug: Missing product ID on button.');
                return;
            }

            // Check if already marked as added or requested
            if (\$btn.hasClass('pl-assigned') || \$btn.hasClass('pl-requested')) {
                console.log('[PL-SPMV] Product already assigned/requested, ignoring click.');
                return;
            }

            var originalText = \$btn.text();
            \$btn.prop('disabled', true).text('Processing...');

            // Different endpoints for vendors vs admins
            var payload, actionName;

            if (plIsAdmin) {
                // Admins use the assignment endpoint
                actionName = 'pl_assign_vendors';
                payload = {
                    action:      actionName,
                    nonce:       plNonce,
                    product_ids: [ productId ],
                    vendor_ids:  [ plUserId ]
                };
            } else {
                // Vendors use the request endpoint (from printlana-vendor-product-requests plugin)
                actionName = 'pl_request_to_sell_product';

                // Get the vendor request nonce (will be created by the request plugin)
                var vendorNonce = typeof plVendorRequests !== 'undefined' ? plVendorRequests.nonce : plNonce;

                payload = {
                    action:      actionName,
                    nonce:       vendorNonce,
                    product_id:  productId
                };
            }

            console.log('[PL-SPMV] Sending AJAX to:', actionName, ' with payload:', payload);

            $.ajax({
                url:      plAjaxUrl,
                method:   'POST',
                data:     payload,
                dataType: 'json'
            })
            .done(function(resp){
                console.log('[PL-SPMV] AJAX success response:', resp);

                if (resp && resp.success) {
                    var data = resp.data || {};

                    if (plIsAdmin) {
                        // Admin successfully assigned product
                        var msg = data.message ? data.message : 'Product assigned successfully.';
                        alert(msg);
                        \$btn.text('Added').addClass('pl-assigned');
                    } else {
                        // Vendor successfully sent request
                        var msg = data.message ? data.message : 'Request sent successfully!';
                        alert(msg);
                        \$btn.text('Request Pending').addClass('pl-requested').prop('disabled', true);
                    }
                } else {
                    var msg = (resp && resp.data && resp.data.message)
                        ? resp.data.message
                        : 'Unknown error occurred.';
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

                // Try to parse error message
                try {
                    var responseData = JSON.parse(jqXHR.responseText);
                    if (responseData && responseData.data && responseData.data.message) {
                        alert('Error: ' + responseData.data.message);
                        \$btn.prop('disabled', false).text(originalText);
                        return;
                    }
                } catch(e) {
                    console.error('[PL-SPMV] Failed to parse error response:', e);
                }

                alert(
                    'AJAX ERROR ' + jqXHR.status + '\\n' +
                    'Status: ' + textStatus + '\\n' +
                    'Response: ' + jqXHR.responseText
                );

                \$btn.prop('disabled', false).text(originalText);
            });

        });

        } // End initWhenReady

        // Start the initialization
        initWhenReady();

    })(); // End IIFE
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
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text hidden" name="role" id="role"
                value="customer" required aria-required="true" />

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

/**
 * Enqueue registration validation script
 */
add_action('wp_enqueue_scripts', 'enqueue_registration_validation_script');
function enqueue_registration_validation_script()
{
    // Only load on registration page
    if (is_page() || is_account_page()) {
        wp_enqueue_script(
            'registration-validation',
            get_stylesheet_directory_uri() . '/assets/js/registration-validation.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Pass AJAX URL to script
        wp_localize_script('registration-validation', 'registrationValidation', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('registration_validation_nonce')
        ]);
    }
}

/**
 * AJAX handler to check if email exists
 */
add_action('wp_ajax_check_email_exists', 'check_email_exists');
add_action('wp_ajax_nopriv_check_email_exists', 'check_email_exists');
function check_email_exists()
{
    check_ajax_referer('registration_validation_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);

    if (empty($email)) {
        wp_send_json_error(['message' => 'Email is required']);
    }

    $user = get_user_by('email', $email);

    if ($user) {
        wp_send_json_error(['message' => 'This email is already registered']);
    }

    wp_send_json_success(['message' => 'Email is available']);
}

/**
 * AJAX handler to check if phone exists
 */
add_action('wp_ajax_check_phone_exists', 'check_phone_exists');
add_action('wp_ajax_nopriv_check_phone_exists', 'check_phone_exists');
function check_phone_exists()
{
    check_ajax_referer('registration_validation_nonce', 'nonce');

    $phone = sanitize_text_field($_POST['phone']);

    if (empty($phone)) {
        wp_send_json_error(['message' => 'Phone is required']);
    }

    // Search for user with this phone number in billing_phone meta
    $users = get_users([
        'meta_key' => 'billing_phone',
        'meta_value' => $phone,
        'number' => 1
    ]);

    if (!empty($users)) {
        wp_send_json_error(['message' => 'This phone number is already registered']);
    }

    wp_send_json_success(['message' => 'Phone is available']);
}

/**
 * AJAX handler to check if company name exists
 */
add_action('wp_ajax_check_company_exists', 'check_company_exists');
add_action('wp_ajax_nopriv_check_company_exists', 'check_company_exists');
function check_company_exists()
{
    check_ajax_referer('registration_validation_nonce', 'nonce');

    $company_name = sanitize_text_field($_POST['company_name']);

    if (empty($company_name)) {
        wp_send_json_success(['message' => 'Company name is optional']);
    }

    // Search for user with this company name in billing_company meta
    $users = get_users([
        'meta_key' => 'billing_company',
        'meta_value' => $company_name,
        'number' => 1
    ]);

    if (!empty($users)) {
        wp_send_json_error(['message' => 'This company name is already registered']);
    }

    wp_send_json_success(['message' => 'Company name is available']);
}

/**
 * Validate registration fields
 */
add_action('woocommerce_register_post', 'validate_registration_extra_fields', 10, 3);
function validate_registration_extra_fields($username, $email, $validation_errors)
{
    // Validate phone number
    if (empty($_POST['phone'])) {
        $validation_errors->add('phone_error', __('Phone number is required.', 'woocommerce'));
    }

    // Validate company name if account type is company
    if (isset($_POST['account_type']) && $_POST['account_type'] === 'company') {
        if (empty($_POST['company_name'])) {
            $validation_errors->add('company_error', __('Company name is required for company accounts.', 'woocommerce'));
        } else {
            $company_name = sanitize_text_field($_POST['company_name']);

            // Check if company name already exists
            $existing_users = get_users([
                'meta_key' => 'billing_company',
                'meta_value' => $company_name,
                'number' => 1
            ]);

            if (!empty($existing_users)) {
                $validation_errors->add('company_error', __('This company name is already registered.', 'woocommerce'));
            }

            // Minimum length validation
            if (strlen($company_name) < 2) {
                $validation_errors->add('company_error', __('Company name must be at least 2 characters.', 'woocommerce'));
            }
        }
    }

    return $validation_errors;
}

/**
 * Save phone, company, account type, and sector to user meta during registration
 */
add_action('woocommerce_created_customer', 'save_registration_extra_fields');
function save_registration_extra_fields($customer_id)
{
    if (isset($_POST['phone'])) {
        $phone = sanitize_text_field($_POST['phone']);
        update_user_meta($customer_id, 'billing_phone', $phone);
    }

    if (isset($_POST['company_name']) && !empty($_POST['company_name'])) {
        $company_name = sanitize_text_field($_POST['company_name']);
        update_user_meta($customer_id, 'billing_company', $company_name);
    }

    if (isset($_POST['account_type'])) {
        $account_type = sanitize_text_field($_POST['account_type']);
        update_user_meta($customer_id, 'account_type', $account_type);
    }

    if (isset($_POST['sector']) && !empty($_POST['sector'])) {
        $sector = sanitize_text_field($_POST['sector']);
        update_user_meta($customer_id, 'sector', $sector);
    }
}


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


//Yahya Remove thumbnails
function remove_default_image_sizes($sizes)
{
    unset($sizes['thumbnail']);
    unset($sizes['medium']);
    unset($sizes['medium_large']);
    unset($sizes['large']);
    unset($sizes['1536x1536']); // For WordPress 5.3+ large size
    unset($sizes['2048x2048']); // For WordPress 5.3+ large size
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'remove_default_image_sizes');

/**
 * Fix Arabic characters in CSV exports
 * Adds UTF-8 BOM to make Excel and other programs display Arabic text correctly
 *
 * IMPORTANT: This uses output buffering to catch ALL CSV downloads and add UTF-8 BOM
 */
add_action('init', 'printlana_start_csv_buffer', 1);
function printlana_start_csv_buffer()
{
    // Only start buffering for CSV export requests to avoid interfering with other pages
    $is_export_request = (
        (isset($_GET['action']) && (
            strpos($_GET['action'], 'export') !== false ||
            strpos($_GET['action'], 'csv') !== false ||
            strpos($_GET['action'], 'download') !== false
        )) ||
        (isset($_POST['action']) && (
            strpos($_POST['action'], 'export') !== false ||
            strpos($_POST['action'], 'csv') !== false ||
            $_POST['action'] === 'withdraw_ajax_submission' // Dokan withdraw CSV export
        ))
    );

    if (!$is_export_request) {
        return; // Don't buffer non-export requests
    }

    // Start output buffering only for CSV exports
    ob_start(function ($buffer) {
        // Check if response headers indicate this is a CSV file
        $headers = headers_list();
        $is_csv = false;

        foreach ($headers as $header) {
            $header_lower = strtolower($header);
            // Check for CSV content type or CSV filename
            if (
                (strpos($header_lower, 'content-type') !== false && strpos($header_lower, 'csv') !== false) ||
                (strpos($header_lower, 'content-disposition') !== false && strpos($header_lower, '.csv') !== false)
            ) {
                $is_csv = true;
                break;
            }
        }

        // If it's a CSV and doesn't already have BOM, add it
        if ($is_csv) {
            $bom = "\xEF\xBB\xBF";
            // Check if BOM is already present
            if (substr($buffer, 0, 3) !== $bom) {
                error_log('[CSV Export] Adding UTF-8 BOM to CSV file');
                return $bom . $buffer;
            }
        }

        return $buffer;
    });
}

/**
 * Also hook into WooCommerce CSV exporter class if available
 */
add_action('plugins_loaded', 'printlana_hook_wc_csv_exporter', 20);
function printlana_hook_wc_csv_exporter()
{
    // For WooCommerce exports
    if (class_exists('WC_CSV_Exporter')) {
        add_filter('woocommerce_csv_product_import_mapping_options', 'printlana_force_utf8_encoding');
        add_filter('woocommerce_csv_product_import_mapping_default_columns', 'printlana_force_utf8_encoding');
    }

    // Hook into the actual file generation
    add_action('woocommerce_product_export_start', function () {
        if (!headers_sent()) {
            error_log('[CSV Export] WooCommerce export started');
        }
    });
}

function printlana_force_utf8_encoding($data)
{
    // This ensures data is in UTF-8
    if (is_array($data)) {
        array_walk_recursive($data, function (&$item) {
            if (is_string($item)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            }
        });
    }
    return $data;
}

/**
 * Enable WordPress debug logging (only in development)
 * IMPORTANT: Disable this on production for better performance
 */
// Uncomment these lines only when debugging issues:
// if (!defined('WP_DEBUG')) {
//     define('WP_DEBUG', true);
// }
// if (!defined('WP_DEBUG_LOG')) {
//     define('WP_DEBUG_LOG', true);
// }
// if (!defined('WP_DEBUG_DISPLAY')) {
//     define('WP_DEBUG_DISPLAY', false);
// }

/**
 * Fix Dokan vendor dashboard infinite loading issue
 * Dequeue conflicting scripts that interfere with Dokan Vue.js pages
 */
add_action('wp_enqueue_scripts', 'pl_fix_dokan_dashboard_loading', 999);
function pl_fix_dokan_dashboard_loading()
{
    // Only run on Dokan seller dashboard
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }

    // Dequeue ThemeHigh Multiple Addresses scripts on Dokan dashboard
    // These scripts can conflict with Dokan's Vue.js components
    wp_dequeue_script('thmaf-public');
    wp_deregister_script('thmaf-public');

    wp_dequeue_style('thmaf-public-style');
    wp_deregister_style('thmaf-public-style');

    // Enhanced debugging script
    wp_add_inline_script('jquery', '
        console.log("[PL Dashboard Fix] ThemeHigh scripts dequeued");

        // Wait for DOM to be ready
        jQuery(document).ready(function($) {
            console.log("[PL Dashboard Debug] DOM Ready");
            console.log("[PL Dashboard Debug] Current URL:", window.location.href);
            console.log("[PL Dashboard Debug] Vue loaded:", typeof Vue !== "undefined");
            console.log("[PL Dashboard Debug] Dokan loaded:", typeof dokan !== "undefined");

            // Check if page has loading indicators
            setTimeout(function() {
                const loaders = document.querySelectorAll(".dokan-loading, .spinner, .loader, [class*=loading]");
                if (loaders.length > 0) {
                    console.warn("[PL Dashboard Debug] Page still showing loaders after 3s:", loaders);
                }

                // Check if page is empty
                const contentArea = document.querySelector(".dokan-dashboard-content");
                if (contentArea && contentArea.innerHTML.trim().length < 100) {
                    console.error("[PL Dashboard Debug] Content area appears empty");
                }
            }, 3000);

            // Monitor all fetch/AJAX requests
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                const url = typeof args[0] === "string" ? args[0] : args[0].url;
                console.log("[PL Dashboard Debug] Fetch request:", url);

                return originalFetch.apply(this, args)
                    .then(response => {
                        if (!response.ok) {
                            console.error("[PL Dashboard Debug] Fetch failed:", {
                                url: url,
                                status: response.status,
                                statusText: response.statusText
                            });
                            // Clone response to read body
                            return response.clone().text().then(body => {
                                console.error("[PL Dashboard Debug] Error response body:", body);
                                return response;
                            });
                        }
                        console.log("[PL Dashboard Debug] Fetch success:", url);
                        return response;
                    })
                    .catch(error => {
                        console.error("[PL Dashboard Debug] Fetch error:", {
                            url: url,
                            error: error.message,
                            stack: error.stack
                        });
                        throw error;
                    });
            };
        });
    ');
}
