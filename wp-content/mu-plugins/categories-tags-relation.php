<?php
/**
 * Get cached related product_tag terms for a given product_cat.
 * Falls back to empty array if not set.
 *
 * @param int $product_cat_id
 * @param bool $as_terms Return WP_Term objects instead of IDs (default true).
 * @return array WP_Term[] or int[] (IDs)
 */
function pl_get_related_product_tags($product_cat_id, $as_terms = true)
{
    $product_cat_id = (int) $product_cat_id;
    if ($product_cat_id <= 0) {
        return [];
    }
    $tag_ids = get_term_meta($product_cat_id, '_pl_related_product_tags', true);
    if (!is_array($tag_ids)) {
        $tag_ids = [];
    }
    if (!$as_terms) {
        return $tag_ids;
    }
    if (empty($tag_ids)) {
        return [];
    }
    $terms = get_terms([
        'taxonomy' => 'product_tag',
        'include' => $tag_ids,
        'hide_empty' => false,
    ]);

    return is_wp_error($terms) ? [] : $terms;
}

/**
 * Rebuild and store related product_tag IDs for one product_cat.
 *
 * @param int $product_cat_id
 * @return int[] Related tag IDs stored
 */
function pl_rebuild_related_product_tags($product_cat_id)
{
    global $wpdb;

    $product_cat_id = (int) $product_cat_id;
    if ($product_cat_id <= 0) {
        return [];
    } // One efficient SQL join to collect distinct product_tag term IDs for products in the given category 
    $sql = "
        SELECT DISTINCT t2.term_id
        FROM {$wpdb->term_relationships} tr1
        INNER JOIN {$wpdb->term_taxonomy} tt1 ON tr1.term_taxonomy_id = tt1.term_taxonomy_id
        INNER JOIN {$wpdb->posts} p ON p.ID = tr1.object_id
        INNER JOIN {$wpdb->term_relationships} tr2 ON tr2.object_id = p.ID
        INNER JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
        INNER JOIN {$wpdb->terms} t2 ON t2.term_id = tt2.term_id
        WHERE tt1.taxonomy = 'product_cat'
          AND tt1.term_id = %d
          AND tt2.taxonomy = 'product_tag'
          AND p.post_status = 'publish'
          AND p.post_type = 'product'
    ";
    $tag_ids = $wpdb->get_col($wpdb->prepare($sql, $product_cat_id));
    $tag_ids = array_values(array_unique(array_map('intval', (array) $tag_ids)));

    // Store as term meta (atomic update)
    update_term_meta($product_cat_id, '_pl_related_product_tags', $tag_ids);

    return $tag_ids;
}

/**
 * Rebuild related tags for affected product categories of a given product ID.
 *
 * @param int $product_id
 */
function pl_refresh_cats_for_product($product_id)
{
    $product_id = (int) $product_id;
    if ($product_id <= 0)
        return; // Get product categories assigned to this product 
    $cats = wp_get_object_terms(
        $product_id,
        'product_cat',
        ['fields' => 'ids']
    );
    if (is_wp_error($cats) || empty($cats)) {
        return;
    }

    foreach ($cats as $cat_id) {
        pl_rebuild_related_product_tags((int) $cat_id);
    }
}

/**
 * When terms are set on a product (categories or tags), refresh affected categories.
 * Runs on both category and tag changes.
 */
add_action('set_object_terms', function ($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    // Only care about products and either product_cat or product_tag edits
    if ($taxonomy !== 'product_cat' && $taxonomy !== 'product_tag') {
        return;
    }
    if (get_post_type($object_id) !== 'product') {
        return;
    }
    pl_refresh_cats_for_product((int) $object_id);
}, 10, 6);

/**
 * Also catch standard product saves/publishes (e.g., attributes saved via UI).
 */
add_action('save_post_product', function ($post_id, $post, $update) {
    // Skip autosaves/revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id))
        return;

    pl_refresh_cats_for_product((int) $post_id);
}, 10, 3);


/**
 * Rebuild for all product categories (e.g., run once via WP-CLI or admin tool).
 */
function pl_rebuild_all_product_cat_relations()
{
    $cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (is_wp_error($cats) || empty($cats))
        return;

    foreach ($cats as $cat_id) {
        pl_rebuild_related_product_tags((int) $cat_id);
    }
}