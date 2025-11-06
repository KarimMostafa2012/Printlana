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
        'orderby' => 'include',
    ]);

    return is_wp_error($terms) ? [] : $terms;
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


// === Shortcode: list categories with their related tags ===
add_shortcode('pl_categories_with_related_tags', function ($atts) {
    $a = shortcode_atts([
        'parent' => 0,        // only direct children of this cat id (0 = all top-level)
        'hide_empty' => 'no',     // 'yes' or 'no'
        'columns' => 3,        // CSS class helper only
        'max_tags' => 0,        // 0 = show all
        'exclude' => '',       // extra tag IDs to exclude, comma-separated
        'orderby' => 'name',
        'order' => 'ASC',
    ], $atts, 'pl_categories_with_related_tags');

    $parent = (int) $a['parent'];
    $hide_empty = $a['hide_empty'] === 'yes';
    $columns = max(1, (int) $a['columns']);
    $max_tags = max(0, (int) $a['max_tags']);

    // Fetch product categories
    $cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => $hide_empty,
        'parent' => $parent,
        'orderby' => $a['include'],
        'order' => $a['order'],
    ]);
    if (is_wp_error($cats) || empty($cats)) {
        return '';
    }

    // Extra excludes passed to shortcode (IDs only)
    $extra_exclude = array_filter(array_map('intval', array_map('trim', explode(',', (string) $a['exclude']))));

    ob_start();
    echo '<div class="pl-cat-grid pl-cols-' . esc_attr($columns) . '">';

    foreach ($cats as $cat) {
        $cat_id = (int) $cat->term_id;

        // Read cached tags for this category (WP_Term[])
        $terms = pl_get_related_product_tags($cat_id, true);

        // Remove per-category excludes (set below) + extra excludes from shortcode
        if ($terms) {
            $ex = pl_get_excluded_tag_ids_for_cat($cat_id); // defined later
            $ex = array_unique(array_merge($ex, $extra_exclude));
            if ($ex) {
                $terms = array_values(array_filter($terms, function ($t) use ($ex) {
                    return !in_array((int) $t->term_id, $ex, true);
                }));
            }
        }

        // Cut to max
        if ($max_tags > 0 && $terms) {
            $terms = array_slice($terms, 0, $max_tags);
        }

        // Render one category card
        echo '<div class="pl-cat-card">';
        echo '<h3 class="pl-cat-name"><a href="' . esc_url(get_term_link($cat)) . '">' . esc_html($cat->name) . '</a></h3>';

        if (!empty($terms)) {
            echo '<ul class="pl-cat-related-tags">';
            foreach ($terms as $t) {
                echo '<li><a href="' . esc_url(get_term_link($t)) . '">' . esc_html($t->name) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="pl-no-tags">—</div>';
        }

        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
});

// === Term meta: Exclude tags per product_cat ===
add_action('product_cat_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label for="pl_related_tags_exclude">Exclude product tags</label>
        <input type="text" name="pl_related_tags_exclude" id="pl_related_tags_exclude"
            placeholder="IDs or slugs, comma-separated">
        <p class="description">Enter product_tag IDs or slugs to exclude for this category. Comma-separated.</p>
    </div>
    <?php
});

add_action('product_cat_edit_form_fields', function ($term) {
    $stored = get_term_meta($term->term_id, '_pl_related_tags_exclude', true);
    $as_list = '';
    if (is_array($stored) && $stored) {
        $as_list = implode(', ', array_map('strval', $stored));
    }
    ?>
    <tr class="form-field">
        <th scope="row"><label for="pl_related_tags_exclude">Exclude product tags</label></th>
        <td>
            <input type="text" name="pl_related_tags_exclude" id="pl_related_tags_exclude"
                value="<?php echo esc_attr($as_list); ?>" placeholder="IDs or slugs, comma-separated" />
            <p class="description">Enter product_tag IDs or slugs to exclude for this category. Comma-separated.</p>
        </td>
    </tr>
    <?php
});

// Save handler
add_action('created_product_cat', 'pl_save_product_cat_excludes');
add_action('edited_product_cat', 'pl_save_product_cat_excludes');
function pl_save_product_cat_excludes($term_id)
{
    if (!isset($_POST['pl_related_tags_exclude'])) {
        return;
    }
    $raw = (string) $_POST['pl_related_tags_exclude'];
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $ids = [];

    foreach ($parts as $p) {
        if (is_numeric($p)) {
            $ids[] = (int) $p;
        } else {
            $tag = get_term_by('slug', $p, 'product_tag');
            if (!$tag) {
                $tag = get_term_by('name', $p, 'product_tag'); // allow names too
            }
            if ($tag && !is_wp_error($tag)) {
                $ids[] = (int) $tag->term_id;
            }
        }
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    update_term_meta($term_id, '_pl_related_tags_exclude', $ids);
}

function pl_get_excluded_tag_ids_for_cat($product_cat_id)
{
    $ids = get_term_meta((int) $product_cat_id, '_pl_related_tags_exclude', true);
    if (!is_array($ids))
        return [];
    return array_values(array_unique(array_map('intval', $ids)));
}

function pl_rebuild_related_product_tags($product_cat_id)
{
    global $wpdb;

    $product_cat_id = (int) $product_cat_id;
    if ($product_cat_id <= 0) {
        return [];
    }

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

    // Subtract per-category excludes
    $excludes = pl_get_excluded_tag_ids_for_cat($product_cat_id);
    if ($excludes) {
        $tag_ids = array_values(array_diff($tag_ids, $excludes));
    }

    update_term_meta($product_cat_id, '_pl_related_product_tags', $tag_ids);

    return $tag_ids;
}
add_action('admin_menu', function () {
    add_management_page(
        'Rebuild Related Tags',
        'Rebuild Related Tags',
        'manage_options',
        'pl-rebuild-related-tags',
        function () {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to access this page.', 'pl'));
            }
            if (isset($_POST['pl_rebuild_all']) && check_admin_referer('pl_rebuild_all_nonce')) {
                $count = pl_rebuild_all_product_cat_relations();
                echo '<div class="updated"><p>Rebuilt related tags for ' . intval($count) . ' product categories.</p></div>';
            }
            echo '<div class="wrap"><h1>Rebuild Related Product Tags</h1>';
            echo '<p>This regenerates the cached related <code>product_tag</code> IDs for each <code>product_cat</code>. Per-category excludes are respected.</p>';
            echo '<form method="post">';
            wp_nonce_field('pl_rebuild_all_nonce');
            submit_button('Rebuild All Now', 'primary', 'pl_rebuild_all');
            echo '</form></div>';
        }
    );
});


// Return how many cats rebuilt
function pl_rebuild_all_product_cat_relations()
{
    $cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (is_wp_error($cats) || empty($cats))
        return 0;

    $n = 0;
    foreach ($cats as $cat_id) {
        pl_rebuild_related_product_tags((int) $cat_id);
        $n++;
    }
    return $n;
}


/**
 * Admin: Related Tags Dashboard (table view + inline excludes manager)
 */
add_action('admin_menu', function () {
    add_management_page(
        'Related Tags Dashboard',
        'Related Tags',
        'manage_options',
        'pl-related-tags-dashboard',
        'pl_render_related_tags_dashboard'
    );
});

function pl_render_related_tags_dashboard()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'pl'));
    }

    // Handle actions: add_exclude, remove_exclude, rebuild_cat, rebuild_all
    pl_related_tags_handle_actions();

    // Filters / search / pagination
    $per_page = 20;
    $paged = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $parent = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;

    $args = [
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'all',
        'number' => $per_page,
        'offset' => ($paged - 1) * $per_page,
    ];
    if ($search !== '') {
        // term name/slug search
        $args['search'] = '*' . $search . '*';
        $args['search_columns'] = ['name', 'slug'];
    }
    if ($parent > 0) {
        $args['parent'] = $parent;
    }

    // Get total count for pagination
    $count_args = $args;
    $count_args['number'] = 0;
    $count_args['offset'] = 0;
    $count_args['fields'] = 'count';
    $total = (int) get_terms($count_args);
    $cats = get_terms($args);
    $mode = isset($_GET['mode']) && $_GET['mode'] === 'all_minus_excludes' ? 'all_minus_excludes' : 'cached';

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Related Product Tags</h1>';

    echo '<form method="get" style="margin:12px 0;">';
    echo '<input type="hidden" name="page" value="pl-related-tags-dashboard" />';
    // In the filter form (near search/parent filters)
    echo '&nbsp;<label><input type="checkbox" name="mode" value="all_minus_excludes" ' . checked($mode, 'all_minus_excludes', false) . '> Show ALL tags minus excludes</label>';

    echo '<input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Search categories by name or slug" />';
    echo '&nbsp;<label>Parent: ';
    wp_dropdown_categories([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'name' => 'parent',
        'orderby' => 'name',
        'show_option_all' => __('All', 'pl'),
        'selected' => $parent,
    ]);
    echo '</label>&nbsp;';
    submit_button(__('Filter'), 'secondary', '', false);
    echo '&nbsp;';
    submit_button(__('Rebuild All'), 'secondary', 'pl_rebuild_all', false);
    wp_nonce_field('pl_rt_dashboard_actions', 'pl_rt_nonce');
    echo '</form>';

    // Table
    echo '<table class="widefat striped fixed">';
    echo '<thead><tr>';
    echo '<th style="width:24%">Category</th>';
    echo '<th style="width:36%">Related Tags (cached)</th>';
    echo '<th style="width:26%">Excluded Tags</th>';
    echo '<th style="width:14%">Actions</th>';
    echo '</tr></thead><tbody>';

    if (is_wp_error($cats) || empty($cats)) {
        echo '<tr><td colspan="4">No categories found.</td></tr>';
    } else {
        foreach ($cats as $cat) {
            $cat_id = (int) $cat->term_id;
            $ex_ids = pl_get_excluded_tag_ids_for_cat($cat_id);
            if ($mode === 'all_minus_excludes') {
                // get ALL product_tag terms, then subtract excludes
                $all_terms = pl_get_related_product_tags($cat_id, true);

                $related = [];
                if (!is_wp_error($all_terms) && !empty($all_terms)) {
                    if (!empty($ex_ids)) {
                        // keep only terms NOT in excludes
                        foreach ($all_terms as $t) {
                            if (!in_array((int) $t->term_id, $ex_ids, true)) {
                                $related[] = $t;
                            }
                        }
                    } else {
                        $related = $all_terms;
                    }
                }
            } else {
                // default: use the cached related tags
                $related = pl_get_related_product_tags($cat_id, true);
            }


            $ex_terms = !empty($ex_ids) ? get_terms([
                'taxonomy' => 'product_tag',
                'include' => $ex_ids,
                'hide_empty' => false,
                'orderby' => 'include',
            ]) : [];

            echo '<tr>';
            // Category cell
            echo '<td>';
            echo '<strong><a href="' . esc_url(get_edit_term_link($cat_id, 'product_cat')) . '">' . esc_html($cat->name) . '</a></strong>';
            echo '<br><span class="description">ID: ' . $cat_id . '</span>';
            echo '</td>';

            // Related tags cell
            echo '<td>';
            if (!empty($related)) {
                echo '<div class="pl-chipwrap">';
                foreach ($related as $t) {
                    echo '<span class="pl-chip" title="ID: ' . (int) $t->term_id . '">#' . esc_html($t->name) . '</span> ';
                }
                echo '</div>';
            } else {
                echo '<em>— None —</em>';
            }
            echo '</td>';

            // Excluded tags cell (with remove buttons)
            echo '<td>';
            if (!empty($ex_terms)) {
                echo '<div class="pl-chipwrap">';
                foreach ($ex_terms as $t) {
                    echo '<form method="post" style="display:inline-block; margin:2px;">';
                    wp_nonce_field('pl_rt_dashboard_actions', 'pl_rt_nonce');
                    echo '<input type="hidden" name="pl_action" value="remove_exclude" />';
                    echo '<input type="hidden" name="term_id" value="' . $cat_id . '" />';
                    echo '<input type="hidden" name="tag_id" value="' . (int) $t->term_id . '" />';
                    echo '<span class="pl-chip pl-chip--danger" title="ID: ' . (int) $t->term_id . '">#' . esc_html($t->name) . '</span> ';
                    submit_button('×', 'delete small', '', false, ['title' => 'Remove from exclude']);
                    echo '</form>';
                }
                echo '</div>';
            } else {
                echo '<em>— None —</em>';
            }

            // Add exclude inline form
            echo '<div style="margin-top:8px;">';
            echo '<form method="post" class="pl-inline">';
            wp_nonce_field('pl_rt_dashboard_actions', 'pl_rt_nonce');
            echo '<input type="hidden" name="pl_action" value="add_exclude" />';
            echo '<input type="hidden" name="term_id" value="' . $cat_id . '" />';
            echo '<input type="text" name="values" placeholder="IDs / slugs / names, comma-separated" style="min-width:260px;margin-bottom:12px;" />';
            submit_button('Add to Exclude', 'secondary', '', false);
            echo '</form>';
            echo '</div>';

            echo '</td>';

            // Actions cell
            echo '<td>';
            echo '<form method="post" style="display:inline-block">';
            wp_nonce_field('pl_rt_dashboard_actions', 'pl_rt_nonce');
            echo '<input type="hidden" name="pl_action" value="rebuild_cat" />';
            echo '<input type="hidden" name="term_id" value="' . $cat_id . '" />';
            submit_button('Rebuild', 'primary small', '', false);
            echo '</form>';
            echo '</td>';

            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    // Pagination
    $page_links = paginate_links([
        'base' => add_query_arg(['paged' => '%#%']),
        'format' => '',
        'prev_text' => __('«'),
        'next_text' => __('»'),
        'total' => max(1, ceil($total / $per_page)),
        'current' => $paged,
    ]);
    if ($page_links) {
        echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
    }

    // Some lightweight styles
    echo '<style>
    .pl-chipwrap{display:flex;flex-wrap:wrap;gap:6px}
    .pl-chip{display:inline-block;padding:4px 8px;border:1px solid #ccd0d4;border-radius:999px;background:#fff}
    .pl-chip--danger{border-color:#dc3232;background:#fff5f5}
    .pl-inline input[type=text]{margin-right:6px}
    </style>';

    echo '</div>';
}

/**
 * Handle dashboard actions: add_exclude, remove_exclude, rebuild_cat, rebuild_all
 */
function pl_related_tags_handle_actions()
{
    if (empty($_POST) && empty($_GET['pl_rebuild_all'])) {
        return;
    }
    // Nonce check
    $nonce_ok = isset($_POST['pl_rt_nonce']) && wp_verify_nonce($_POST['pl_rt_nonce'], 'pl_rt_dashboard_actions');
    // For GET rebuild_all button we set nonce in the filter form
    if (isset($_GET['pl_rebuild_all'])) {
        $nonce_ok = isset($_GET['pl_rt_nonce']) && wp_verify_nonce($_GET['pl_rt_nonce'], 'pl_rt_dashboard_actions');
    }
    if (!$nonce_ok) {
        return;
    }

    // Rebuild all (from filter form submit button)
    if (isset($_GET['pl_rebuild_all'])) {
        $count = pl_rebuild_all_product_cat_relations();
        add_action('admin_notices', function () use ($count) {
            echo '<div class="updated"><p>Rebuilt related tags for ' . intval($count) . ' product categories.</p></div>';
        });
        return;
    }

    $action = isset($_POST['pl_action']) ? sanitize_key($_POST['pl_action']) : '';
    $term_id = isset($_POST['term_id']) ? (int) $_POST['term_id'] : 0;
    if ($term_id <= 0) {
        return;
    }

    if ($action === 'rebuild_cat') {
        pl_rebuild_related_product_tags($term_id);
        add_action('admin_notices', function () use ($term_id) {
            echo '<div class="updated"><p>Rebuilt related tags for category ID ' . intval($term_id) . '.</p></div>';
        });
        return;
    }

    if ($action === 'add_exclude') {
        $raw = isset($_POST['values']) ? (string) wp_unslash($_POST['values']) : '';
        $parts = array_filter(array_map('trim', explode(',', $raw)));

        $ids = pl_normalize_tag_identifiers_to_ids($parts); // helper below

        if ($ids) {
            $current = pl_get_excluded_tag_ids_for_cat($term_id);
            $merged = array_values(array_unique(array_merge($current, $ids)));
            update_term_meta($term_id, '_pl_related_tags_exclude', $merged);
            // Rebuild this cat now
            pl_rebuild_related_product_tags($term_id);
            add_action('admin_notices', function () use ($term_id) {
                echo '<div class="updated"><p>Excluded tags updated and category rebuilt (ID ' . intval($term_id) . ').</p></div>';
            });
        }
        return;
    }

    if ($action === 'remove_exclude') {
        $tag_id = isset($_POST['tag_id']) ? (int) $_POST['tag_id'] : 0;
        if ($tag_id > 0) {
            $current = pl_get_excluded_tag_ids_for_cat($term_id);
            $new = array_values(array_diff($current, [$tag_id]));
            update_term_meta($term_id, '_pl_related_tags_exclude', $new);
            // Rebuild this cat now
            pl_rebuild_related_product_tags($term_id);
            add_action('admin_notices', function () use ($term_id) {
                echo '<div class="updated"><p>Removed tag from excludes and rebuilt (Category ID ' . intval($term_id) . ').</p></div>';
            });
        }
        return;
    }
}

/**
 * Helper: Normalize array of identifiers (IDs, slugs, names) into tag IDs
 */
function pl_normalize_tag_identifiers_to_ids(array $parts)
{
    $ids = [];
    foreach ($parts as $p) {
        if ($p === '')
            continue;
        if (is_numeric($p)) {
            $ids[] = (int) $p;
            continue;
        }
        $p = sanitize_title($p); // try slug first (fast)
        $tag = get_term_by('slug', $p, 'product_tag');
        if (!$tag || is_wp_error($tag)) {
            // fallback to name (slower)
            $tag = get_term_by('name', $p, 'product_tag');
        }
        if ($tag && !is_wp_error($tag)) {
            $ids[] = (int) $tag->term_id;
        }
    }
    return array_values(array_unique(array_filter(array_map('intval', $ids))));
}



/**
 * Render related tags for a given product_cat (respects excludes + cache).
 *
 * @param int   $cat_id  product_cat term_id
 * @param array $args    [
 *   'max'        => 0,            // 0 = all
 *   'show_icons' => false,        // if you store tag icons (thumbnail_id / pl_tag_icon), set true
 *   'icon_meta'  => ['thumbnail_id','pl_tag_icon'], // order to check
 *   'class'      => 'pl-related-tags', // wrapper class
 *   'as'         => 'list',       // 'list' = <ul>, 'inline' = span pills
 * ]
 * @return string HTML
 */
function pl_render_related_tags_for_cat($cat_id, $args = [])
{
    $cat_id = (int) $cat_id;
    if ($cat_id <= 0)
        return '';

    $a = wp_parse_args($args, [
        'max' => 0,
        'show_icons' => false,
        'icon_meta' => ['thumbnail_id', 'pl_tag_icon'],
        'class' => 'pl-related-tags',
        'as' => 'list', // list|inline
    ]);

    // get cached tags (these are already cleaned by excludes on rebuild)
    $tags = pl_get_related_product_tags($cat_id, true);

    // double-safety: subtract excludes again (in case someone changed excludes but hasn’t rebuilt yet)
    if ($tags) {
        $ex = pl_get_excluded_tag_ids_for_cat($cat_id);
        if ($ex) {
            $tags = array_values(array_filter($tags, fn($t) => !in_array((int) $t->term_id, $ex, true)));
        }
    }
    if ($a['max'] > 0 && $tags) {
        $tags = array_slice($tags, 0, (int) $a['max']);
    }
    if (empty($tags))
        return '';

    // optional icon getter
    $get_icon = function ($tag_id) use ($a) {
        if (empty($a['show_icons']))
            return '';
        foreach ((array) $a['icon_meta'] as $key) {
            if ($key === 'thumbnail_id') {
                $att_id = (int) get_term_meta($tag_id, 'thumbnail_id', true);
                if ($att_id) {
                    $url = wp_get_attachment_image_url($att_id, 'thumbnail');
                    if ($url)
                        return esc_url($url);
                }
            } else {
                $url = get_term_meta($tag_id, $key, true);
                if (is_string($url) && $url !== '')
                    return esc_url($url);
            }
        }
        return '';
    };

    ob_start();
    $wrap_open = $a['as'] === 'inline' ? '<div class="' . esc_attr($a['class']) . '">' : '<ul class="' . esc_attr($a['class']) . '">';
    $wrap_close = $a['as'] === 'inline' ? '</div>' : '</ul>';
    echo $wrap_open;

    foreach ($tags as $t) {
        $icon = $get_icon((int) $t->term_id);
        if ($a['as'] === 'inline') {
            echo '<a class="pl-tag-pill" href="' . esc_url(get_term_link($t)) . '">';
            if ($icon)
                echo '<span class="pl-tag-ico"><img src="' . $icon . '" alt=""></span>';
            echo '<span class="pl-tag-txt">' . esc_html($t->name) . '</span>';
            echo '</a>';
        } else {
            echo '<li><a href="' . esc_url(get_term_link($t)) . '">';
            if ($icon)
                echo '<span class="pl-tag-ico"><img src="' . $icon . '" alt=""></span> ';
            echo esc_html($t->name) . '</a></li>';
        }
    }

    echo $wrap_close;
    return ob_get_clean();
}

// Use in Elementor taxonomy Loop Item: [pl_cat_tags_auto max="10" as="inline" show_icons="no" class="pl-pills"]
add_shortcode('pl_cat_tags_auto', function ($atts) {
    $a = shortcode_atts([
        'max' => 10,
        'as' => 'inline',
        'show_icons' => 'no',
        'class' => 'pl-pills',
    ], $atts, 'pl_cat_tags_auto');

    $term_id = 0;

    // 1) Most loops: queried object is the current term
    $qo = get_queried_object();
    if ($qo && !empty($qo->term_id)) {
        $term_id = (int) $qo->term_id;
    }

    // 2) Elementor sometimes sets $GLOBALS['wp_query']->queried_object
    if (!$term_id && isset($GLOBALS['wp_query']) && is_object($GLOBALS['wp_query'])) {
        $qobj = $GLOBALS['wp_query']->get_queried_object();
        if ($qobj && !empty($qobj->term_id)) {
            $term_id = (int) $qobj->term_id;
        }
    }

    // 3) Some builds pass `elementor_loop` query var with term_id
    if (!$term_id && isset($GLOBALS['wp_query']->query) && is_array($GLOBALS['wp_query']->query)) {
        if (!empty($GLOBALS['wp_query']->query['term_id'])) {
            $term_id = (int) $GLOBALS['wp_query']->query['term_id'];
        }
    }

    // 4) Fallback: detect from global $post if loop unexpectedly runs in a post context with a single term
    if (!$term_id && isset($GLOBALS['post']->ID)) {
        $terms = get_the_terms((int) $GLOBALS['post']->ID, 'product_cat');
        if ($terms && !is_wp_error($terms) && count($terms) === 1) {
            $term_id = (int) $terms[0]->term_id;
        }
    }

    if (!$term_id) {
        return ''; // we couldn't detect the term in this context
    }

    return pl_render_related_tags_for_cat($term_id, [
        'max' => (int) $a['max'],
        'as' => $a['as'],
        'show_icons' => ($a['show_icons'] === 'yes'),
        'class' => $a['class'],
    ]);
});
// Register a minimal Elementor widget to render related tags for the current term
add_action('elementor/widgets/register', function ($widgets_manager) {
    if (!class_exists('\Elementor\Widget_Base'))
        return;

    class PL_Related_Tags_Widget extends \Elementor\Widget_Base
    {
        public function get_name()
        {
            return 'pl-related-tags';
        }
        public function get_title()
        {
            return __('PL Related Tags', 'pl');
        }
        public function get_icon()
        {
            return 'eicon-tags';
        }
        public function get_categories()
        {
            return ['general'];
        } // put in "General"; adjust if you have a custom category

        protected function register_controls()
        {
            $this->start_controls_section('section_settings', ['label' => __('Settings', 'pl')]);
            $this->add_control('max', [
                'label' => __('Max tags', 'pl'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 0,
                'description' => '0 = show all'
            ]);
            $this->add_control('as', [
                'label' => __('Layout', 'pl'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'inline',
                'options' => [
                    'inline' => __('Inline pills', 'pl'),
                    'list' => __('List (<ul>)', 'pl'),
                ]
            ]);
            $this->add_control('show_icons', [
                'label' => __('Show icons', 'pl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            ]);
            $this->add_control('class', [
                'label' => __('Wrapper CSS class', 'pl'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'pl-pills',
            ]);
            $this->end_controls_section();
        }

        protected function render()
        {
            // Try to detect current term (similar to shortcode)
            $term_id = 0;
            $qo = get_queried_object();
            if ($qo && !empty($qo->term_id)) {
                $term_id = (int) $qo->term_id;
            }
            if (!$term_id && isset($GLOBALS['wp_query'])) {
                $qobj = $GLOBALS['wp_query']->get_queried_object();
                if ($qobj && !empty($qobj->term_id)) {
                    $term_id = (int) $qobj->term_id;
                }
            }
            if (!$term_id && isset($GLOBALS['wp_query']->query['term_id'])) {
                $term_id = (int) $GLOBALS['wp_query']->query['term_id'];
            }
            if (!$term_id) {
                echo ''; // nothing we can do
                return;
            }

            $settings = $this->get_settings_for_display();
            echo pl_render_related_tags_for_cat($term_id, [
                'max' => (int) ($settings['max'] ?? 10),
                'as' => ($settings['as'] ?? 'inline'),
                'show_icons' => (($settings['show_icons'] ?? 'no') === 'yes'),
                'class' => ($settings['class'] ?? 'pl-pills'),
            ]);
        }
    }

    $widgets_manager->register(new PL_Related_Tags_Widget());
});

// [pl_term_debug] — prints what the loop "thinks" is current
add_shortcode('pl_term_debug', function () {
    ob_start();
    echo '<pre style="font:12px/1.4 monospace; white-space:pre-wrap; background:#fff; padding:8px; border:1px solid #ddd">';
    $out = [];

    $qo = get_queried_object();
    $out['get_queried_object'] = $qo ? [
        'type' => is_object($qo) ? get_class($qo) : gettype($qo),
        'term_id' => isset($qo->term_id) ? (int) $qo->term_id : null,
        'name' => isset($qo->name) ? $qo->name : null,
        'slug' => isset($qo->slug) ? $qo->slug : null,
        'taxonomy' => isset($qo->taxonomy) ? $qo->taxonomy : null,
    ] : null;

    global $wp_query, $post;
    if ($wp_query) {
        $qobj = $wp_query->get_queried_object();
        $out['wp_query->queried_object'] = $qobj ? [
            'term_id' => isset($qobj->term_id) ? (int) $qobj->term_id : null,
            'name' => isset($qobj->name) ? $qobj->name : null,
            'slug' => isset($qobj->slug) ? $qobj->slug : null,
            'taxonomy' => isset($qobj->taxonomy) ? $qobj->taxonomy : null,
        ] : null;
        $out['wp_query->query'] = $wp_query->query;
    }

    if ($post) {
        $out['global $post'] = ['ID' => (int) $post->ID, 'post_type' => get_post_type($post)];
        $out['terms of $post (product_cat)'] = get_the_terms((int) $post->ID, 'product_cat');
    }

    print_r($out);
    echo '</pre>';
    return ob_get_clean();
});


/**
 * Global holder for the term currently rendered in Elementor taxonomy loop.
 */
$GLOBALS['pl_current_loop_term_id'] = 0;

/**
 * When Elementor Pro builds taxonomy loops, it uses a WP_Term_Query internally.
 * We hook before each item render and set a global "current term id".
 */
add_action('elementor/frontend/loop_start', function( $query ){
    // reset at start
    $GLOBALS['pl_current_loop_term_id'] = 0;
}, 10, 1);

add_action('elementor/frontend/loop_item', function( $item ){
    // $item might be WP_Term or WP_Post depending on loop type
    if ( is_object($item) && isset($item->term_id) ) {
        $GLOBALS['pl_current_loop_term_id'] = (int) $item->term_id;
    } else {
        // Some builds pass array
        if ( is_array($item) && !empty($item['term_id']) ) {
            $GLOBALS['pl_current_loop_term_id'] = (int) $item['term_id'];
        }
    }
}, 10, 1);

add_action('elementor/frontend/loop_end', function( $query ){
    // clear after loop
    $GLOBALS['pl_current_loop_term_id'] = 0;
}, 10, 1);


// [pl_cat_tags_auto max="10" as="inline" show_icons="no" class="pl-pills"]
add_shortcode('pl_cat_tags_auto', function($atts){
    $a = shortcode_atts([
        'max'        => 10,
        'as'         => 'inline',
        'show_icons' => 'no',
        'class'      => 'pl-pills',
    ], $atts, 'pl_cat_tags_auto');

    $term_id = isset($GLOBALS['pl_current_loop_term_id']) ? (int)$GLOBALS['pl_current_loop_term_id'] : 0;

    // Fallbacks if hook didn't set it (depends on Elementor version)
    if (!$term_id) {
        $qo = get_queried_object();
        if ($qo && !empty($qo->term_id)) {
            $term_id = (int)$qo->term_id;
        }
    }

    if (!$term_id) return '';

    return pl_render_related_tags_for_cat($term_id, [
        'max'        => (int)$a['max'],
        'as'         => $a['as'],
        'show_icons' => ($a['show_icons'] === 'yes'),
        'class'      => $a['class'],
    ]);
});

