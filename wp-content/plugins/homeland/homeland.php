<?php
/**
 * Plugin Name:       Homeland
 * Description:       A custom plugin for Homepage Carousel and Highlighted Elements.
 * Version:           2.3.0
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

    // Register Discount Card CPT
    $dc_labels = array(
        'name' => _x('Discount Cards', 'Post Type General Name', 'homeland'),
        'singular_name' => _x('Discount Card', 'Post Type Singular Name', 'homeland'),
        'menu_name' => __('Discount Cards', 'homeland'),
        'all_items' => __('All Discount Cards', 'homeland'),
        'add_new_item' => __('Add New Card', 'homeland'),
        'add_new' => __('Add New', 'homeland'),
        'edit_item' => __('Edit Card', 'homeland'),
    );
    $dc_args = array(
        'labels' => $dc_labels,
        'supports' => array('title'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_rest' => true,
        'has_archive' => false,
        'capability_type' => 'post',
    );
    register_post_type('discount_card', $dc_args);
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

    add_submenu_page(
        'edit.php?post_type=hp_carousel_slide',
        'Discount Cards',
        'Discount Cards',
        'manage_options',
        'edit.php?post_type=discount_card'
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

// 3.1 Meta Boxes for Discount Cards
function homeland_add_discount_card_meta_boxes() {
    add_meta_box('homeland_dc_settings', 'Card Settings', 'homeland_render_dc_meta_box', 'discount_card', 'normal', 'high');
}
add_action('add_meta_boxes', 'homeland_add_discount_card_meta_boxes');

function homeland_render_dc_meta_box($post) {
    wp_nonce_field('homeland_save_dc_meta', 'homeland_dc_meta_nonce');
    $title = get_post_meta($post->ID, '_dc_title', true);
    $percentage = get_post_meta($post->ID, '_dc_percentage', true);
    $color = get_post_meta($post->ID, '_dc_color', true) ?: '#F9B110';
    $superscript = get_post_meta($post->ID, '_dc_superscript', true) ?: 'UP TO';
    $subscript = get_post_meta($post->ID, '_dc_subscript', true) ?: 'OFF';
    ?>
    <style>
        .homeland-dc-field { margin-bottom: 20px; }
        .homeland-dc-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .homeland-dc-warning { padding: 10px; border-radius: 4px; margin-top: 5px; display: none; }
        .homeland-dc-warning.yellow { background: #fff3cd !important; border: 1px solid #ffeeba; color: #856404; }
        .homeland-dc-warning.red { background: #f8d7da !important; border: 1px solid #f5c6cb; color: #721c24; }
        .homeland-dc-warning.green { background: #d4edda !important; border: 1px solid #c3e6cb; color: #155724; }
    </style>
    <div class="homeland-dc-settings">
        <div class="homeland-dc-field">
            <label>Title (Beiruti font):</label>
            <input type="text" name="dc_title" value="<?php echo esc_attr($title); ?>" style="width:100%;" placeholder="e.g. خصومات هوم لاند">
        </div>
        <div class="homeland-dc-field">
            <label>Percentage:</label>
            <input type="number" name="dc_percentage" id="dc_percentage_input" value="<?php echo esc_attr($percentage); ?>" style="width:100px;">
            <div id="dc_warning_box" class="homeland-dc-warning"></div>
        </div>
        <div class="homeland-dc-field">
            <label>Background Color:</label>
            <input type="color" name="dc_color" value="<?php echo esc_attr($color); ?>">
        </div>
        <div class="homeland-dc-field">
            <label>Superscript Text:</label>
            <input type="text" name="dc_superscript" value="<?php echo esc_attr($superscript); ?>" style="width:100%;">
        </div>
        <div class="homeland-dc-field">
            <label>Subscript Text:</label>
            <input type="text" name="dc_subscript" value="<?php echo esc_attr($subscript); ?>" style="width:100%;">
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            function checkPercentage() {
                var val = $('#dc_percentage_input').val();
                var $box = $('#dc_warning_box');
                if (!val) { $box.hide(); return; }
                var lastDigit = parseInt(val.toString().split('').pop());
                $box.removeClass('yellow red green').hide();
                
                if ([1, 2, 9].includes(lastDigit)) {
                    $box.text('Yellow Warning: This unit digit is acceptable but not recommended.').addClass('yellow').show();
                } else if (lastDigit === 4) {
                    $box.text('Red Warning: This unit digit is extremely not recommended!').addClass('red').show();
                } else {
                    $box.text('Recommended: This digit is good to go.').addClass('green').show();
                }
            }
            $('#dc_percentage_input').on('input', checkPercentage);
            checkPercentage();
        });
    </script>
    <?php
}

function homeland_save_dc_meta($post_id) {
    if (!isset($_POST['homeland_dc_meta_nonce']) || !wp_verify_nonce($_POST['homeland_dc_meta_nonce'], 'homeland_save_dc_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    $fields = ['dc_title', 'dc_percentage', 'dc_color', 'dc_superscript', 'dc_subscript'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'homeland_save_dc_meta');

// 3.2 List Table Toggle Logic & Columns
function homeland_dc_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['dc_status'] = 'Active Status';
        }
    }
    return $new_columns;
}
add_filter('manage_discount_card_posts_columns', 'homeland_dc_columns');

function homeland_dc_column_content($column, $post_id) {
    if ($column === 'dc_status') {
        $is_active = get_post_meta($post_id, '_dc_active', true) === '1';
        $url = add_query_arg(array(
            'action' => 'homeland_toggle_dc',
            'dc_id' => $post_id,
            'nonce' => wp_create_nonce('homeland_toggle_' . $post_id)
        ), admin_url('edit.php?post_type=discount_card'));

        if ($is_active) {
            echo '<a href="' . esc_url($url) . '" class="button button-small" style="background:#d4edda; color:#155724; border-color:#c3e6cb;">Active</a>';
        } else {
            echo '<a href="' . esc_url($url) . '" class="button button-small">Inactive</a>';
        }
    }
}
add_action('manage_discount_card_posts_custom_column', 'homeland_dc_column_content', 10, 2);

function homeland_handle_dc_toggle() {
    if (isset($_GET['action']) && $_GET['action'] === 'homeland_toggle_dc' && isset($_GET['dc_id'])) {
        $post_id = intval($_GET['dc_id']);
        if (!wp_verify_nonce($_GET['nonce'], 'homeland_toggle_' . $post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $is_active = get_post_meta($post_id, '_dc_active', true) === '1';
        
        if (!$is_active) {
            // Activating: Check max 4
            $active_query = new WP_Query(array(
                'post_type' => 'discount_card',
                'meta_key' => '_dc_active',
                'meta_value' => '1',
                'posts_per_page' => -1
            ));
            if ($active_query->found_posts >= 4) {
                wp_die('You can only have a maximum of 4 active discount cards. Please deactivate one before activating a new one.');
            }
            update_post_meta($post_id, '_dc_active', '1');
        } else {
            update_post_meta($post_id, '_dc_active', '0');
        }

        wp_redirect(admin_url('edit.php?post_type=discount_card'));
        exit;
    }
}
add_action('admin_init', 'homeland_handle_dc_toggle');

// 4. Assets
function homeland_enqueue_assets() {
    wp_enqueue_style('homeland-fonts', 'https://fonts.googleapis.com/css2?family=Beiruti:wght@200..900&family=Inter:wght@700&display=swap', array(), null);
    wp_enqueue_script('gsap-cdn', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);
    wp_enqueue_style('homeland-carousel-style', plugin_dir_url(__FILE__) . 'homeland.css', array(), '2.3.1');
    wp_register_script('homeland-carousel-script', plugin_dir_url(__FILE__) . 'homeland.js', array('gsap-cdn'), '2.3.0', true);
}
add_action('wp_enqueue_scripts', 'homeland_enqueue_assets');

function homeland_admin_assets($hook) {
    global $post_type;
    $is_homeland_page = (strpos($hook, 'homeland') !== false);
    $is_carousel_post_type = (isset($post_type) && $post_type === 'hp_carousel_slide') || (isset($_GET['post_type']) && $_GET['post_type'] === 'hp_carousel_slide');
    
    if (!$is_homeland_page && !$is_carousel_post_type) return;

    wp_enqueue_media();
    wp_enqueue_style('homeland-fonts', 'https://fonts.googleapis.com/css2?family=Beiruti:wght@200..900&family=Inter:wght@700&display=swap', array(), null);
    wp_enqueue_style('homeland-carousel-style', plugin_dir_url(__FILE__) . 'homeland.css', array(), '2.3.1');
    wp_enqueue_style('homeland-admin-style', plugin_dir_url(__FILE__) . 'admin.css', array(), '2.3.0');
    wp_enqueue_script('homeland-admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '2.3.0', true);
    wp_localize_script('homeland-admin-script', 'homeland_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('homeland_admin_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'homeland_admin_assets');

// 6. Highlighted Elements Shortcode
function homeland_highlights_shortcode()
{
    wp_enqueue_style('homeland-carousel-style');
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
    <div class="h-wrapper">
        <a href="<?php echo esc_url($h[1]['link']); ?>" class="h-card" data-index="1">
            <img src="<?php echo esc_url($h[1]['image']); ?>" class="h-img">
            <div class="h-text-group"><p class="h-promo"><?php echo esc_html($h[1]['text']); ?></p></div>
        </a>
        <div class="h-mid-col">
            <a href="<?php echo esc_url($h[2]['link']); ?>" class="h-small-card" data-index="2">
                <img src="<?php echo esc_url($h[2]['image']); ?>" class="h-small-img">
                <div class="h-small-text-group"><p class="h-small-promo"><?php echo esc_html($h[2]['text']); ?></p></div>
            </a>
            <a href="<?php echo esc_url($h[3]['link']); ?>" class="h-small-card" data-index="3">
                <img src="<?php echo esc_url($h[3]['image']); ?>" class="h-small-img">
                <div class="h-small-text-group"><p class="h-small-promo"><?php echo esc_html($h[3]['text']); ?></p></div>
            </a>
        </div>
        <a href="<?php echo esc_url($h[4]['link']); ?>" class="h-card" data-index="4">
            <img src="<?php echo esc_url($h[4]['image']); ?>" class="h-img">
            <div class="h-text-group"><p class="h-promo"><?php echo esc_html($h[4]['text']); ?></p></div>
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
    wp_localize_script('homeland-carousel-script', 'homeland_carousel_data', array('slides' => $slides)); 
    wp_enqueue_script('homeland-carousel-script');
    wp_enqueue_style('homeland-carousel-style');
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

// 7.1 Discount Card Shortcode & Rendering
function homeland_render_discount_card($args) {
    $attr = shortcode_atts(array('id' => 0), $args);
    $post_ids = array();

    if ($attr['id']) {
        $post_ids[] = $attr['id'];
    } else {
        // Fetch up to 4 active cards
        $dc_query = new WP_Query(array(
            'post_type' => 'discount_card',
            'meta_key' => '_dc_active',
            'meta_value' => '1',
            'posts_per_page' => 4,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        if ($dc_query->have_posts()) {
            while ($dc_query->have_posts()) {
                $dc_query->the_post();
                $post_ids[] = get_the_ID();
            }
        }
        wp_reset_postdata();
    }

    if (empty($post_ids)) return '';

    ob_start();
    echo '<div class="discount-cards-container" style="display:flex; flex-wrap:wrap; gap:30px; justify-content:center; width:100%; padding: 40px 0;">';
    
    foreach ($post_ids as $post_id) {
        $title = get_post_meta($post_id, '_dc_title', true);
        $percentage = get_post_meta($post_id, '_dc_percentage', true);
        $color = get_post_meta($post_id, '_dc_color', true) ?: '#F9B110';
        $superscript = get_post_meta($post_id, '_dc_superscript', true) ?: 'UP TO';
        $subscript = get_post_meta($post_id, '_dc_subscript', true) ?: 'OFF';

        $p_str = (string)$percentage;
        $digits = str_split($p_str);
        ?>
        <div class="discount-card" style="--card-bg-color: <?php echo esc_attr($color); ?>;">
            <div class="top-section">
                <div class="content">
                    <div class="header"><?php echo esc_html($title); ?></div>
                    <div class="up-to"><?php echo esc_html($superscript); ?></div>
                    
                    <div class="percentage-group">
                        <?php 
                        $numStr = str_pad($percentage, 2, '0', STR_PAD_LEFT);
                        $is_single = intval($percentage) < 10;
                        $numberX = $is_single ? 110 : 100;
                        
                        $PERCENT_PATHS = array(
                            'M61.5208 4.23339L14.3891 86.9256H4.79338L54.1829 0L61.5208 4.23339Z',
                            'M9.31346 35.2781C4.2334 35.2781 0 39.5114 0 44.5915V51.9294C0 57.0094 4.2334 61.2428 9.31346 61.2428C14.3935 61.2428 18.6269 57.0094 18.6269 51.9294V44.5915C18.6269 39.5114 14.3935 35.2781 9.31346 35.2781ZM13.2646 51.9294C13.2646 54.1872 11.5713 55.5983 9.59571 55.5983C7.62012 55.5983 5.92676 53.905 5.92676 51.9294V44.5915C5.92676 42.6159 7.62012 40.9226 9.59571 40.9226C11.5713 40.9226 13.2646 42.6159 13.2646 44.5915V51.9294Z',
                            'M38.9473 60.6787C33.8672 60.6787 29.6338 64.9121 29.6338 69.9921V77.33C29.6338 82.4101 33.8672 86.6434 38.9473 86.6434C44.0273 86.6434 48.2607 82.4101 48.2607 77.33V69.9921C48.2607 64.9121 44.0273 60.6787 38.9473 60.6787ZM42.6162 77.6123C42.6162 79.5878 40.9228 81.2812 38.9473 81.2812C36.9717 81.2812 35.2783 79.5878 35.2783 77.6123V70.2744C35.2783 68.2988 36.9717 66.6054 38.9473 66.6054C40.9228 66.6054 42.6162 68.2988 42.6162 70.2744V77.6123Z'
                        );
                        ?>
                        <svg class="percentage-display" viewBox="0 0 200 160" width="200" height="160" xmlns="http://www.w3.org/2000/svg" overflow="visible">
                            <text x="<?php echo $numberX; ?>" y="125"
                                  font-family="CustomNumbers, Cairo, Arial, sans-serif"
                                  font-weight="900"
                                  font-size="140"
                                  fill="black"
                                  text-anchor="middle"
                                  letter-spacing="-2" 
                                  transform="scale(1, 1.2)"
                                  transform-origin="<?php echo $numberX; ?> 100">
                                <?php echo $numStr; ?>
                            </text>
                            <g transform="translate(135, 45)">
                                <?php foreach ($PERCENT_PATHS as $idx => $d) : 
                                    $sw = ($idx === 0) ? "18" : "14";
                                    $lj = ($idx === 0) ? "miter" : "round";
                                ?>
                                    <path d="<?php echo $d; ?>" stroke="<?php echo esc_attr($color); ?>" stroke-width="<?php echo $sw; ?>" fill="none" stroke-linejoin="<?php echo $lj; ?>" />
                                <?php endforeach; ?>
                                <?php foreach ($PERCENT_PATHS as $d) : ?>
                                    <path d="<?php echo $d; ?>" fill="black" />
                                <?php endforeach; ?>
                            </g>
                        </svg>
                    </div>
                    
                    <div class="off-text"><?php echo esc_html($subscript); ?></div>
                </div>
            </div>
            
            <div class="divider-line"></div>
            
            <div class="bottom-section">
                <button class="redeem-button">احصل على العرض</button>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('discount_card', 'homeland_render_discount_card');

// 8. Carousel Admin UI
function homeland_carousel_admin_buttons() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-hp_carousel_slide') return;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var buttons = '<div class="homeland-carousel-actions" style="display:inline-flex; align-items:center; gap:8px; margin-left:15px; vertical-align:middle;">' +
                          '<button type="button" class="button button-primary homeland-bulk-add">Bulk Add Slides</button>' +
                          '<a href="<?php echo admin_url('post-new.php?post_type=hp_carousel_slide'); ?>" class="button">Add New Slide</a>' +
                          '</div>';
            
            if ($('.wp-heading-inline').length) {
                $('.wp-heading-inline').after(buttons);
            } else {
                $('.wp-header-end').before(buttons);
            }
        });
    </script>
    <?php
}
add_action('admin_head', 'homeland_carousel_admin_buttons');

function homeland_common_admin_footer() {
    $screen = get_current_screen();
    if (!$screen) return;
    
    if ($screen->id === 'edit-hp_carousel_slide' || $screen->id === 'edit-discount_card') {
        echo '<div class="wrap" style="clear:both; margin-top:40px; margin-bottom: 60px;">';
        if ($screen->id === 'edit-hp_carousel_slide') {
            homeland_render_shortcode_box('[homeland_carousel]');
        } else {
            homeland_render_shortcode_box('[discount_card]');
            echo '<p style="margin-top: -30px; color: #666; font-style: italic;">Note: This shortcode will display all active discount cards (max 4).</p>';
        }
        echo '</div>';
    }
}
add_action('admin_footer', 'homeland_common_admin_footer');

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
