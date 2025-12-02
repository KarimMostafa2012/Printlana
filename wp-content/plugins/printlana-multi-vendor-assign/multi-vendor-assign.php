<?php
/**
 * Plugin Name:      Printlana – Product Vendor Assignment Tool
 * Description:      Assigns vendors to admin products for internal fulfillment tracking and displays them in the vendor dashboard.
 * Version:          1.1.5
 * Author:           Printlana
 */

if (!defined('ABSPATH'))
    exit;

class Printlana_Vendor_Assign_Tool
{

    const PAGE_SLUG = 'printlana-vendor-assign';
    const META_KEY = '_assigned_vendor_ids';
    private $is_on_plugin_page = false;

    public function __construct()
    {
        // Admin Page Hooks
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);

        // AJAX Hooks
        add_action('wp_ajax_pl_assign_vendors', [$this, 'ajax_assign_vendors']);
        add_action('wp_ajax_pl_unlink_vendors', [$this, 'ajax_unlink_vendors']);

        // Dokan Dashboard Hooks
        add_filter('dokan_pre_product_listing_args', [$this, 'show_assigned_products_in_vendor_dashboard'], 20);
        // Search Hook
        add_action('pre_get_posts', [$this, 'setup_search_filter']);
        // WP-Admin product meta box
        add_action('add_meta_boxes', [$this, 'admin_product_meta_box_init']);
        add_action('save_post', [$this, 'admin_product_meta_box_save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'assets_product_edit']);
    }

    public function show_assigned_products_in_vendor_dashboard($args)
    {
        if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
            return $args;
        }

        $vendor_id = absint(dokan_get_current_user_id());
        error_log("Vendor ID: " . $vendor_id);
        if (!$vendor_id) {
            unset($args['author'], $args['author__in'], $args['author_name']);
            $args['post__in'] = array(0);
            return $args;
        }

        // Clean author constraints
        unset($args['author'], $args['author__in'], $args['author_name'], $args['post__in']);

        global $wpdb;
        $key = self::META_KEY; // '_assigned_vendor_ids'
        $vid = (string) $vendor_id;

        // Default: no language filter
        $lang_sql = '';
        $lang_param = null;

        // If WPML is active, restrict to current language
        if (function_exists('icl_object_id') || defined('ICL_SITEPRESS_VERSION')) {
            $current_lang = apply_filters('wpml_current_language', null);
            if ($current_lang) {
                $lang_sql = " INNER JOIN {$wpdb->prefix}icl_translations t 
                        ON t.element_id = p.ID 
                       AND t.element_type = 'post_product'
                       AND t.language_code = %s ";
                $lang_param = $current_lang;
            }
        }


        // Get DISTINCT product IDs, from posts table, only real products
        $sql = $wpdb->prepare(
            "SELECT DISTINCT p.ID
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     {$lang_sql}
     WHERE pm.meta_key = %s
       AND p.post_type = 'product'
       AND p.post_status NOT IN ('trash', 'auto-draft', 'draft')
       AND (
             pm.meta_value REGEXP %s      /* i:1; */
          OR pm.meta_value REGEXP %s      /* s:1:\"1\" or s:2:\"30\" */
          OR pm.meta_value REGEXP %s      /* JSON arrays/objects: \"1\" */
       )",
            $lang_param ? [$lang_param, $key, '(^|;)i:' . $vid . '(;|$)', '(^|;)s:[0-9]+:"' . $vid . '";', '"' . $vid . '"'] : [$key, '(^|;)i:' . $vid . '(;|$)', '(^|;)s:[0-9]+:"' . $vid . '";', '"' . $vid . '"']
        );

        $ids = $wpdb->get_col($sql);

        // 🔍 DEBUG: log status of each matched product
        if (!empty($ids)) {
            foreach ($ids as $pid) {
                $status = get_post_status($pid);
                error_log(sprintf(
                    '[PL DEBUG] Vendor %d – product #%d has post_status="%s"',
                    $vendor_id,
                    $pid,
                    $status
                ));
            }

            error_log(sprintf(
                '[PL DEBUG] Vendor %d – total matched products: %d (raw IDs: %s)',
                $vendor_id,
                count($ids),
                implode(',', array_map('intval', $ids))
            ));
        }

        // Normalize & uniq for safety
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique($ids));

        error_log('[PL] matched product IDs for vendor ' . $vendor_id . ': ' . json_encode($ids));

        $assigned_count = count($ids);
        update_user_meta($vendor_id, '_pl_assigned_product_count', $assigned_count);

        // If nothing matched, force no results (and we can see the IDs log)
        if (empty($ids)) {
            $args['post__in'] = array(0);
            return $args;
        }

        // 2) Constrain Dokan’s listing strictly to these posts
        $args['post__in'] = array_map('intval', $ids);

        // (Optional) Let Dokan/others still set post_status/pagination, but they can’t escape post__in
        // Kill any broken empty NOT IN tax filters
        if (!empty($args['tax_query']) && is_array($args['tax_query'])) {
            $args['tax_query'] = array_values(array_filter($args['tax_query'], function ($clause) {
                return !(is_array($clause)
                    && isset($clause['operator'], $clause['terms'])
                    && 'NOT IN' === $clause['operator']
                    && empty($clause['terms']));
            }));
            if (empty($args['tax_query']))
                unset($args['tax_query']);
        }

        error_log("Final ARGS (with post__in): " . print_r($args, true));
        return $args;
    }

    // public function hide_placeholder_row() {
    //     if ( ! function_exists('dokan_is_seller_dashboard') || ! dokan_is_seller_dashboard() ) return;

    //     add_action('wp_head', function () {
    //         $placeholder_id = (int) get_option('pl_placeholder_product_id');
    //         if ( $placeholder_id ) {
    //             echo '<style>.dokan-dashboard .dokan-dashboard-content tr#post-' . (int)$placeholder_id . '{display:none !important;}</style>';
    //         }
    //     });
    // }

    /**
     * === WP-ADMIN: Meta box to manage assigned users on product edit ===
     */
    public function admin_product_meta_box_init()
    {
        add_meta_box(
            'pl_assigned_users',
            __('Assigned Users (Vendors/Admins)', 'printlana'),
            [$this, 'admin_product_meta_box_render'],
            'product',
            'side',
            'default'
        );
    }

    public function admin_product_meta_box_render($post)
    {
        // Nonce
        wp_nonce_field('pl_save_assigned_users', 'pl_assigned_users_nonce');

        // Load current value as an ARRAY of ints
        $current = get_post_meta($post->ID, self::META_KEY, true);
        $current = is_array($current) ? array_map('absint', $current) : [];

        // Choices
        $choices = get_users([
            'role__in' => ['administrator', 'seller', 'vendor'],
            'orderby' => 'display_name',
            'fields' => ['ID', 'display_name'],
            'number' => 1000,
        ]);

        echo '<p style="margin:0 0 6px;">' . esc_html__('Select one or more users:', 'printlana') . '</p>';

        // IMPORTANT: name ends with [] to submit an array
        echo '<select id="product-vendor-select" class="pl-select2" multiple name="pl_assigned_users[]" style="width:100%; min-height:160px;">';
        foreach ($choices as $u) {
            $selected = in_array($u->ID, $current, true) ? 'selected' : '';
            printf(
                '<option value="%d" %s>%s (#%d)</option>',
                $u->ID,
                $selected,
                esc_html($u->display_name),
                $u->ID
            );
        }
        echo '</select>';

        // Preview (helpful confirmation)
        echo '<div style="margin-top:8px; font-size:12px; color:#555;">';
        echo '<strong>' . esc_html__('Currently assigned:', 'printlana') . '</strong><br>';
        if (empty($current)) {
            echo '<em>' . esc_html__('None', 'printlana') . '</em>';
        } else {
            $names = array_map(function ($uid) {
                $u = get_user_by('id', $uid);
                return $u ? $u->display_name . ' (#' . $u->ID . ')' : 'User #' . $uid;
            }, $current);
            echo esc_html(implode(', ', $names));
        }
        echo '</div>';
    }

    public function assets_product_edit($hook)
    {
        // Only run on WP-Admin product edit screens
        if ($hook !== 'post.php' && $hook !== 'post-new.php')
            return;
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product')
            return;

        // Woo bundles SelectWoo (a fork of Select2). Load whichever is available.
        if (wp_script_is('selectWoo', 'registered')) {
            wp_enqueue_script('selectWoo');
            wp_enqueue_style('select2'); // Woo registers style under 'select2'
        } else {
            // Fallback to WooCommerce’s Select2 paths (you already used these in assets())
            wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', [], '4.0.13');
            wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', ['jquery'], '4.0.13', true);
        }

        // Small style nudge (matches what you did on your tool page)
        wp_add_inline_style('select2', '.select2-container { min-width:280px; max-width:520px; }');

        // Initialize it for the meta box field
// Build init script (use NOWDOC so $... inside JS isn't parsed by PHP)
        $init = <<<'JS'
    jQuery(function($){
        var $el = $('#product-vendor-select');
        if (!$el.length) return;
    
        // Use SelectWoo if available, else Select2
        var fn = $.fn.selectWoo ? 'selectWoo' : ($.fn.select2 ? 'select2' : null);
        if (!fn) return;
    
        $el[fn]({
            width: 'resolve',
            placeholder: 'Select users…',
            closeOnSelect: false,
            allowClear: true
        });
    });
JS;

        // Attach to whichever script was enqueued
        if (wp_script_is('selectWoo', 'enqueued')) {
            wp_add_inline_script('selectWoo', $init);
        } else {
            wp_add_inline_script('select2', $init);
        }

    }

    public function admin_product_meta_box_save($post_id, $post)
    {
        if ($post->post_type !== 'product')
            return;

        // Nonce / capability / autosave guards
        if (!isset($_POST['pl_assigned_users_nonce']))
            return;
        if (!wp_verify_nonce($_POST['pl_assigned_users_nonce'], 'pl_save_assigned_users'))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        // Collect array safely
        $vals = isset($_POST['pl_assigned_users']) ? (array) $_POST['pl_assigned_users'] : [];
        $vals = array_values(array_unique(array_map('absint', $vals)));

        // Persist
        if (empty($vals)) {
            delete_post_meta($post_id, self::META_KEY);
        } else {
            update_post_meta($post_id, self::META_KEY, $vals);
        }
    }

    public function setup_search_filter($query)
    {
        if (is_admin() && $query->is_main_query() && isset($_GET['page']) && $_GET['page'] === self::PAGE_SLUG) {
            $this->is_on_plugin_page = true;
            add_filter('posts_search', [$this, 'expand_admin_search_to_sku'], 10, 2);
        }
    }

    public function expand_admin_search_to_sku($search, $query)
    {
        if (!$this->is_on_plugin_page || !$query->is_search() || !$query->is_main_query()) {
            return $search;
        }

        global $wpdb;
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }

        $search = preg_replace('/^\s*AND\s*\((.*)\)\s*$/', '$1', $search);

        $sku_search = $wpdb->prepare(
            " OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key='_sku' AND meta_value LIKE %s)",
            '%' . $wpdb->esc_like($search_term) . '%'
        );

        $search = " AND ({$search} {$sku_search}) ";

        remove_filter('posts_search', [$this, 'expand_admin_search_to_sku'], 10);
        $this->is_on_plugin_page = false;

        return $search;
    }

    public function add_menu()
    {
        add_menu_page(__('Vendor Assignment', 'printlana'), __('Vendor Assignment', 'printlana'), 'manage_woocommerce', self::PAGE_SLUG, [$this, 'render_page'], 'dashicons-networking', 54);
    }

    public function assets($hook)
    {
        if (isset($_GET['page']) && $_GET['page'] === self::PAGE_SLUG) {
            wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', [], '4.0.13');
            wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', ['jquery'], '4.0.13', true);
            wp_add_inline_style('select2', '.select2-container { min-width:280px; max-width:520px; }');
        }
    }

    /**
     * Get other translation IDs for a given product (WPML).
     *
     * @param int $product_id
     * @return int[] Array of translated product IDs (excluding the original).
     */
    private function get_product_translation_ids(int $product_id): array
    {
        // If WPML is not active, do nothing.
        if (!defined('ICL_SITEPRESS_VERSION')) {
            return [];
        }

        // Get translation group ID (trid) for this product
        $trid = apply_filters('wpml_element_trid', null, $product_id, 'post_product');
        if (!$trid) {
            return [];
        }

        // Get all translations in this group
        $translations = apply_filters('wpml_get_element_translations', null, $trid, 'post_product');
        if (empty($translations) || !is_array($translations)) {
            return [];
        }

        $ids = [];
        foreach ($translations as $lang => $t) {
            $translated_id = isset($t->element_id) ? (int) $t->element_id : 0;
            if ($translated_id && $translated_id !== (int) $product_id) {
                $ids[] = $translated_id;
            }
        }

        return $ids;
    }


    public function ajax_assign_vendors()
    {
        check_ajax_referer('pl_vendor_assign_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $product_ids = isset($_POST['product_ids']) ? array_map('absint', (array) $_POST['product_ids']) : [];
        $vendor_ids_to_add = isset($_POST['vendor_ids']) ? array_map('absint', (array) $_POST['vendor_ids']) : [];

        if (empty($product_ids) || empty($vendor_ids_to_add)) {
            wp_send_json_error(['message' => 'Missing product or vendor IDs.'], 400);
        }

        $count = 0;

        foreach ($product_ids as $pid) {
            $existing_vendors = get_post_meta($pid, self::META_KEY, true);
            if (!is_array($existing_vendors)) {
                $existing_vendors = [];
            }
            $new_vendors = array_unique(array_merge($existing_vendors, $vendor_ids_to_add));
            update_post_meta($pid, self::META_KEY, $new_vendors);
            $count++;

            $translation_ids = $this->get_product_translation_ids($pid);

            if (!empty($translation_ids)) {
                foreach ($translation_ids as $tid) {
                    $existing_translation_vendors = get_post_meta($tid, self::META_KEY, true);

                    if (!is_array($existing_translation_vendors)) {
                        $existing_translation_vendors = [];
                    }

                    $new_translation_vendors = array_unique(array_merge($existing_translation_vendors, $vendor_ids_to_add));
                    update_post_meta($tid, self::META_KEY, $new_translation_vendors);
                }
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                '%d vendors assigned to %d products (including translations).',
                count($vendor_ids_to_add),
                $count
            )
        ]);
    }

    public function ajax_unlink_vendors()
    {
        check_ajax_referer('pl_vendor_assign_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $product_ids = isset($_POST['product_ids']) ? array_map('absint', (array) $_POST['product_ids']) : [];
        $vendor_ids_to_remove = isset($_POST['vendor_ids']) ? array_map('absint', (array) $_POST['vendor_ids']) : [];

        if (empty($product_ids) || empty($vendor_ids_to_remove)) {
            wp_send_json_error(['message' => 'Missing product or vendor IDs.'], 400);
        }

        $count = 0;
        foreach ($product_ids as $pid) {
            $existing_vendors = get_post_meta($pid, self::META_KEY, true);
            if (!is_array($existing_vendors) || empty($existing_vendors)) {
                continue;
            }
            $new_vendors = array_values(array_diff($existing_vendors, $vendor_ids_to_remove));
            update_post_meta($pid, self::META_KEY, $new_vendors);
            $count++;
        }

        wp_send_json_success(['message' => sprintf('%d vendors unlinked from %d products.', count($vendor_ids_to_remove), $count)]);
    }

    public function render_page()
    {
        // This method's content remains exactly the same as you had it.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission.', 'printlana'));
        }

        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $per_page = isset($_GET['per_page']) ? min(200, max(10, absint($_GET['per_page']))) : 20;
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $vendor_id = isset($_GET['vendor_id']) ? absint($_GET['vendor_id']) : 0;
        $admin_ids = get_users(['role' => 'administrator', 'fields' => 'ids']);

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'author__in' => !empty($admin_ids) ? $admin_ids : [0],
            'fields' => 'ids',
        ];

        if ($search !== '') {
            if (is_numeric($search) && intval($search) > 0) {
                $args['p'] = intval($search);
            } else {
                $args['s'] = $search;
            }
        }

        if ($vendor_id) {
            $args['meta_query'] = [['key' => self::META_KEY, 'value' => '"' . $vendor_id . '"', 'compare' => 'LIKE']];
        }

        $product_query = new WP_Query($args);
        $product_ids = $product_query->posts;

        $vendor_map = [];
        $all_vendor_ids = [];
        $vendors_info = [];
        if (!empty($product_ids)) {
            _prime_post_caches($product_ids);
            global $wpdb;
            $meta_results = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN (" . implode(',', array_map('absint', $product_ids)) . ")", self::META_KEY));
            foreach ($meta_results as $row) {
                $v_ids = maybe_unserialize($row->meta_value);
                if (is_array($v_ids) && !empty($v_ids)) {
                    $vendor_map[$row->post_id] = $v_ids;
                    $all_vendor_ids = array_merge($all_vendor_ids, $v_ids);
                }
            }
            $all_vendor_ids = array_unique(array_filter(array_map('absint', $all_vendor_ids)));
            if (!empty($all_vendor_ids)) {
                $users = get_users(['include' => $all_vendor_ids, 'fields' => ['ID', 'display_name']]);
                foreach ($users as $user) {
                    $vendors_info[$user->ID] = $user->display_name;
                }
            }
        }

        $assignable_users = get_users(['role__in' => ['administrator', 'seller', 'vendor'], 'orderby' => 'display_name', 'number' => 1000]);
        $nonce = wp_create_nonce('pl_vendor_assign_nonce');
        ?>
        <div class="wrap">
            <style>
                .vendorsList {
                    margin: 0;
                    list-style: none;
                    padding-right: 24px;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px 12px;
                }
            </style>
            <h1><?php _e('Product User Assignment', 'printlana'); ?></h1>
            <p><?php _e('Assign internal fulfillment users (vendors or admins) to your products.', 'printlana'); ?></p>
            <div id="pl-assign-ui" style="margin:15px 0; padding:15px; border:1px solid #ccd0d4; background:#fff;">
                <strong><?php _e('1. Select Users to Assign', 'printlana'); ?>:</strong>
                <select id="pl-vendor-assign-select" multiple>
                    <?php foreach ($assignable_users as $u): ?>
                        <option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html($u->display_name . ' (#' . $u->ID . ')'); ?>
                        </option><?php endforeach; ?>
                </select>
                <button class="button button-primary"
                    id="pl-bulk-assign-btn"><?php _e('Assign to Selected Products', 'printlana'); ?></button>
                <span id="pl-bulk-assign-status" style="margin-left:10px;"></span>
            </div>
            <div id="pl-unlink-ui" style="margin:15px 0; padding:15px; border:1px solid #f3caca; background:#fff7f7;">
                <strong><?php _e('Bulk Remove Users', 'printlana'); ?>:</strong>
                <select id="pl-vendor-unlink-select" multiple>
                    <?php foreach ($assignable_users as $u): ?>
                        <option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html($u->display_name . ' (#' . $u->ID . ')'); ?>
                        </option><?php endforeach; ?>
                </select>
                <button class="button"
                    id="pl-bulk-unlink-btn"><?php _e('Remove from Selected Products', 'printlana'); ?></button>
                <span id="pl-bulk-unlink-status" style="margin-left:10px;"></span>
            </div>
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                <p class="search-box">
                    <label class="screen-reader-text"
                        for="post-search-input"><?php _e('Search Products', 'printlana'); ?>:</label>
                    <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>">
                    <input type="submit" id="search-submit" class="button"
                        value="<?php esc_attr_e('Search Products', 'printlana'); ?>">
                </p>
            </form>
            <table class="widefat fixed striped" style="margin-top:20px;">
                <thead>
                    <tr>
                        <th style="width:28px"><input type="checkbox" id="pl-check-all"></th>
                        <th style="width: 360px;"><?php _e('Product', 'printlana'); ?></th>
                        <th><?php _e('Assigned Users', 'printlana'); ?></th>
                        <th style="width: 120px;"><?php _e('Actions', 'printlana'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($product_ids)): ?>
                        <tr>
                            <td colspan="4"><?php _e('No products found for your search term.', 'printlana'); ?></td>
                        </tr>
                    <?php else:
                        foreach ($product_ids as $pid):
                            $product = wc_get_product($pid);
                            if (!$product)
                                continue;
                            $assigned_user_ids = isset($vendor_map[$pid]) ? $vendor_map[$pid] : []; ?>
                            <tr>
                                <td><input type="checkbox" class="pl-row-cb" value="<?php echo $pid; ?>"></td>
                                <td><strong><a href="<?php echo esc_url(get_edit_post_link($pid)); ?>"
                                            target="_blank"><?php echo esc_html($product->get_name()); ?></a></strong>
                                    (#<?php echo $pid; ?>)<br><code>Creator: <?php echo esc_html(get_the_author_meta('display_name', get_post_field('post_author', $pid))); ?></code>
                                </td>
                                <td><?php if (empty($assigned_user_ids)): ?><em><?php _e('No users assigned.', 'printlana'); ?></em><?php else: ?>
                                        <ul class="vendorsList"><?php foreach ($assigned_user_ids as $user_id): ?>
                                                <li><?php echo esc_html(isset($vendors_info[$user_id]) ? $vendors_info[$user_id] : 'Unknown User #' . $user_id); ?><button
                                                        type="button" class="button-link-delete pl-unlink-single"
                                                        data-product-id="<?php echo $pid; ?>" data-vendor-id="<?php echo $user_id; ?>"
                                                        style="margin-left:5px; vertical-align:middle;">&times;</button></li>
                                            <?php endforeach; ?>
                                        </ul><?php endif; ?>
                                </td>
                                <td><button class="button pl-assign-row"
                                        data-product-id="<?php echo $pid; ?>"><?php _e('Assign', 'printlana'); ?></button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php if ($product_query->max_num_pages > 1) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $paged, 'total' => $product_query->max_num_pages]) . '</div></div>';
            } ?>
        </div>
        <script>
            jQuery(function ($) {
                $('#pl-vendor-assign-select, #pl-vendor-unlink-select, #product-vendor-select').select2({ width: 'resolve', placeholder: 'Select users…', closeOnSelect: false });

                async function doAjax(action, data) {
                    const nonce = '<?php echo esc_js($nonce); ?>';
                    const body = new URLSearchParams();
                    body.append('action', action);
                    body.append('nonce', nonce);

                    // Expand arrays into repeated keys (critical for PHP)
                    if (Array.isArray(data['product_ids'])) {
                        data['product_ids'].forEach(id => body.append('product_ids[]', id));
                    }
                    if (Array.isArray(data['vendor_ids'])) {
                        data['vendor_ids'].forEach(id => body.append('vendor_ids[]', id));
                    }

                    // Include any other scalar props if you ever add them
                    Object.entries(data).forEach(([k, v]) => {
                        if (k !== 'product_ids' && k !== 'vendor_ids') body.append(k, v);
                    });

                    const response = await fetch(ajaxurl, { method: 'POST', body });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result?.data?.message || 'Unknown server error.');
                    }
                    return result.data.message;
                }


                $('#pl-check-all').on('change', function () {
                    $('.pl-row-cb').prop('checked', $(this).is(':checked'));
                });

                // This function now handles ALL assignment actions
                async function handleAssignment(product_ids, vendor_ids, btn) {
                    if (!vendor_ids.length) {
                        alert('Please select one or more users from the "Select Users to Assign" box at the top.');
                        return;
                    }
                    if (!product_ids.length) {
                        alert('Please select one or more products.');
                        return;
                    }

                    const originalText = btn.text();
                    btn.prop('disabled', true).text('Assigning...');

                    try {
                        const message = await doAjax('pl_assign_vendors', { product_ids, vendor_ids });
                        alert('Success: ' + message);
                        location.reload();
                    } catch (e) {
                        btn.prop('disabled', false).text(originalText);
                    }
                }

                // Bulk assign
                $('#pl-bulk-assign-btn').on('click', function () {
                    const vendor_ids = $('#pl-vendor-assign-select').val() || [];
                    const product_ids = $('.pl-row-cb:checked').map((_, el) => el.value).get();
                    handleAssignment(product_ids, vendor_ids, $(this));
                });

                // Per-row assign
                $(document).on('click', '.pl-assign-row', function () {
                    const vendor_ids = $('#pl-vendor-assign-select').val() || [];
                    const product_id = $(this).data('product-id');
                    handleAssignment([product_id], vendor_ids, $(this));
                });

                // Unlink bulk
                $('#pl-bulk-unlink-btn').on('click', async function () {
                    const btn = $(this);
                    const vendor_ids = $('#pl-vendor-unlink-select').val() || [];
                    const product_ids = $('.pl-row-cb:checked').map((_, el) => el.value).get();
                    if (!vendor_ids.length || !product_ids.length) { alert('Please select users and products to remove.'); return; }
                    if (!confirm('Are you sure you want to remove the selected users from the selected products?')) return;
                    btn.prop('disabled', true).text('Removing...');
                    try {
                        const message = await doAjax('pl_unlink_vendors', { product_ids, vendor_ids });
                        alert('Success: ' + message);
                        location.reload();
                    } catch (e) {
                        btn.prop('disabled', false).text('Remove from Selected Products');
                    }
                });

                // Unlink single
                $(document).on('click', '.pl-unlink-single', async function () {
                    const btn = $(this);
                    const product_id = btn.data('product-id');
                    const vendor_id = btn.data('vendor-id');
                    if (!confirm('Are you sure you want to remove this user from this product?')) return;
                    btn.css('opacity', 0.5);
                    try {
                        await doAjax('pl_unlink_vendors', { product_ids: [product_id], vendor_ids: [vendor_id] });
                        btn.closest('li').fadeOut(300, function () { $(this).remove(); });
                    } catch (e) {
                        btn.css('opacity', 1);
                    }
                });

            });
        </script>
        <?php
    }
}

// add_action('admin_init', function () {
//     if ( ! current_user_can('manage_woocommerce') ) return;

//     $product_id = 19075; // ⬅️ put a product that SHOULD be assigned to vendor 1
//     $key = '_assigned_vendor_ids';

//     global $wpdb;
//     $raw = $wpdb->get_var( $wpdb->prepare(
//         "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s LIMIT 1",
//         $product_id, $key
//     ) );

//     error_log('[PL Dump] raw meta_value: ' . (is_string($raw) ? $raw : var_export($raw, true)));
//     error_log('[PL Dump] get_post_meta(true): ' . var_export(get_post_meta($product_id, $key, true), true));
// });


// This filter can stay as it is for admin "view as vendor" functionality
add_filter('dokan_pre_product_listing_args', function ($args) {
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return $args;
    }

    if (current_user_can('manage_woocommerce') && isset($_GET['pl-as-vendor'])) {
        $as_vendor = absint($_GET['pl-as-vendor']);
        if ($as_vendor) {
            add_filter('dokan_get_current_user_id', function ($id) use ($as_vendor) {
                return $as_vendor;
            });
        }
    }

    return $args;
}, 9);

new Printlana_Vendor_Assign_Tool();


add_action('wp_footer', function () {
    if (!function_exists('dokan_is_seller_dashboard') || !dokan_is_seller_dashboard()) {
        return;
    }
    if (!is_user_logged_in()) {
        return;
    }

    $vendor_id = dokan_get_current_user_id();
    $raw = get_user_meta($vendor_id, '_pl_assigned_product_count', true);

    // If meta never set at all, you can choose to NOT override
    if ($raw === '') {
        return;
    }

    // Cast safely (0 is valid – we WANT to show 0)
    $count = (int) $raw;
    ?>
    <script>
        jQuery(function ($) {
            var correctCount = <?php echo (int) $count; ?>;

            var $el = $('.dokan-dashboard .dokan-dashboard-content .dokan-product-listing-area .product-listing-top .dokan-listing-filter .active a');

            if (!$el.length) {
                return;
            }

            var oldText = $el[0].textContent;
            var parts = oldText.split("(");
            if (parts.length > 1) {
                var newText = parts[0] + '(' + correctCount + ')';
                $el.text(newText);
            }
        });
    </script>
    <?php
});

