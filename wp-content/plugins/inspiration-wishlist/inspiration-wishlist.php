<?php

/**
 * Plugin Name: Custom Inspiration Wishlist
 * Description: Wishlist for post type "inspiration"
 * Version: 1.0
 * Author: melkoff
 * Author URI: https://github.com/melkoff
 */

add_action('wp_enqueue_scripts', 'custom_inspiration_wishlist_scripts');
function custom_inspiration_wishlist_scripts()
{
    // Include custom JavaScript
    wp_enqueue_script('inspiration-wishlist-js', plugin_dir_url(__FILE__) . 'assets/ajax-wishlist.js', ['jquery'], null, true);
    wp_localize_script('inspiration-wishlist-js', 'InspirationWishlist', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('inspiration_wishlist_nonce')
    ]);

    // Include custom CSS for Favorites
    wp_enqueue_style('inspiration-wishlist-css', plugin_dir_url(__FILE__) . 'assets/favorite-wishlist.css');

    // Include custom CSS for button
    wp_enqueue_style('inspiration-wishlist-button-css', plugin_dir_url(__FILE__) . 'assets/button-wishlist.css');
}


// Render wishlist toggle button
include_once(plugin_dir_path(__FILE__) . 'render-button.php');

// Render wishlist list
include_once(plugin_dir_path(__FILE__) . 'render-wishlist.php');

// AJAX toggle wishlist
include_once(plugin_dir_path(__FILE__) . 'ajax-functions.php');
