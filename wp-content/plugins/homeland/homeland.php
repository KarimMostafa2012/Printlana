<?php
/**
 * Plugin Name:       Homeland
 * Description:       A custom plugin for Homepage Carousel and Highlighted Elements.
 * Version:           1.7.0
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
        'singular_name' => _x('Homeland Slide', 'Post Type Singular Name', 'homeland'),
        'menu_name' => __('Homeland', 'homeland'),
        'name_admin_bar' => __('Homeland Slide', 'homeland'),
        'all_items' => __('Carousel Slides', 'homeland'),
        'add_new_item' => __('Add New Slide', 'homeland'),
        'add_new' => __('Add New', 'homeland'),
        'edit_item' => __('Edit Slide', 'homeland'),
        'featured_image' => __('Product Image', 'homeland'),
    );
    $args = array(
        'label' => __('Homeland', 'homeland'),
        'labels' => $labels,
        'supports' => array('title', 'thumbnail'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // Hidden from main menu, we'll add it manually
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-home',
        'show_in_rest' => true,
        'has_archive' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
    );
    // Use a unique slug to avoid WPML/other conflicts
    register_post_type('hp_carousel_slide', $args);
}
add_action('init', 'homeland_register_carousel_slide_cpt');

// 2. Custom Admin Menu
function homeland_admin_menu()
{
    add_menu_page(
        'Homeland',
        'Homeland',
        'manage_options',
        'homeland',
        'homeland_render_highlights_page', // Default to highlights page
        'dashicons-admin-home',
        5
    );

    add_submenu_page(
        'homeland',
        'Carousel Slides',
        'Carousel Slides',
        'manage_options',
        'edit.php?post_type=hp_carousel_slide'
    );

    add_submenu_page(
        'homeland',
        'Highlighted Elements',
        'Highlighted Elements',
        'manage_options',
        'homeland', // Same as parent slug to avoid double entry, or use it for the second page
        'homeland_render_highlights_page'
    );
}
add_action('admin_menu', 'homeland_admin_menu');

function homeland_render_highlights_page()
{
    // Save logic
    if (isset($_POST['homeland_save_highlights']) && check_admin_referer('homeland_highlights_action', 'homeland_highlights_nonce')) {
        $highlights = array();
        for ($i = 1; $i <= 4; $i++) {
            $highlights[$i] = array(
                'image' => sanitize_text_field($_POST['h_img_' . $i]),
                'text'  => sanitize_textarea_field($_POST['h_text_' . $i]),
                'link'  => esc_url_raw($_POST['h_link_' . $i]),
            );
        }
        update_option('homeland_highlights', $highlights);
        echo '<div class="updated"><p>Settings saved!</p></div>';
    }

    $highlights = get_option('homeland_highlights', array());
    ?>
    <div class="wrap">
        <h1>Homeland - Highlighted Elements</h1>
        <form method="post">
            <?php wp_nonce_field('homeland_highlights_action', 'homeland_highlights_nonce'); ?>
            <div class="homeland-admin-grid">
                <?php for ($i = 1; $i <= 4; $i++) : 
                    $data = isset($highlights[$i]) ? $highlights[$i] : array('image' => '', 'text' => '', 'link' => '');
                ?>
                    <div class="homeland-admin-card">
                        <h3>Element <?php echo $i; ?></h3>
                        <div class="homeland-field">
                            <label>Image:</label>
                            <input type="text" name="h_img_<?php echo $i; ?>" id="h_img_<?php echo $i; ?>" value="<?php echo esc_attr($data['image']); ?>" class="regular-text">
                            <button type="button" class="button homeland-upload-btn" data-target="h_img_<?php echo $i; ?>">Select Image</button>
                            <div class="homeland-preview" id="preview_h_img_<?php echo $i; ?>">
                                <?php if ($data['image']) : ?><img src="<?php echo esc_url($data['image']); ?>" style="max-width:100px;display:block;margin-top:10px;"><?php endif; ?>
                            </div>
                        </div>
                        <div class="homeland-field">
                            <label>Description:</label>
                            <textarea name="h_text_<?php echo $i; ?>" class="regular-text" rows="3"><?php echo esc_textarea($data['text']); ?></textarea>
                        </div>
                        <div class="homeland-field">
                            <label>Link:</label>
                            <input type="url" name="h_link_<?php echo $i; ?>" value="<?php echo esc_url($data['link']); ?>" class="regular-text">
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <p class="submit">
                <input type="submit" name="homeland_save_highlights" class="button button-primary" value="Save Changes">
            </p>
        </form>

        <div class="homeland-shortcode-box">
            <h3>Shortcode</h3>
            <p>Use this shortcode to display the highlighted elements:</p>
            <code>[homeland_highlights]</code>
            <button class="button button-secondary homeland-copy-btn" data-code="[homeland_highlights]">Copy Shortcode</button>
        </div>
    </div>
    <?php
}

// 2. Add Meta Box for the Link
function homeland_add_link_meta_box()
{
    add_meta_box(
        'homeland_slide_link',
        'Slide Link',
        'homeland_render_link_meta_box',
        'hp_carousel_slide',
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
    if (!isset($_POST['homeland_link_meta_box_nonce'])) return;
    if (!wp_verify_nonce($_POST['homeland_link_meta_box_nonce'], 'homeland_save_link_meta_box_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['homeland_slide_link_field'])) return;

    update_post_meta($post_id, '_slide_link', sanitize_text_field($_POST['homeland_slide_link_field']));
}
add_action('save_post', 'homeland_save_link_meta_box_data');

// 3. Register Scripts and Styles
function homeland_enqueue_assets()
{
    wp_enqueue_script('gsap-cdn', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);
    wp_enqueue_style('homeland-carousel-style', plugin_dir_url(__FILE__) . 'homeland.css', array(), '1.7.0');
    wp_register_script('homeland-carousel-script', plugin_dir_url(__FILE__) . 'homeland.js', array('gsap-cdn'), '1.7.0', true);
}
add_action('wp_enqueue_scripts', 'homeland_enqueue_assets');

// 3. Admin Assets
function homeland_admin_assets($hook)
{
    // Only load on our plugin pages
    if (strpos($hook, 'homeland') === false && strpos($hook, 'hp_carousel_slide') === false) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('homeland-admin-style', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.8.0');
    wp_enqueue_script('homeland-admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.8.0', true);
}
add_action('admin_enqueue_scripts', 'homeland_admin_assets');

// 5. Shortcode for Carousel
function homeland_carousel_shortcode()
{
    $args = array(
        'post_type' => 'hp_carousel_slide',
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

    wp_localize_script('homeland-carousel-script', 'homeland_carousel_data', array(
        'slides' => $slides_data,
    ));

    wp_enqueue_script('homeland-carousel-script');

    ob_start();
    ?>
    <div class="homeland-carousel-wrapper">
        <div class="slider-container">
            <button class="nav-arrow nav-arrow-left">
                <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" /></svg>
            </button>
            <div class="product-showcase" id="productShowcase"></div>
            <button class="nav-arrow nav-arrow-right">
                <svg viewBox="0 0 24 24"><path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z" /></svg>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('homeland_carousel', 'homeland_carousel_shortcode');

// 6. Shortcode for Highlighted Elements
function homeland_highlights_shortcode()
{
    $highlights = get_option('homeland_highlights', array());
    if (empty($highlights)) {
        return '';
    }

    // Default placeholders if data is missing
    $defaults = array(
        1 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'Enjoy discounts on all types of groceries and frozen products', 'link' => '#'),
        2 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'Enjoy discounts on all groceries and frozen products', 'link' => '#'),
        3 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'Enjoy discounts on all groceries and frozen products', 'link' => '#'),
        4 => array('image' => plugin_dir_url(__FILE__) . 'assets/fries.png', 'text' => 'Enjoy discounts on all types of groceries and frozen products', 'link' => '#'),
    );

    $h = array();
    for ($i = 1; $i <= 4; $i++) {
        $h[$i] = isset($highlights[$i]) ? $highlights[$i] : $defaults[$i];
        if (empty($h[$i]['image'])) $h[$i]['image'] = $defaults[$i]['image'];
        if (empty($h[$i]['text']))  $h[$i]['text']  = $defaults[$i]['text'];
        if (empty($h[$i]['link']))  $h[$i]['link']  = $defaults[$i]['link'];
    }

    ob_start();
    ?>
    <style>
    .h-wrapper { display: flex; flex-direction: row; gap: 32px; width: 100%; max-width: 1280px; margin: 0 auto; font-family: 'Beiruti', sans-serif; background: transparent; padding: 20px; box-sizing: border-box; }
    .h-card { background: #F1F5FD; border-radius: 16px; position: relative; overflow: hidden; height: 544px; flex: 1; padding: 24px; display: flex; flex-direction: column; align-items: center; transition: box-shadow 0.3s; box-sizing: border-box; text-decoration: none; color: inherit; }
    .h-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .h-img { max-width: 80%; max-height: 60%; object-fit: contain; transition: transform 0.3s; margin-top: 40px; }
    .h-card:hover .h-img { transform: scale(1.05) translateY(-10px); }
    .h-text-group { position: absolute; bottom: 40px; text-align: center; width: 100%; padding: 0 24px; direction: rtl; box-sizing: border-box; }
    .h-promo { font-size: 24px; font-weight: 600; color: #000014; line-height: 1.2; }
    .h-mid-col { display: flex; flex-direction: column; gap: 32px; flex: 1; box-sizing: border-box; }
    .h-small-card { background: #F1F5FD; border-radius: 16px; height: 256px; position: relative; padding: 15px; overflow: hidden; transition: box-shadow 0.3s; display: flex; flex-direction: column; align-items: center; box-sizing: border-box; text-decoration: none; color: inherit; }
    .h-small-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .h-small-img { max-width: 50%; max-height: 55%; object-fit: contain; transition: transform 0.3s; margin-top: 10px; }
    .h-small-card:hover .h-small-img { transform: scale(1.05) translateY(-5px); }
    .h-small-text { position: absolute; bottom: 24px; text-align: center; width: 100%; padding: 0 15px; font-weight: 600; color: #000014; direction: rtl; font-size: 20px; line-height: 1.2; box-sizing: border-box; }
    @media (max-width: 1024px) { .h-wrapper { flex-direction: column; align-items: center; } .h-card, .h-mid-col { width: 100%; max-width: 405px; flex: none; } }
    </style>

    <div class="h-wrapper">
        <a href="<?php echo esc_url($h[1]['link']); ?>" class="h-card">
            <img src="<?php echo esc_url($h[1]['image']); ?>" class="h-img">
            <div class="h-text-group"><div class="h-promo"><?php echo esc_html($h[1]['text']); ?></div></div>
        </a>
        <div class="h-mid-col">
            <a href="<?php echo esc_url($h[2]['link']); ?>" class="h-small-card">
                <img src="<?php echo esc_url($h[2]['image']); ?>" class="h-small-img">
                <div class="h-small-text"><?php echo esc_html($h[2]['text']); ?></div>
            </a>
            <a href="<?php echo esc_url($h[3]['link']); ?>" class="h-small-card">
                <img src="<?php echo esc_url($h[3]['image']); ?>" class="h-small-img">
                <div class="h-small-text"><?php echo esc_html($h[3]['text']); ?></div>
            </a>
        </div>
        <a href="<?php echo esc_url($h[4]['link']); ?>" class="h-card">
            <img src="<?php echo esc_url($h[4]['image']); ?>" class="h-img">
            <div class="h-text-group"><div class="h-promo"><?php echo esc_html($h[4]['text']); ?></div></div>
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('homeland_highlights', 'homeland_highlights_shortcode');

// 7. Add columns to Slide list
function homeland_set_custom_edit_hp_carousel_slide_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key == 'title') {
            $new_columns['shortcode'] = 'Shortcode';
        }
    }
    return $new_columns;
}
add_filter('manage_hp_carousel_slide_posts_columns', 'homeland_set_custom_edit_hp_carousel_slide_columns');

function homeland_custom_hp_carousel_slide_column($column, $post_id) {
    if ($column == 'shortcode') {
        echo '<code>[homeland_carousel]</code>';
        echo '<br><button class="button button-small homeland-copy-btn" data-code="[homeland_carousel]">Copy</button>';
    }
}
add_action('manage_hp_carousel_slide_posts_custom_column', 'homeland_custom_hp_carousel_slide_column', 10, 2);
