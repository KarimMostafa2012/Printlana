<?php
/**
 * Plugin Name: DOM Count Analyzer
 * Description: Analyzes Elementor pages to identify high DOM element counts
 * Version: 1.0
 * Author: Performance Optimization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for DOM analyzer
 */
add_action('admin_menu', 'pl_dom_analyzer_menu');
function pl_dom_analyzer_menu() {
    add_submenu_page(
        'tools.php',
        'DOM Count Analyzer',
        'DOM Counter',
        'manage_options',
        'dom-count-analyzer',
        'pl_dom_analyzer_page'
    );
}

/**
 * Render the analyzer page
 */
function pl_dom_analyzer_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle scan request
    $scan_results = array();
    if (isset($_POST['scan_pages']) && check_admin_referer('pl_dom_scan', 'pl_dom_nonce')) {
        $scan_results = pl_scan_elementor_pages();
    }

    ?>
    <div class="wrap">
        <h1>DOM Count Analyzer</h1>
        <p>This tool scans all Elementor pages and counts their DOM elements to identify pages that need optimization.</p>

        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2>PageSpeed Recommendation</h2>
            <p><strong>Target:</strong> Keep DOM size below 1,500 elements</p>
            <p><strong>Current site issue:</strong> 2,326 elements detected</p>
            <p>Pages with high DOM counts slow down rendering and increase memory usage.</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('pl_dom_scan', 'pl_dom_nonce'); ?>
            <p>
                <button type="submit" name="scan_pages" class="button button-primary button-large">
                    Scan All Pages
                </button>
            </p>
        </form>

        <?php if (!empty($scan_results)): ?>
            <h2>Scan Results</h2>
            <p>Found <?php echo count($scan_results); ?> pages with Elementor content</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50%;">Page Title</th>
                        <th style="width: 15%;">Estimated DOM</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scan_results as $result): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($result['title']); ?></strong>
                                <div style="color: #666; font-size: 12px;">
                                    <?php echo esc_html($result['type']); ?> • ID: <?php echo $result['id']; ?>
                                </div>
                            </td>
                            <td>
                                <strong style="font-size: 16px; <?php echo $result['dom_count'] > 1500 ? 'color: #d63638;' : 'color: #00a32a;'; ?>">
                                    ~<?php echo number_format($result['dom_count']); ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($result['dom_count'] > 1500): ?>
                                    <span style="color: #d63638; font-weight: bold;">⚠️ TOO HIGH</span>
                                <?php elseif ($result['dom_count'] > 1000): ?>
                                    <span style="color: #dba617; font-weight: bold;">⚡ Moderate</span>
                                <?php else: ?>
                                    <span style="color: #00a32a; font-weight: bold;">✅ Good</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($result['id']); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo get_permalink($result['id']); ?>" class="button button-small" target="_blank">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h3>How to Reduce DOM Count:</h3>
                <ol>
                    <li><strong>Remove unused sections:</strong> Delete sections you're not using</li>
                    <li><strong>Simplify complex layouts:</strong> Use fewer columns and nested containers</li>
                    <li><strong>Combine elements:</strong> Merge similar sections instead of duplicating</li>
                    <li><strong>Use Elementor experiments:</strong> Enable "Optimized DOM Output" in Elementor → Settings → Features</li>
                    <li><strong>Avoid excessive widgets:</strong> Each widget adds DOM elements</li>
                    <li><strong>Split long pages:</strong> Consider breaking very long pages into multiple pages</li>
                </ol>
            </div>

        <?php endif; ?>
    </div>
    <?php
}

/**
 * Scan all Elementor pages and estimate DOM count
 */
function pl_scan_elementor_pages() {
    $results = array();

    // Query all posts/pages with Elementor data
    $args = array(
        'post_type' => array('page', 'post', 'product'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_elementor_data',
                'compare' => 'EXISTS'
            )
        ),
        'orderby' => 'modified',
        'order' => 'DESC'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $elementor_data = get_post_meta($post_id, '_elementor_data', true);

            if (!empty($elementor_data)) {
                $dom_count = pl_estimate_dom_count($elementor_data);

                $results[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'type' => get_post_type(),
                    'dom_count' => $dom_count,
                );
            }
        }
        wp_reset_postdata();
    }

    // Sort by DOM count (highest first)
    usort($results, function($a, $b) {
        return $b['dom_count'] - $a['dom_count'];
    });

    return $results;
}

/**
 * Estimate DOM element count from Elementor data
 * This is an approximation based on Elementor structure
 */
function pl_estimate_dom_count($elementor_data) {
    // Decode JSON if it's a string
    if (is_string($elementor_data)) {
        $data = json_decode($elementor_data, true);
    } else {
        $data = $elementor_data;
    }

    if (empty($data) || !is_array($data)) {
        return 0;
    }

    $count = 0;

    // Recursively count elements
    $count += pl_count_elements_recursive($data);

    return $count;
}

/**
 * Recursively count Elementor elements
 */
function pl_count_elements_recursive($data) {
    $count = 0;

    if (!is_array($data)) {
        return 0;
    }

    foreach ($data as $element) {
        if (!is_array($element)) {
            continue;
        }

        // Each Elementor element creates DOM nodes
        // Base element + wrapper = ~2-5 DOM nodes
        $multiplier = 3;

        // Some elements create more DOM nodes
        if (isset($element['elType'])) {
            switch ($element['elType']) {
                case 'section':
                    $multiplier = 4; // section + container + row + col
                    break;
                case 'column':
                    $multiplier = 3; // column + wrapper + inner
                    break;
                case 'widget':
                    $multiplier = 5; // widget + wrapper + content + specific elements
                    break;
                case 'container':
                    $multiplier = 3;
                    break;
            }
        }

        $count += $multiplier;

        // Check for nested elements
        if (isset($element['elements']) && is_array($element['elements'])) {
            $count += pl_count_elements_recursive($element['elements']);
        }
    }

    return $count;
}

/**
 * Add admin notice with quick link
 */
add_action('admin_notices', 'pl_dom_analyzer_notice');
function pl_dom_analyzer_notice() {
    $screen = get_current_screen();

    // Only show on dashboard and Elementor pages
    if (!in_array($screen->id, array('dashboard', 'edit-elementor_library'))) {
        return;
    }

    // Check if we've already dismissed this notice
    if (get_user_meta(get_current_user_id(), 'pl_dom_notice_dismissed', true)) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" data-notice="pl_dom_notice">
        <p>
            <strong>Performance Tip:</strong> Your site has pages with high DOM counts (2,326 elements).
            <a href="<?php echo admin_url('tools.php?page=dom-count-analyzer'); ?>">Analyze pages now</a> to identify which need optimization.
        </p>
    </div>
    <script>
    jQuery(document).on('click', '.notice[data-notice="pl_dom_notice"] .notice-dismiss', function() {
        jQuery.post(ajaxurl, {
            action: 'pl_dismiss_dom_notice'
        });
    });
    </script>
    <?php
}

// Handle notice dismissal
add_action('wp_ajax_pl_dismiss_dom_notice', 'pl_dismiss_dom_notice');
function pl_dismiss_dom_notice() {
    update_user_meta(get_current_user_id(), 'pl_dom_notice_dismissed', true);
    wp_die();
}
