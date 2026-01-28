<?php
/**
 * Plugin Name:       Homeland
 * Description:       A custom plugin for Homepage Carousel and Highlighted Elements.
 * Version:           1.1.0
 * Author:            Yahya AlQersh
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// 1. Register Custom Post Type for Carousel Slides
function homeland_register_carousel_slide_cpt()
{
    $labels = array(
        'name' => _x('Homeland', 'Post Type General Name', 'homeland'),
        'singular_name' => _x('Carousel Slide', 'Post Type Singular Name', 'homeland'),
        'menu_name' => __('Homeland', 'homeland'),
        'name_admin_bar' => __('Carousel Slide', 'homeland'),
        'archives' => __('Slide Archives', 'homeland'),
        'attributes' => __('Slide Attributes', 'homeland'),
        'parent_item_colon' => __('Parent Slide:', 'homeland'),
        'all_items' => __('Carousel Slides', 'homeland'),
        'add_new_item' => __('Add New Slide', 'homeland'),
        'add_new' => __('Add New', 'homeland'),
        'new_item' => __('New Slide', 'homeland'),
        'edit_item' => __('Edit Slide', 'homeland'),
        'update_item' => __('Update Slide', 'homeland'),
        'view_item' => __('View Slide', 'homeland'),
        'view_items' => __('View Slides', 'homeland'),
        'search_items' => __('Search Slide', 'homeland'),
        'not_found' => __('Not found', 'homeland'),
        'not_found_in_trash' => __('Not found in Trash', 'homeland'),
        'featured_image' => __('Product Image', 'homeland'),
        'set_featured_image' => __('Set product image', 'homeland'),
        'remove_featured_image' => __('Remove product image', 'homeland'),
        'use_featured_image' => __('Use as product image', 'homeland'),
        'insert_into_item' => __('Insert into slide', 'homeland'),
        'uploaded_to_this_item' => __('Uploaded to this slide', 'homeland'),
        'items_list' => __('Slides list', 'homeland'),
        'items_list_navigation' => __('Slides list navigation', 'homeland'),
        'filter_items_list' => __('Filter slides list', 'homeland'),
    );
    $args = array(
        'label' => __('Carousel Slide', 'homeland'),
        'description' => __('Slides for the homepage carousel.', 'homeland'),
        'labels' => $labels,
        'supports' => array('title', 'thumbnail'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-home',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => true,
        'capability_type' => 'post',
        'show_in_rest' => true,
    );
    register_post_type('carousel_slide', $args);
}
add_action('init', 'homeland_register_carousel_slide_cpt', 0);

// 2. Add Meta Box for the Link
function homeland_add_link_meta_box()
{
    add_meta_box(
        'homeland_slide_link',
        'Slide Link',
        'homeland_render_link_meta_box',
        'carousel_slide',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'homeland_add_link_meta_box');

function homeland_render_link_meta_box($post)
{
    wp_nonce_field('homeland_save_link_meta_box_data', 'homeland_link_meta_box_nonce');
    $value = get_post_meta($post->ID, '_slide_link', true);
    echo '<label for="homeland_slide_link_field">Destination URL:</label> ';
    echo '<input type="url" id="homeland_slide_link_field" name="homeland_slide_link_field" value="' . esc_attr($value) . '" size="25" />';
}

function homeland_save_link_meta_box_data($post_id)
{
    if (!isset($_POST['homeland_link_meta_box_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['homeland_link_meta_box_nonce'], 'homeland_save_link_meta_box_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['homeland_slide_link_field'])) {
        return;
    }
    $my_data = sanitize_text_field($_POST['homeland_slide_link_field']);
    update_post_meta($post_id, '_slide_link', $my_data);
}
add_action('save_post', 'homeland_save_link_meta_box_data');

// 3. Register Scripts and Styles
function homeland_enqueue_assets()
{
    // GSAP from CDN
    wp_enqueue_script('gsap-cdn', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);

    // Custom CSS and JS
    wp_enqueue_style('homeland-style', plugin_dir_url(__FILE__) . 'homeland.css', array(), '1.1.0');
    wp_register_script('homeland-script', plugin_dir_url(__FILE__) . 'homeland.js', array('gsap-cdn'), '1.1.0', true);
}
add_action('wp_enqueue_scripts', 'homeland_enqueue_assets');

// 4. Settings Page for Highlighted Elements
function homeland_add_settings_page()
{
    add_submenu_page(
        'edit.php?post_type=carousel_slide',
        'Highlighted Elements',
        'Highlighted Elements',
        'manage_options',
        'homeland-highlighted',
        'homeland_render_highlighted_settings'
    );
}
add_action('admin_menu', 'homeland_add_settings_page');

function homeland_render_highlighted_settings()
{
    ?>
    <style>
        .homeland-admin-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-top: 20px;
        }

        .homeland-preview-box {
            display: flex;
            gap: 20px;
            align-items: center;
            background: #F1F5FD;
            padding: 15px;
            border-radius: 12px;
            max-width: 400px;
            margin-top: 10px;
        }

        .homeland-preview-img {
            width: 100px;
            transition: transform 0.3s;
        }

        .homeland-preview-img:hover {
            transform: scale(1.1);
        }
    </style>

    <div class="wrap" dir="rtl">
        <h1>إعدادات Homeland - العناصر المميزة (New & Hot)</h1>
        <p>استخدم هذا القسم لتخصيص كود Elementor للعناصر المميزة.</p>

        <div class="homeland-admin-card">
            <h3>معاينة العنصر (Placeholder)</h3>
            <div class="homeland-preview-box">
                <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/bag.png'; ?>" class="homeland-preview-img">
                <div>
                    <strong>Lorem Ipsum</strong>
                    <p style="font-size: 14px; color: #666; margin: 5px 0 0;">استمتع بخصومات على جميع أنواع البقالة
                        والمنتجات المجمدة</p>
                </div>
            </div>

            <hr style="margin: 20px 0;">

            <h3>تخصيص الكود (Elementor)</h3>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">العنوان الرئيسي</th>
                        <td><input type="text" id="h_title" value="Lorem Ipsum" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">النص الترويجي</th>
                        <td><textarea id="h_promo" class="regular-text"
                                rows="3">استمتع بخصومات على جميع أنواع البقالة والمنتجات المجمدة</textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="button" class="button button-primary" value="توليد الكود" onclick="generateHomelandCode()">
                </p>
            </form>

            <div id="homeland-code-result" style="display:none; margin-top: 20px;">
                <h3>انسخ هذا الكود واستخدمه في عنصر (HTML) في Elementor:</h3>
                <textarea id="homeland-textarea" style="width:100%; height:250px; font-family: monospace; direction: ltr;"
                    readonly></textarea>
                <p>
                    <button class="button button-secondary" onclick="copyHomelandCode()">نسخ الكود</button>
                </p>
            </div>
        </div>
    </div>

    <script>
        function generateHomelandCode() {
            const title = document.getElementById('h_title').value;
            const promo = document.getElementById('h_promo').value;
            const bagUrl = "<?php echo plugin_dir_url(__FILE__) . 'assets/bag.png'; ?>";
            const friesUrl = "<?php echo plugin_dir_url(__FILE__) . 'assets/fries.png'; ?>";

            const code = `
<style>
.h-container { display: flex; flex-direction: row; gap: 32px; width: 100%; max-width: 1280px; margin: 0 auto; font-family: 'Beiruti', sans-serif; }
.h-card { background: #F1F5FD; border-radius: 16px; position: relative; overflow: hidden; height: 544px; flex: 1; padding: 24px; display: flex; flex-direction: column; align-items: center; transition: box-shadow 0.3s; }
.h-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.h-img { max-width: 80%; max-height: 60%; object-fit: contain; transition: transform 0.3s; margin-top: 40px; }
.h-card:hover .h-img { transform: scale(1.05) translateY(-10px); }
.h-text { position: absolute; bottom: 40px; text-align: center; width: 100%; padding: 0 20px; direction: rtl; }
.h-title { font-size: 24px; font-weight: 600; color: #000014; }
.h-mid-col { display: flex; flex-direction: column; gap: 32px; flex: 1; }
.h-small-card { background: #F1F5FD; border-radius: 16px; height: 256px; position: relative; padding: 15px; overflow: hidden; transition: box-shadow 0.3s; }
.h-small-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.h-small-img { max-width: 50%; max-height: 60%; object-fit: contain; display: block; margin: 0 auto; transition: transform 0.3s; }
.h-small-card:hover .h-small-img { transform: scale(1.05) translateY(-5px); }
.h-small-text { text-align: center; margin-top: 10px; font-weight: 600; color: #000014; direction: rtl; }
@media (max-width: 768px) { .h-container { flex-direction: column; } .h-card { height: 400px; } }
</style>

<div class="h-container">
    <div class="h-card">
        <img src="${bagUrl}" class="h-img">
        <div class="h-text"><div class="h-title">${promo}</div></div>
    </div>
    <div class="h-mid-col">
        <div class="h-small-card">
            <img src="${bagUrl}" class="h-small-img">
            <div class="h-small-text">${promo}</div>
        </div>
        <div class="h-small-card">
            <img src="${bagUrl}" class="h-small-img">
            <div class="h-small-text">${promo}</div>
        </div>
    </div>
    <div class="h-card">
        <img src="${friesUrl}" class="h-img">
        <div class="h-text"><div class="h-title">${promo}</div></div>
    </div>
</div>
        `;

        document.getElementById('homeland-textarea').value = code.trim();
        document.getElementById('homeland-code-result').style.display = 'block';
    }

    function copyHomelandCode() {
        const copyText = document.getElementById("homeland-textarea");
        copyText.select();
        document.execCommand("copy");
        alert("تم نسخ الكود بنجاح!");
    }
</script>
<?php
}

// 5. Create the Shortcode for Carousel
function homeland_carousel_shortcode()
{
    $args = array(
        'post_type' => 'carousel_slide',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC',
    );
    $slides_query = new WP_Query($args);

    $slides_data = array();
    if ($slides_query->have_posts()) {
        while ($slides_query->have_posts()) {
            $slides_query->the_post();
            $post_id = get_the_ID();
            $image_url = get_the_post_thumbnail_url($post_id, 'full');
            $link_url = get_post_meta($post_id, '_slide_link', true);

            if ($image_url) {
                $slides_data[] = array(
                    'name' => get_the_title(),
                    'image' => $image_url,
                    'link' => $link_url ? esc_url($link_url) : '#',
                );
            }
        }
    }
    wp_reset_postdata();

    // Pass the data to JavaScript
    wp_localize_script('homeland-script', 'homeland_carousel_data', array(
        'slides' => $slides_data,
    ));

    // Enqueue the script now that data is localized
    wp_enqueue_script('homeland-script');

    // The HTML structure for the carousel
    ob_start();
    ?>
<div class="homeland-carousel-wrapper">
    <div class="slider-container">
        <button class="nav-arrow nav-arrow-left">
            <svg viewBox="0 0 24 24">
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
            </svg>
        </button>
        <div class="product-showcase" id="productShowcase">
            <!-- Cards will be generated by JavaScript -->
        </div>
        <button class="nav-arrow nav-arrow-right">
            <svg viewBox="0 0 24 24">
                <path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z" />
            </svg>
        </button>
    </div>
</div>
<?php
        return ob_get_clean();
}
add_shortcode('homeland_carousel', 'homeland_carousel_shortcode');
