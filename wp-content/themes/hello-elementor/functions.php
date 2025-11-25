<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('HELLO_ELEMENTOR_VERSION', '3.4.4');
define('EHP_THEME_SLUG', 'hello-elementor');

define('HELLO_THEME_PATH', get_template_directory());
define('HELLO_THEME_URL', get_template_directory_uri());
define('HELLO_THEME_ASSETS_PATH', HELLO_THEME_PATH . '/assets/');
define('HELLO_THEME_ASSETS_URL', HELLO_THEME_URL . '/assets/');
define('HELLO_THEME_SCRIPTS_PATH', HELLO_THEME_ASSETS_PATH . 'js/');
define('HELLO_THEME_SCRIPTS_URL', HELLO_THEME_ASSETS_URL . 'js/');
define('HELLO_THEME_STYLE_PATH', HELLO_THEME_ASSETS_PATH . 'css/');
define('HELLO_THEME_STYLE_URL', HELLO_THEME_ASSETS_URL . 'css/');
define('HELLO_THEME_IMAGES_PATH', HELLO_THEME_ASSETS_PATH . 'images/');
define('HELLO_THEME_IMAGES_URL', HELLO_THEME_ASSETS_URL . 'images/');


if (!isset($content_width)) {
    $content_width = 800; // Pixels.
}

if (!function_exists('hello_elementor_setup')) {
    /**
     * Set up theme support.
     *
     * @return void
     */
    function hello_elementor_setup()
    {
        if (is_admin()) {
            hello_maybe_update_theme_version_in_db();
        }

        if (apply_filters('hello_elementor_register_menus', true)) {
            register_nav_menus(['menu-1' => esc_html__('Header', 'hello-elementor')]);
            register_nav_menus(['menu-2' => esc_html__('Footer', 'hello-elementor')]);
        }

        if (apply_filters('hello_elementor_post_type_support', true)) {
            add_post_type_support('page', 'excerpt');
        }

        if (apply_filters('hello_elementor_add_theme_support', true)) {
            add_theme_support('post-thumbnails');
            add_theme_support('automatic-feed-links');
            add_theme_support('title-tag');
            add_theme_support(
                'html5',
                [
                    'search-form',
                    'comment-form',
                    'comment-list',
                    'gallery',
                    'caption',
                    'script',
                    'style',
                    'navigation-widgets',
                ]
            );
            add_theme_support(
                'custom-logo',
                [
                    'height' => 100,
                    'width' => 350,
                    'flex-height' => true,
                    'flex-width' => true,
                ]
            );
            add_theme_support('align-wide');
            add_theme_support('responsive-embeds');

            /*
             * Editor Styles
             */
            add_theme_support('editor-styles');
            add_editor_style('editor-styles.css');

            /*
             * WooCommerce.
             */
            if (apply_filters('hello_elementor_add_woocommerce_support', true)) {
                // WooCommerce in general.
                add_theme_support('woocommerce');
                // Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
                // zoom.
                add_theme_support('wc-product-gallery-zoom');
                // lightbox.
                add_theme_support('wc-product-gallery-lightbox');
                // swipe.
                add_theme_support('wc-product-gallery-slider');
            }
        }
    }
}
add_action('after_setup_theme', 'hello_elementor_setup');

function hello_maybe_update_theme_version_in_db()
{
    $theme_version_option_name = 'hello_theme_version';
    // The theme version saved in the database.
    $hello_theme_db_version = get_option($theme_version_option_name);

    // If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
    if (!$hello_theme_db_version || version_compare($hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<')) {
        update_option($theme_version_option_name, HELLO_ELEMENTOR_VERSION);
    }
}

if (!function_exists('hello_elementor_display_header_footer')) {
    /**
     * Check whether to display header footer.
     *
     * @return bool
     */
    function hello_elementor_display_header_footer()
    {
        $hello_elementor_header_footer = true;

        return apply_filters('hello_elementor_header_footer', $hello_elementor_header_footer);
    }
}

add_filter('elementor/widget/render_content', function ($content, $widget) {
    if (method_exists($widget, 'get_name') && 'hotspot' === $widget->get_name()) {
        $settings = $widget->get_settings_for_display();

        // Normalize repeater items (hotspots)
        if (!empty($settings['hotspot_items']) && is_array($settings['hotspot_items'])) {
            foreach ($settings['hotspot_items'] as $i => $item) {
                if (!isset($settings['hotspot_items'][$i]['hotspot_offset_x'])) {
                    $settings['hotspot_items'][$i]['hotspot_offset_x'] = 50; // default %
                }
                if (!isset($settings['hotspot_items'][$i]['hotspot_offset_y'])) {
                    $settings['hotspot_items'][$i]['hotspot_offset_y'] = 50; // default %
                }
            }

            // Re-inject normalized settings before render
            $widget->set_settings('hotspot_items', $settings['hotspot_items']);
        }
    }
    return $content;
}, 10, 2);

// removed unused CSS Files

add_action('wp_enqueue_scripts', function () {

    // If the current user is NOT an admin, remove the Orders Chat CSS
    if (!current_user_can('manage_options')) {
        wp_dequeue_style('dashicons');
        wp_deregister_style('dashicons');
    }
    wp_dequeue_style('dokan-pro-shipping-blocks');
    wp_deregister_style('dokan-pro-shipping-blocks');
    wp_dequeue_style('orders-chat-frontend');
    wp_deregister_style('orders-chat-frontend');

}, 100);

/**--------------------------------------------**/


add_action('after_setup_theme', function () {
    // Add a new custom image size
    add_image_size('Hero Section', 480, 480, true);
});


/**
 * Return [product_label, product_url] for a Dokan sub-order that has one product.
 * Label example: "Pizza Box — Size: Large — SKU: PZB-001 × 200"
 */
function pl_suborder_primary_product(WC_Order $order)
{

    foreach ($order->get_items('line_item') as $item_id => $item) {

        $name = $item->get_name();                   // item name (includes parent product name)
        $qty = (int) $item->get_quantity();
        $product = $item->get_product();
        $sku = $product ? $product->get_sku() : '';

        // ------------------------------------------------------------------
        // Variation attributes
        // ------------------------------------------------------------------
        $variation_text = '';

        if ($product && $product->is_type('variation')) {
            // Case 1: we have the variation product object
            $variation_text = wc_get_formatted_variation(
                $product->get_variation_attributes(),
                true
            );

        } else {
            // Case 2: try to build variation attributes from order item meta
            // (this avoids the fatal `get_variation_attributes()` call)
            $meta_attributes = [];

            foreach ($item->get_meta_data() as $meta) {
                $data = $meta->get_data(); // ['id' => ..., 'key' => ..., 'value' => ...]
                if (empty($data['key']) || strpos($data['key'], 'attribute_') !== 0) {
                    continue;
                }

                // Remove the 'attribute_' prefix WooCommerce uses
                $attr_name = substr($data['key'], 10);
                $attr_value = $data['value'];

                $meta_attributes[$attr_name] = $attr_value;
            }

            if (!empty($meta_attributes)) {
                $variation_text = wc_get_formatted_variation($meta_attributes, true);
            }
        }

        // ------------------------------------------------------------------
        // Build label
        // ------------------------------------------------------------------
        $parts = array_filter([
            $name,
            $variation_text ?: null,
            $sku ? 'SKU: ' . $sku : null,
        ]);

        $label = implode(' — ', $parts) . ' × ' . $qty;
        $url = ($product && $product->get_id()) ? get_permalink($product->get_id()) : '';

        // "Primary product" → just return the first line item
        return [$label, $url];
    }

    return [__('(no product)', 'your-textdomain'), ''];
}


add_filter('manage_edit-product_columns', 'add_vendor_id_product_column');
function add_vendor_id_product_column($columns)
{
    // We will add our new column right after the 'price' column for better visibility.
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'price') {
            // Add the 'Vendor ID' column
            // The key 'vendor_id' is a unique identifier for our column.
            // The value is the display text for the column header.
            $new_columns['vendor_id'] = __('Vendor ID', 'your-text-domain');
        }
    }
    return $new_columns;
}

add_action('admin_head', 'custom_vendor_id_column_width');
function custom_vendor_id_column_width()
{
    // We only want this CSS to apply on the WooCommerce product list page.
    $screen = get_current_screen();
    if ($screen && $screen->id === 'edit-product') {
        echo '<style>
            .column-vendor_id {
                width: 10% !important; /* Adjust the width as you need - e.g., 150px or 10% */
            }
        </style>';
    }
}


/**
 * Display the data for the custom 'Vendor ID' column.
 *
 * This function hooks into 'manage_product_posts_custom_column' to populate our new column with data.
 * It fetches the post meta with the key '_assigned_vendor_ids'.
 *
 * @param string $column  The key of the current column being displayed.
 * @param int    $post_id The ID of the product (post).
 */
add_action('manage_product_posts_custom_column', 'display_vendor_id_column_data', 10, 2);
function display_vendor_id_column_data($column, $post_id)
{
    // Check if it's our custom column by matching the key 'vendor_id'.
    if ($column === 'vendor_id') {
        // Get the custom field value using the specified meta key.
        $vendor_ids = get_post_meta($post_id, '_assigned_vendor_ids', true);

        // Check if the retrieved data is not empty.
        if (!empty($vendor_ids)) {
            // The meta value could be an array of IDs or a single ID.
            // We'll handle both cases to be safe.
            if (is_array($vendor_ids)) {
                // If it's an array, convert it to a comma-separated string.
                echo esc_html(implode(', ', $vendor_ids));
            } else {
                // If it's a single value, just display it.
                echo esc_html($vendor_ids);
            }
        } else {
            // Display a dash '—' if the meta field is empty or doesn't exist.
            echo '—';
        }
    }
}




/**
 * Smart order-status notifications (Dokan sub-orders only).
 *
 * - If vendor changed a SUB-ORDER: notify Customer + Admin.
 * - If admin changed a SUB-ORDER: notify Customer + Vendor.
 *
 * Put in a small MU plugin or your child theme's functions.php.
 */

// Add vendors as BCC on WooCommerce "New Order" email (admin email).
add_filter('woocommerce_email_headers', function ($headers, $email_id, $order) {

    if ($email_id !== 'new_order' || !$order instanceof WC_Order) {
        return $headers;
    }

    if ((int) $order->get_parent_id() > 0) {
        return $headers;
    }

    // Collect vendor emails from items
    $vendor_emails = [];

    foreach ($order->get_items('line_item') as $item_id => $item) {
        $product = $item->get_product();
        if (!$product)
            continue;

        $product_id = $product->get_id();

        // Get vendor/user ID (Dokan sets product author as vendor)
        $vendor_id = (int) get_post_field('post_author', $product_id);
        if (!$vendor_id)
            continue;

        // Prefer Dokan store email if set
        if (function_exists('dokan')) {
            $vendor = dokan()->vendor->get($vendor_id);
            if ($vendor) {
                $store_email = $vendor->get_email(); // pulls store email, falls back to user email
                if ($store_email) {
                    $vendor_emails[] = sanitize_email($store_email);
                    continue;
                }
            }
        }

        // Fallback to WP user email
        $user = get_user_by('id', $vendor_id);
        if ($user && !empty($user->user_email)) {
            $vendor_emails[] = sanitize_email($user->user_email);
        }
    }

    $vendor_emails = array_filter(array_unique($vendor_emails));

    if (!empty($vendor_emails)) {
        // Append BCC header(s). WooCommerce accepts \r\n separated headers.
        $bcc_line = 'Bcc: ' . implode(',', $vendor_emails) . "\r\n";
        $headers .= $bcc_line;
    }

    return $headers;
}, 10, 3);



if (!function_exists('hello_elementor_scripts_styles')) {
    /**
     * Theme Scripts & Styles.
     *
     * @return void
     */
    function hello_elementor_scripts_styles()
    {
        if (apply_filters('hello_elementor_enqueue_style', true)) {
            wp_enqueue_style(
                'hello-elementor',
                HELLO_THEME_STYLE_URL . 'reset.css',
                [],
                HELLO_ELEMENTOR_VERSION
            );
        }

        if (apply_filters('hello_elementor_enqueue_theme_style', true)) {
            wp_enqueue_style(
                'hello-elementor-theme-style',
                HELLO_THEME_STYLE_URL . 'theme.css',
                [],
                HELLO_ELEMENTOR_VERSION
            );
        }

        if (hello_elementor_display_header_footer()) {
            wp_enqueue_style(
                'hello-elementor-header-footer',
                HELLO_THEME_STYLE_URL . 'header-footer.css',
                [],
                HELLO_ELEMENTOR_VERSION
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'hello_elementor_scripts_styles');

if (!function_exists('hello_elementor_register_elementor_locations')) {
    /**
     * Register Elementor Locations.
     *
     * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
     *
     * @return void
     */
    function hello_elementor_register_elementor_locations($elementor_theme_manager)
    {
        if (apply_filters('hello_elementor_register_elementor_locations', true)) {
            $elementor_theme_manager->register_all_core_location();
        }
    }
}
add_action('elementor/theme/register_locations', 'hello_elementor_register_elementor_locations');

if (!function_exists('hello_elementor_content_width')) {
    /**
     * Set default content width.
     *
     * @return void
     */
    function hello_elementor_content_width()
    {
        $GLOBALS['content_width'] = apply_filters('hello_elementor_content_width', 800);
    }
}
add_action('after_setup_theme', 'hello_elementor_content_width', 0);

if (!function_exists('hello_elementor_add_description_meta_tag')) {
    /**
     * Add description meta tag with excerpt text.
     *
     * @return void
     */
    function hello_elementor_add_description_meta_tag()
    {
        if (!apply_filters('hello_elementor_description_meta_tag', true)) {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $post = get_queried_object();
        if (empty($post->post_excerpt)) {
            return;
        }

        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($post->post_excerpt)) . '">' . "\n";
    }
}
add_action('wp_head', 'hello_elementor_add_description_meta_tag');

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if (!function_exists('hello_elementor_customizer')) {
    // Customizer controls
    function hello_elementor_customizer()
    {
        if (!is_customize_preview()) {
            return;
        }

        if (!hello_elementor_display_header_footer()) {
            return;
        }

        require get_template_directory() . '/includes/customizer-functions.php';
    }
}

// Add a new meta box for extra product image
add_action('add_meta_boxes', 'custom_product_extra_image_metabox');
function custom_product_extra_image_metabox()
{
    add_meta_box(
        'custom_product_extra_image',
        'Product Home Image',
        'custom_product_extra_image_callback',
        'product',
        'side',
        'default'
    );
}

function custom_product_extra_image_callback($post)
{
    wp_nonce_field('custom_product_extra_image_nonce', 'custom_product_extra_image_nonce');

    $image_id = (int) get_post_meta($post->ID, '_custom_product_extra_image', true);
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    ?>
    <div>
        <input type="hidden" id="custom_product_extra_image" name="custom_product_extra_image"
            value="<?php echo esc_attr($image_id); ?>" />
        <img id="custom_product_extra_image_preview" src="<?php echo esc_url($image_url); ?>"
            style="max-width:100%; margin-bottom:10px; <?php echo $image_url ? '' : 'display:none;'; ?>" />
        <br />
        <button type="button" class="button upload_image_button">Upload Image</button>
        <button type="button" class="button remove_image_button"
            style="<?php echo $image_url ? '' : 'display:none;'; ?>">Remove Image</button>
    </div>

    <script>
        jQuery(function ($) {
            var frame;
            $('.upload_image_button').on('click', function (e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Select or Upload Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#custom_product_extra_image').val(attachment.id);
                    $('#custom_product_extra_image_preview').attr('src', attachment.url).show();
                    $('.remove_image_button').show();
                });
                frame.open();
            });
            $('.remove_image_button').on('click', function () {
                $('#custom_product_extra_image').val('');
                $('#custom_product_extra_image_preview').hide().attr('src', '');
                $(this).hide();
            });
        });
    </script>
    <?php
}

// Enqueue the WP media frame on product edit screens
add_action('admin_enqueue_scripts', function ($hook) {
    if (($hook === 'post.php' || $hook === 'post-new.php') && get_post_type() === 'product') {
        wp_enqueue_media();
    }
});

// Save meta
add_action('save_post_product', function ($post_id) {
    if (
        !isset($_POST['custom_product_extra_image_nonce']) ||
        !wp_verify_nonce($_POST['custom_product_extra_image_nonce'], 'custom_product_extra_image_nonce')
    ) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    if (isset($_POST['custom_product_extra_image'])) {
        update_post_meta($post_id, '_custom_product_extra_image', absint($_POST['custom_product_extra_image']));
    }
});

add_action('save_post', 'save_custom_product_extra_image');
function save_custom_product_extra_image($post_id)
{
    error_log('Nonce received: ' . (isset($_POST['custom_product_extra_image_nonce']) ? $_POST['custom_product_extra_image_nonce'] : 'NONE'));

    if (!isset($_POST['custom_product_extra_image_nonce']) || !wp_verify_nonce($_POST['custom_product_extra_image_nonce'], basename(__FILE__))) {
        error_log('Nonce failed validation!');
        return;
    } else {
        error_log('Nonce validation passed!');
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // ✅ DEBUG: Check if the custom image field exists in POST
    if (isset($_POST['custom_product_extra_image'])) {
        error_log('POST custom_product_extra_image: ' . $_POST['custom_product_extra_image']);

        update_post_meta($post_id, '_custom_product_extra_image', sanitize_text_field($_POST['custom_product_extra_image']));
        error_log('Image saved with ID: ' . $_POST['custom_product_extra_image']);
    } else {
        error_log('POST custom_product_extra_image is NOT set!');
    }
}




add_action('woocommerce_product_thumbnails', 'display_custom_image_under_main', 20);
function display_custom_image_under_main()
{
    global $post;

    // Get custom image ID from post meta
    $image_id = get_post_meta($post->ID, '_custom_product_extra_image', true);

    // If an image is uploaded, display it
    if ($image_id) {
        echo '<div class="custom-extra-product-image" style="margin-top:15px;">';
        echo wp_get_attachment_image($image_id, 'woocommerce_single', false, array(
            'class' => 'woocommerce-product-gallery__image'
        ));
        echo '</div>';
    }
}

// Virtual meta for Elementor "Post Custom Field" (key: price_quantity_combo)
add_filter('get_post_metadata', function ($value, $object_id, $meta_key, $single) {
    if ($meta_key !== 'price_quantity_combo') {
        return $value;
    }

    $min_qty = (int) get_post_meta($object_id, 'min_quantity', true);
    if ($min_qty <= 0) {
        $min_qty = 1;
    }

    $price = function_exists('wc_get_product') && ($p = wc_get_product($object_id))
        ? (float) $p->get_price()
        : (float) get_post_meta($object_id, '_price', true);

    $total = $price * $min_qty;

    // Pull Woo formatting info
    $symbol = get_woocommerce_currency_symbol();
    $dec = wc_get_price_decimals();
    $thou = wc_get_price_thousand_separator();
    $dot = wc_get_price_decimal_separator();
    $pos = get_option('woocommerce_currency_pos', 'left');

    $amount = number_format($total, $dec, $dot, $thou);

    // Respect Woo’s currency position
    switch ($pos) {
        case 'left':
            $combo = $symbol . $amount;
            break;
        case 'right':
            $combo = $amount . $symbol;
            break;
        case 'left_space':
            $combo = $symbol . ' ' . $amount;
            break;
        case 'right_space':
            $combo = $amount . ' ' . $symbol;
            break;
        default:
            $combo = $symbol . $amount;
    }

    return $single ? $combo : [$combo];
}, 10, 4);





/**
 * Register Custom Elementor Dynamic Tags
 */
add_action('elementor/init', function () {
    // Exit if Elementor is not active
    if (!defined('ELEMENTOR_VERSION')) {
        return;
    }

    // Define and register the custom tag
    add_action('elementor/dynamic_tags/register', function ($dynamic_tags_manager) {

        // Ensure necessary Elementor classes are available
        if (!class_exists('Elementor\Core\DynamicTags\Tag') || !class_exists('Elementor\Modules\DynamicTags\Module')) {
            return;
        }

        class Custom_Product_Extra_Image_Tag extends \Elementor\Core\DynamicTags\Tag
        {

            public function get_name()
            {
                return 'custom_product_extra_image';
            }

            public function get_title()
            {
                return __('Product Home Image', 'your-textdomain');
            }

            public function get_group()
            {
                return 'woocommerce';
            }

            public function get_categories()
            {
                return [\Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY];
            }

            public function get_value(array $options = [])
            {
                $post_id = get_the_ID();
                if (!$post_id)
                    return [];

                $image_id = (int) get_post_meta($post_id, '_custom_product_extra_image', true);
                if (!$image_id)
                    return [];

                $url = wp_get_attachment_image_url($image_id, 'full');
                if (!$url)
                    return [];

                return [
                    'id' => $image_id,
                    'url' => $url,
                ];
            }
        }

        // Register the tag
        $dynamic_tags_manager->register(new Custom_Product_Extra_Image_Tag());
    });
});



add_action('init', 'hello_elementor_customizer');

if (!function_exists('hello_elementor_check_hide_title')) {
    /**
     * Check whether to display the page title.
     *
     * @param bool $val default value.
     *
     * @return bool
     */
    function hello_elementor_check_hide_title($val)
    {
        if (defined('ELEMENTOR_VERSION')) {
            $current_doc = Elementor\Plugin::instance()->documents->get(get_the_ID());
            if ($current_doc && 'yes' === $current_doc->get_settings('hide_title')) {
                $val = false;
            }
        }
        return $val;
    }
}
add_filter('hello_elementor_page_title', 'hello_elementor_check_hide_title');

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if (!function_exists('hello_elementor_body_open')) {
    function hello_elementor_body_open()
    {
        wp_body_open();
    }
}

require HELLO_THEME_PATH . '/theme.php';

HelloTheme\Theme::instance();