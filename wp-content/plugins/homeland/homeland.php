<?php
/**
 * Plugin Name:       Homeland Carousel
 * Description:       A custom carousel for the Homepage.
 * Version:           1.0.0
 * Author:            Yahya AlQersh
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// 1. Register Custom Post Type for Carousel Slides
function homeland_register_carousel_slide_cpt()
{
    $labels = array(
        'name' => _x('Carousel Slides', 'Post Type General Name', 'homeland'),
        'singular_name' => _x('Carousel Slide', 'Post Type Singular Name', 'homeland'),
        'menu_name' => __('Carousel Slides', 'homeland'),
        'name_admin_bar' => __('Carousel Slide', 'homeland'),
        'archives' => __('Slide Archives', 'homeland'),
        'attributes' => __('Slide Attributes', 'homeland'),
        'parent_item_colon' => __('Parent Slide:', 'homeland'),
        'all_items' => __('All Slides', 'homeland'),
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
        'menu_icon' => 'dashicons-slides',
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
function homeland_enqueue_carousel_assets()
{
    // GSAP from CDN
    wp_enqueue_script('gsap-cdn', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);

    // Custom CSS and JS
    wp_enqueue_style('homeland-carousel-style', plugin_dir_url(__FILE__) . 'carousel.css', array(), '1.0.2');
    wp_register_script('homeland-carousel-script', plugin_dir_url(__FILE__) . 'carousel.js', array('gsap-cdn'), '1.0.2', true);
}
add_action('wp_enqueue_scripts', 'homeland_enqueue_carousel_assets');

// 4. Create the Shortcode
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
    wp_localize_script('homeland-carousel-script', 'homeland_carousel_data', array(
        'slides' => $slides_data,
    ));

    // Enqueue the script now that data is localized
    wp_enqueue_script('homeland-carousel-script');

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
