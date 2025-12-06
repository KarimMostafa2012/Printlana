<?php
/**
 * Handle AJAX
 */
add_action('wp_ajax_toggle_inspiration_wishlist', 'toggle_inspiration_wishlist');
function toggle_inspiration_wishlist()
{
    check_ajax_referer('inspiration_wishlist_nonce', 'nonce');

    $post_id = absint($_POST['post_id']);
    $user_id = get_current_user_id();
    if (! $user_id || get_post_type($post_id) !== 'inspiration') {
        wp_send_json_error();
    }

    $wishlist = get_user_meta($user_id, 'inspiration_wishlist', true);
    if (! is_array($wishlist)) $wishlist = [];

    if (in_array($post_id, $wishlist)) {
        $wishlist = array_diff($wishlist, [$post_id]);
        $status = 'removed';
    } else {
        $wishlist[] = $post_id;
        $status = 'added';
    }

    update_user_meta($user_id, 'inspiration_wishlist', $wishlist);
    wp_send_json_success(['status' => $status]);
}