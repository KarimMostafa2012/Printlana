<?php
// Register shortcode to display WooCommerce reviews
function get_woocommerce_reviews_shortcode($atts) {
    global $product; // Get the global $product variable
    
    $atts = shortcode_atts(array(
        'number' => 3,
        'product_id' => is_a($product, 'WC_Product') ? $product->get_id() : 0, // Get the product ID
    ), $atts);

    ob_start();
    ?>
    

    <div id="ajax-reviews-wrapper" 
         data-posts-per-page="<?php echo esc_attr($atts['number']); ?>" 
         data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
        <?php echo get_reviews_html(1, $atts['number'], false, $atts['product_id']); ?>
    </div>


    <div class="load-more-container">
        <!-- Load More button -->
        <div id="reviews-loadmore-wrapper" style="text-align: center; margin-top: 20px;">
            <button id="reviews-loadmore-btn" data-current-page="1">View More</button>
        </div>
        <!-- Spinner loading -->
        <div id="reviews-loading-spinner" style="display:none; text-align:center; margin: 20px;">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/blue-spinner.svg" alt="Loading..." style="width:40px;">
        </div>
    </div>

<?php
    $args = array(
    'type'   => 'review',
    'status' => 'approve',
    'post_id' => $atts['product_id'],
);
$total = get_comments(array_merge($args, ['count' => true]));

if ( $total > 0 ) : ?>
    <!-- Custom Pagination with buttons and Pagination with arrows -->
    <div id="reviews-pagination" class="custom-pagination">
        <button class="pagination-arrow prev-arrow" disabled>&larr;</button>
        <div class="pagination-numbers"></div>
        <button class="pagination-arrow next-arrow">&rarr;</button>
    </div>
<?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('woocommerce_reviews', 'get_woocommerce_reviews_shortcode');

// Generate HTML markup for reviews
function get_reviews_html($paged = 1, $number = -1, $hide_pagination = false, $product_id = 0) {
    $offset = ($paged - 1) * $number;

    $args = array(
        'number' => $number,
        'offset' => $offset,
        'status' => 'approve',
        'type'   => 'review',
        'post_id' => $product_id,
    );

    $comments = get_comments($args);
    $total = get_comments(array_merge($args, ['count' => true]));

    ob_start();

    if ($comments) {
        echo '<div class="reviews-loop">';
        foreach ($comments as $comment) {
            $product = wc_get_product($comment->comment_post_ID);
            $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
            $avatar = get_avatar_url($comment->user_id, ['size' => 64]);
            ?>
            <div class="review-item">
                <div class="review-header">
                    <img src="<?php echo esc_url($avatar); ?>" class="review-avatar" alt="Avatar">
                    <div class="review-meta">
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <strong class="review-author"><?php echo esc_html($comment->comment_author); ?></strong>
                    </div>
                </div>
                <div class="review-content">
                    <p><?php echo esc_html($comment->comment_content); ?></p>
                </div>
            </div>
            <?php
        }
        echo '</div>';

    } else {
        echo '<p>There are no reviews yet!</p>';
    }

    return ob_get_clean();
}

// Handle AJAX request to load reviews
add_action('wp_ajax_load_reviews', 'handle_ajax_reviews');
add_action('wp_ajax_nopriv_load_reviews', 'handle_ajax_reviews');

function handle_ajax_reviews() {
    $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $number = isset($_POST['number']) ? intval($_POST['number']) : 5;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $is_load_more = isset($_POST['load_more']) && $_POST['load_more'] === 'true';

    echo get_reviews_html($paged, $number, $is_load_more, $product_id);
    wp_die();
}


add_action('wp_ajax_get_reviews_total_pages', 'get_reviews_total_pages');
add_action('wp_ajax_nopriv_get_reviews_total_pages', 'get_reviews_total_pages');
function get_reviews_total_pages() {
    $number = isset($_POST['number']) ? intval($_POST['number']) : 3;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    $args = array(
        'type'   => 'review',
        'status' => 'approve',
        'post_id' => $product_id,
    );
    
    $total = get_comments(array_merge($args, ['count' => true]));
    $pages = ceil($total / $number);

    wp_send_json(['total_pages' => $pages]);
}