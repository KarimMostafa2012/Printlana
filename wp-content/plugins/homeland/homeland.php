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
    // Main Menu Item pointing to Carousel Slides
    add_menu_page(
        'Homeland',
        'Homeland',
        'manage_options',
        'edit.php?post_type=hp_carousel_slide',
        '',
        'dashicons-admin-home',
        5
    );

    // Submenu for Carousel Slides (re-add to ensure name is correct under Homeland)
    add_submenu_page(
        'edit.php?post_type=hp_carousel_slide',
        'Carousel Slides',
        'Carousel Slides',
        'manage_options',
        'edit.php?post_type=hp_carousel_slide'
    );

    // Submenu for Highlighted Elements
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
        
        <div class="homeland-admin-preview-section">
            <p>Click on an element below to customize it.</p>
            <div class="homeland-preview-container">
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
                    
                    <!-- Hidden inputs for all 4 elements to preserve data on save -->
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
                                <img id="modal-preview-img" src="" style="max-width:150px; display:none; margin-bottom:10px;">
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

        <div class="homeland-shortcode-box">
            <h3>Shortcode</h3>
            <p>Use this shortcode to display the highlighted elements on any page:</p>
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
    
    /* Admin specificity */
    .homeland-preview-container .h-card, .homeland-preview-container .h-small-card { cursor: pointer; }
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

// 7. Carousel List Enhancements
function homeland_carousel_list_ui() {
    $screen = get_current_screen();
    if ($screen->id !== 'edit-hp_carousel_slide') return;
    ?>
    <div class="homeland-carousel-admin-header">
        <div class="homeland-shortcode-info">
            <strong>Shortcode:</strong> <code>[homeland_carousel]</code>
            <button class="button button-small homeland-copy-btn" data-code="[homeland_carousel]">Copy</button>
        </div>
        <div class="homeland-bulk-actions">
            <button type="button" class="button button-primary homeland-bulk-add">Bulk Add Slides</button>
        </div>
    </div>
    <style>
        .homeland-carousel-admin-header { margin: 15px 0; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border: 1px solid #ccd0d4; border-radius: 4px; }
        .homeland-add-bottom-right { position: fixed; bottom: 30px; right: 30px; z-index: 99; }
        .homeland-add-bottom-right a { padding: 10px 20px !important; height: auto !important; line-height: 1 !important; font-size: 14px !important; border-radius: 25px !important; box-shadow: 0 4px 10px rgba(0,0,0,0.2) !important; }
    </style>
    <div class="homeland-add-bottom-right">
        <a href="<?php echo admin_url('post-new.php?post_type=hp_carousel_slide'); ?>" class="button button-primary">Add New Slide</a>
    </div>
    <?php
}
add_action('admin_notices', 'homeland_carousel_list_ui');

// 8. AJX Provider for Bulk Add
function homeland_bulk_add_slides() {
    check_ajax_referer('homeland_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

    $image_ids = isset($_POST['image_ids']) ? (array)$_POST['image_ids'] : array();
    $created = 0;

    foreach ($image_ids as $id) {
        $title = get_the_title($id);
        $new_post = array(
            'post_title'    => $title,
            'post_status'   => 'publish',
            'post_type'     => 'hp_carousel_slide'
        );
        $post_id = wp_insert_post($new_post);
        if ($post_id) {
            set_post_thumbnail($post_id, $id);
            $created++;
        }
    }

    wp_send_json_success(array('count' => $created));
}
add_action('wp_ajax_homeland_bulk_add', 'homeland_bulk_add_slides');

// Pass nonce to JS
function homeland_admin_footer_nonce() {
    ?>
    <script type="text/javascript">
        var homeland_admin = {
            nonce: '<?php echo wp_create_nonce("homeland_admin_nonce"); ?>',
            ajax_url: '<?php echo admin_url("admin-ajax.php"); ?>'
        };
    </script>
    <?php
}
add_action('admin_footer', 'homeland_admin_footer_nonce');
