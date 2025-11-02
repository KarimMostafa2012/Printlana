
<?php
// This hook will display inspiration wishlist on Dokan dashboard
add_action('woocommerce_account_my-wish-list_endpoint', 'show_custom_inspiration_wishlist');
function show_custom_inspiration_wishlist() {
    echo '<div class="inspiration-on-dokan">';
    echo do_shortcode('[inspiration_wishlist]');
    echo '</div>';
}