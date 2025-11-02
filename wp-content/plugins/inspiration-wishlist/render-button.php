<?php

/**
 * Render wishlist toggle button
 */
add_shortcode('inspiration_favorite_btn', function () {
    $post_id = get_the_ID();
    if (! $post_id) return '';

    $user_id = get_current_user_id();
    $wishlist = get_user_meta($user_id, 'inspiration_wishlist', true);
    if (! is_array($wishlist)) $wishlist = [];

    $in_wishlist = in_array($post_id, $wishlist);
    $state_class = $in_wishlist ? 'active' : '';
    $label = $in_wishlist ? '' : ''; // Add labels here if needed

    ob_start();
?>
    <button class="inspiration-fav-btn <?php echo $state_class; ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <span class="icon-heart">
            <i class="fa<?php echo $in_wishlist ? 's' : 'r'; ?> fa-heart"></i>
        </span>
        <span class="spinner" style="display:none;">
            <img class="spinner-img" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/spinner.svg" alt="Loading..." style="width:16px;">
        </span>
        <?php echo $label; ?>
    </button>

<?php
    return ob_get_clean();
});
