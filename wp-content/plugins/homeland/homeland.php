<?php
/**
 * Plugin Name:       Homeland
 * Description:       A custom plugin for Homepage Carousel and Highlighted Elements.
 * Version:           1.9.0
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
        'show_in_menu' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-home',
        'show_in_rest' => true,
        'has_archive' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
    );
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
        'edit.php?post_type=hp_carousel_slide',
        '',
        'dashicons-admin-home',
        5
    );

    add_submenu_page(
        'edit.php?post_type=hp_carousel_slide',
        'Carousel Slides',
        'Carousel Slides',
        'manage_options',
        'edit.php?post_type=hp_carousel_slide'
    );

    add_submenu_page(
        'edit.php?post_type=hp_carousel_slide',
        'Highlighted Elements',
        'Highlighted Elements',
        'manage_options',
        'homeland_highlights',
        'homeland_render_highlights_page'
    );
}
add_action('admin_menu', 'homeland_admin_menu');

// Helper to render shortcode info box (Standardized)
function homeland_render_shortcode_box($code) {
    ?>
    <div class="homeland-shortcode-box">
        <h3>Shortcode</h3>
        <p>Use this shortcode to display this feature on any page:</p>
        <div class="homeland-code-wrap">
            <code><?php echo esc_html($code); ?></code>
            <button class="button button-secondary homeland-copy-btn" data-code="<?php echo esc_attr($code); ?>">Copy Shortcode</button>
        </div>
    </div>
    <?php
}

function homeland_render_highlights_page()
{
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
        
        <div class="homeland-admin-preview-section">
            <div class="homeland-preview-header">
                <p>Click on an element below to customize it.</p>
                <button type="button" class="button homeland-reset-btn">Reset to Defaults</button>
            </div>
            <div class="homeland-preview-container homeland-admin-preview">
                <?php echo do_shortcode('[homeland_highlights]'); ?>
            </div>
        </div>

        <!-- Customization Modal -->
        <div id="homeland-modal" class="homeland-modal">
            <div class="homeland-modal-content">
                <span class="homeland-modal-close">&times;</span>
                <h2>Customize Element <span id="homeland-element-index"></span></h2>
                <form method="post" id="homeland-highlights-form">
                    <?php wp_nonce_field('homeland_highlights_action', 'homeland_highlights_nonce'); ?>
                    
                    <?php for ($i = 1; $i <= 4; $i++) : 
                        $data = isset($highlights[$i]) ? $highlights[$i] : array('image' => '', 'text' => '', 'link' => '');
                    ?>
                        <input type="hidden" name="h_img_<?php echo $i; ?>" id="h_img_<?php echo $i; ?>" value="<?php echo esc_attr($data['image']); ?>">
                        <input type="hidden" name="h_text_<?php echo $i; ?>" id="h_text_<?php echo $i; ?>" value="<?php echo esc_attr($data['text']); ?>">
                        <input type="hidden" name="h_link_<?php echo $i; ?>" id="h_link_<?php echo $i; ?>" value="<?php echo esc_attr($data['link']); ?>">
                    <?php endfor; ?>

                    <div class="homeland-modal-fields">
                        <div class="homeland-field">
                            <label>Image:</label>
                            <div class="homeland-image-preview-wrap">
                                <img id="modal-preview-img" src="" style="max-width:150px; display:none; margin-bottom:10px; margin-left:auto; margin-right:auto;">
                                <button type="button" class="button homeland-modal-upload-btn">Select Image</button>
                            </div>
                        </div>
                        <div class="homeland-field">
                            <label>Description:</label>
                            <textarea id="modal-field-text" class="regular-text" rows="3"></textarea>
                        </div>
                        <div class="homeland-field">
                            <label>Link:</label>
                            <input type="url" id="modal-field-link" value="" class="regular-text">
                        </div>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="homeland_save_highlights" class="button button-primary" value="Save Changes">
                    </p>
                </form>
            </div>
        </div>

        <?php homeland_render_shortcode_box('[homeland_highlights]'); ?>
    </div>
    <?php
}

// 3. Meta Box for Slide Link
function homeland_add_link_meta_box()
{
    add_meta_box('homeland_slide_link', 'Slide Link', 'homeland_render_link_meta_box', 'hp_carousel_slide', 'normal', 'high');
}
add_action('add_meta_boxes', 'homeland_add_link_meta_box');

function homeland_render_link_meta_box($post)
{
    wp_nonce_field('homeland_save_link_meta_box_data', 'homeland_link_meta_box_nonce');
    $value = get_post_meta($post->ID, '_slide_link', true);
    echo '<label for="homeland_slide_link_field">Destination URL:</label> ';
    echo '<input type="url" id="homeland_slide_link_field" name="homeland_slide_link_field" value="' . esc_attr($value) . '" style="width:100%;" />';
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

// 4. Assets
function homeland_enqueue_assets() {
    wp_enqueue_script('gsap-cdn', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);
    wp_enqueue_style('homeland-carousel-style', plugin_dir_url(__FILE__) . 'homeland.css', array(), '1.9.0');
    wp_register_script('homeland-carousel-script', plugin_dir_url(__FILE__) . 'homeland.js', array('gsap-cdn'), '1.9.0', true);
}
add_action('wp_enqueue_scripts', 'homeland_enqueue_assets');

function homeland_admin_assets($hook) {
    if (strpos($hook, 'homeland') === false && strpos($hook, 'hp_carousel_slide') === false) return;
    wp_enqueue_media();
    wp_enqueue_style('homeland-admin-style', plugin_dir_url(__FILE__) . 'admin.css', array(), '2.0.0');
    wp_enqueue_script('homeland-admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '2.0.0', true);
    wp_localize_script('homeland-admin-script', 'homeland_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('homeland_admin_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'homeland_admin_assets');

// 6. Highlighted Elements Shortcode
function homeland_highlights_shortcode()
{
    $highlights = get_option('homeland_highlights', array());
    $defaults = array(
        1 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'استمتع بخصومات على جميع أنواع البقالة والمنتجات المجمدة', 'link' => '#'),
        2 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'استمتع بخصومات على جميع البقالة والمنتجات المجمدة', 'link' => '#'),
        3 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'استمتع بخصومات على جميع البقالة والمنتجات المجمدة', 'link' => '#'),
        4 => array('image' => plugin_dir_url(__FILE__) . 'assets/bag.png',   'text' => 'استمتع بخصومات على جميع أنواع البقالة والمنتجات المجمدة', 'link' => '#'),
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
    .h-card { background: #F1F5FD; border-radius: 16px; position: relative; overflow: hidden; height: 544px; flex: 1; padding: 24px; display: flex; flex-direction: column; align-items: center; transition: all 0.3s; box-sizing: border-box; text-decoration: none; color: inherit; }
    .h-img { max-width: 80%; max-height: 60%; object-fit: contain; transition: transform 0.3s; margin-top: 40px; }
    .h-card:hover .h-img { transform: scale(1.05) translateY(-10px); }
    .h-text-group { position: absolute; bottom: 40px; text-align: center; width: 100%; padding: 0 24px; direction: rtl; box-sizing: border-box; }
    .h-promo { font-size: 24px; font-weight: 600; color: #000014; line-height: 1.2; }
    .h-mid-col { display: flex; flex-direction: column; gap: 32px; flex: 1; box-sizing: border-box; }
    .h-small-card { background: #F1F5FD; border-radius: 16px; height: 256px; position: relative; padding: 15px; overflow: hidden; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; box-sizing: border-box; text-decoration: none; color: inherit; }
    .h-small-img { max-width: 50%; max-height: 55%; object-fit: contain; transition: transform 0.3s; margin-top: 10px; }
    .h-small-card:hover .h-small-img { transform: scale(1.05) translateY(-5px); }
    .h-small-text { position: absolute; bottom: 24px; text-align: center; width: 100%; padding: 0 15px; font-weight: 600; color: #000014; direction: rtl; font-size: 20px; line-height: 1.2; box-sizing: border-box; }
    @media (max-width: 1024px) { .h-wrapper { flex-direction: column; align-items: center; } .h-card, .h-mid-col { width: 100%; max-width: 405px; flex: none; } }
    .homeland-preview-container .h-card, .homeland-preview-container .h-small-card { cursor: pointer; box-shadow: none !important; }
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

// 7. Carousel Shortcode
function homeland_carousel_shortcode() {
    $args = array('post_type' => 'hp_carousel_slide', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'ASC');
    $query = new WP_Query($args); $slides = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post(); $url = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if ($url) $slides[] = array('name' => get_the_title(), 'image' => $url, 'link' => get_post_meta(get_the_ID(), '_slide_link', true) ?: '#');
        }
    }
    wp_reset_postdata();
    wp_localize_script('homeland_carousel_data', 'slides', $slides); wp_enqueue_script('homeland-carousel-script');
    ob_start(); ?>
    <div class="homeland-carousel-wrapper">
        <div class="slider-container">
            <button class="nav-arrow nav-arrow-left"><svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" /></svg></button>
            <div class="product-showcase" id="productShowcase"></div>
            <button class="nav-arrow nav-arrow-right"><svg viewBox="0 0 24 24"><path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z" /></svg></button>
        </div>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('homeland_carousel', 'homeland_carousel_shortcode');

// 8. Carousel Admin UI
function homeland_carousel_admin_buttons() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-hp_carousel_slide') return;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.wp-header-end').after('<div class="homeland-carousel-actions"><button type="button" class="button action homeland-bulk-add">Bulk Add Slides</button> <a href="<?php echo admin_url('post-new.php?post_type=hp_carousel_slide'); ?>" class="button action">Add New Slide</a></div>');
        });
    </script>
    <?php
}
add_action('admin_head', 'homeland_carousel_admin_buttons');

function homeland_carousel_admin_footer() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-hp_carousel_slide') return;
    homeland_render_shortcode_box('[homeland_carousel]');
}
add_action('admin_footer', 'homeland_carousel_admin_footer');

// 9. AJAX
function homeland_bulk_add_ajax() {
    check_ajax_referer('homeland_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');
    $ids = isset($_POST['image_ids']) ? (array)$_POST['image_ids'] : array(); $count = 0;
    foreach ($ids as $id) {
        $pid = wp_insert_post(array('post_title' => get_the_title($id), 'post_status' => 'publish', 'post_type' => 'hp_carousel_slide'));
        if ($pid) { set_post_thumbnail($pid, $id); $count++; }
    }
    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_homeland_bulk_add', 'homeland_bulk_add_ajax');

function homeland_reset_highlights_ajax() {
    check_ajax_referer('homeland_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');
    delete_option('homeland_highlights');
    wp_send_json_success();
}
add_action('wp_ajax_homeland_reset_highlights', 'homeland_reset_highlights_ajax');
