<?php

/**
 * Render wishlist list
 */
add_shortcode('inspiration_wishlist', function () {
    $user_id = get_current_user_id();
    if (! $user_id) return '<p>Please log in to view your favorites.</p>';

    $wishlist = get_user_meta($user_id, 'inspiration_wishlist', true);
    if (empty($wishlist) || ! is_array($wishlist)) return '<p>No favorites yet.</p>';

    $args = [
        'post_type' => 'inspiration', // Replace with your post type
        'post__in'  => $wishlist,
        'orderby'   => 'post__in'
    ];

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<div class="inspiration-wishlist-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $title = get_the_title();
            $link = get_permalink();
            $thumb = get_the_post_thumbnail_url($id, 'medium');
?>
            <div class="wishlist-card">
                <div class="wishlist-card__image">
                    <a href="<?php echo esc_url($link); ?>">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                    </a>
                    <button class="wishlist-remove-btn" data-post-id="<?php echo esc_attr($id); ?>">
                        <span class="icon-trash"><i class="fas fa-trash-alt"></i></span>
                        <span class="spinner" style="display:none;">
                            <img class="spinner-img" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/spinner.svg" alt="Loading...">
                        </span>
                    </button>

                </div>
                <div class="wishlist-card__title">
                    <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                </div>
            </div>
<?php
        }
        echo '</div>';
    } else {
        echo '<p>No inspiration favorites found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
});
