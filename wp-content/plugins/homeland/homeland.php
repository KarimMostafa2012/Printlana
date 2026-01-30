<?php
/**
 * Plugin Name:       Homeland
 * Description:       A custom plugin for Homepage Carousel and Highlighted Elements.
 * Version:           1.3.0
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
    wp_enqueue_style('homeland-style', plugin_dir_url(__FILE__) . 'homeland.css', array(), '1.3.0');
    wp_register_script('homeland-script', plugin_dir_url(__FILE__) . 'homeland.js', array('gsap-cdn'), '1.3.0', true);
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
            max-width: 1000px;
        }

        .homeland-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .homeland-element {
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 8px;
            background: #fafafa;
        }

        .homeland-preview-box {
            display: flex;
            gap: 20px;
            align-items: center;
            background: #F1F5FD;
            padding: 15px;
            border-radius: 12px;
            margin-top: 10px;
        }

        .homeland-preview-img {
            width: 60px;
            transition: transform 0.3s;
        }

        .homeland-preview-img:hover {
            transform: scale(1.1);
        }

        .homeland-label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
    </style>

    <div class="wrap">
        <h1>Homeland Settings - Highlighted Elements (New & Hot)</h1>
        <p>Customize the content for the 4 highlighted elements and generate the Elementor HTML code.</p>

        <div class="homeland-admin-card">
            <h3>Customization</h3>
            <form id="homeland-form">
                <div class="homeland-grid">
                    <!-- Card 1 (Large Left) -->
                    <div class="homeland-element">
                        <h4>Element 1 (Large Left - Bag)</h4>
                        <label class="homeland-label">Title/Content</label>
                        <textarea id="h_card_1" class="regular-text" rows="2"
                            style="width:100%">Enjoy discounts on all types of groceries and frozen products</textarea>
                    </div>

                    <!-- Card 2 (Small Mid Top) -->
                    <div class="homeland-element">
                        <h4>Element 2 (Small Mid Top - Bag)</h4>
                        <label class="homeland-label">Title/Content</label>
                        <textarea id="h_card_2" class="regular-text" rows="2"
                            style="width:100%">Enjoy discounts on all groceries and frozen products</textarea>
                    </div>

                    <!-- Card 3 (Small Mid Bottom) -->
                    <div class="homeland-element">
                        <h4>Element 3 (Small Mid Bottom - Bag)</h4>
                        <label class="homeland-label">Title/Content</label>
                        <textarea id="h_card_3" class="regular-text" rows="2"
                            style="width:100%">Enjoy discounts on all groceries and frozen products</textarea>
                    </div>

                    <!-- Card 4 (Large Right - Fries) -->
                    <div class="homeland-element">
                        <h4>Element 4 (Large Right - Fries)</h4>
                        <label class="homeland-label">Title/Content</label>
                        <textarea id="h_card_4" class="regular-text" rows="2"
                            style="width:100%">Enjoy discounts on all types of groceries and frozen products</textarea>
                    </div>
                </div>

                <p class="submit">
                    <input type="button" class="button button-primary" value="Generate Elementor Code"
                        onclick="generateHomelandCode()">
                </p>
            </form>

            <div id="homeland-code-result" style="display:none; margin-top: 20px;">
                <hr>
                <h3>Copy this code and use it in a (HTML) element in Elementor:</h3>
                <textarea id="homeland-textarea"
                    style="width:100%; height:300px; font-family: monospace; font-size: 12px; direction: ltr;"
                    readonly></textarea>
                <p>
                    <button class="button button-secondary" onclick="copyHomelandCode()">Copy Code</button>
                </p>
            </div>
        </div>

        <div class="homeland-admin-card">
            <h3>Preview (Placeholder Mode)</h3>
            <div class="homeland-preview-box">
                <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/bag.png'; ?>" class="homeland-preview-img">
                <div>
                    <strong>Lorem Ipsum</strong>
                    <p style="font-size: 14px; color: #666; margin: 5px 0 0;">Placeholder text with bag.png</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateHomelandCode() {
            const c1 = document.getElementById('h_card_1').value;
            const c2 = document.getElementById('h_card_2').value;
            const c3 = document.getElementById('h_card_3').value;
            const c4 = document.getElementById('h_card_4').value;

            const bagUrl = "<?php echo plugin_dir_url(__FILE__) . 'assets/bag.png'; ?>";
            const friesUrl = "<?php echo plugin_dir_url(__FILE__) . 'assets/fries.png'; ?>";

            const code = `
<style>
.h-wrapper { display: flex; flex-direction: row; gap: 32px; width: 100%; max-width: 1280px; margin: 0 auto; font-family: 'Beiruti', sans-serif; background: #000; padding: 20px; box-sizing: border-box; }
.h-card { background: #F1F5FD; border-radius: 16px; position: relative; overflow: hidden; height: 544px; flex: 1; padding: 24px; display: flex; flex-direction: column; align-items: center; transition: box-shadow 0.3s; box-sizing: border-box; }
.h-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.h-img { max-width: 80%; max-height: 60%; object-fit: contain; transition: transform 0.3s; margin-top: 40px; }
.h-card:hover .h-img { transform: scale(1.05) translateY(-10px); }
.h-text-group { position: absolute; bottom: 40px; text-align: center; width: 100%; padding: 0 24px; direction: rtl; box-sizing: border-box; }
.h-promo { font-size: 24px; font-weight: 600; color: #000014; line-height: 1.2; }
.h-mid-col { display: flex; flex-direction: column; gap: 32px; flex: 1; box-sizing: border-box; }
.h-small-card { background: #F1F5FD; border-radius: 16px; height: 256px; position: relative; padding: 15px; overflow: hidden; transition: box-shadow 0.3s; display: flex; flex-direction: column; align-items: center; box-sizing: border-box; }
.h-small-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.h-small-img { max-width: 50%; max-height: 55%; object-fit: contain; transition: transform 0.3s; margin-top: 10px; }
.h-small-card:hover .h-small-img { transform: scale(1.05) translateY(-5px); }
.h-small-text { position: absolute; bottom: 24px; text-align: center; width: 100%; padding: 0 15px; font-weight: 600; color: #000014; direction: rtl; font-size: 20px; line-height: 1.2; box-sizing: border-box; }
@media (max-width: 1024px) { .h-wrapper { flex-direction: column; align-items: center; } .h-card, .h-mid-col { width: 100%; max-width: 405px; flex: none; } }
</style>

<div class="h-wrapper">
    <!-- Card 1 (Large Left) -->
    <div class="h-card">
        <img src="${bagUrl}" class="h-img">
        <div class="h-text-group"><div class="h-promo">${c1}</div></div>
    </div>
    
    <!-- Middle Column -->
    <div class="h-mid-col">
        <!-- Card 2 (Top) -->
        <div class="h-small-card">
            <img src="${bagUrl}" class="h-small-img">
            <div class="h-small-text">${c2}</div>
        </div>
        <!-- Card 3 (Bottom) -->
        <div class="h-small-card">
            <img src="${bagUrl}" class="h-small-img">
            <div class="h-small-text">${c3}</div>
        </div>
    </div>

    <!-- Card 4 (Large Right) -->
    <div class="h-card">
        <img src="${friesUrl}" class="h-img">
        <div class="h-text-group"><div class="h-promo">${c4}</div></div>
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
        alert("Code copied to clipboard successfully!");
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
    <h1>منتجاتنا</h1>
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
