<?php
/**
 * Plugin Name: Unused CSS Detector
 * Description: Detects unused CSS selectors in your theme and plugins
 * Version: 1.0
 * Author: Performance Optimization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for CSS analyzer
 */
add_action('admin_menu', 'pl_css_analyzer_menu');
function pl_css_analyzer_menu() {
    add_submenu_page(
        'tools.php',
        'Unused CSS Detector',
        'CSS Detector',
        'manage_options',
        'unused-css-detector',
        'pl_css_analyzer_page'
    );
}

/**
 * Render the analyzer page
 */
function pl_css_analyzer_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle scan request
    $scan_results = array();
    $scan_in_progress = false;

    if (isset($_POST['scan_css']) && check_admin_referer('pl_css_scan', 'pl_css_nonce')) {
        $scan_results = pl_scan_css_files();
    }

    if (isset($_POST['clear_cache']) && check_admin_referer('pl_css_scan', 'pl_css_nonce')) {
        delete_transient('pl_css_scan_results');
        echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
    }

    // Check for cached results
    if (empty($scan_results)) {
        $cached = get_transient('pl_css_scan_results');
        if ($cached) {
            $scan_results = $cached;
        }
    }

    ?>
    <div class="wrap">
        <h1>Unused CSS Detector</h1>
        <p>This tool analyzes CSS files to identify potentially unused selectors. PageSpeed reports <strong>900ms potential savings</strong> from unused CSS.</p>

        <div class="card" style="max-width: 900px; margin: 20px 0; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h2 style="margin-top: 0;">⚠️ Important Notes</h2>
            <ul style="margin: 10px 0;">
                <li><strong>This is a static analysis</strong> - it only checks HTML structure, not dynamic content loaded via JavaScript</li>
                <li><strong>False positives are common</strong> - selectors used in:
                    <ul>
                        <li>AJAX-loaded content (cart, checkout, modals)</li>
                        <li>JavaScript-generated elements (sliders, tabs)</li>
                        <li>Hover/focus/active states</li>
                        <li>Media queries for mobile/tablet</li>
                    </ul>
                </li>
                <li><strong>DO NOT blindly remove</strong> - always test thoroughly</li>
                <li><strong>Backup first</strong> - create a backup before making changes</li>
            </ul>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('pl_css_scan', 'pl_css_nonce'); ?>
            <p>
                <button type="submit" name="scan_css" class="button button-primary button-large">
                    🔍 Scan CSS Files
                </button>
                <?php if (!empty($scan_results)): ?>
                    <button type="submit" name="clear_cache" class="button button-secondary">
                        Clear Cache
                    </button>
                <?php endif; ?>
            </p>
            <p class="description">
                This will scan your theme and plugin CSS files. The process may take 30-60 seconds.
            </p>
        </form>

        <?php if (!empty($scan_results)): ?>
            <h2>Scan Results</h2>

            <?php if (isset($scan_results['summary'])): ?>
                <div class="card" style="max-width: 900px; margin: 20px 0;">
                    <h3>Summary</h3>
                    <ul>
                        <li><strong>Files scanned:</strong> <?php echo $scan_results['summary']['files_scanned']; ?></li>
                        <li><strong>Total selectors:</strong> <?php echo number_format($scan_results['summary']['total_selectors']); ?></li>
                        <li><strong>Used selectors:</strong> <span style="color: #00a32a;"><?php echo number_format($scan_results['summary']['used_selectors']); ?></span></li>
                        <li><strong>Potentially unused:</strong> <span style="color: #d63638;"><?php echo number_format($scan_results['summary']['unused_selectors']); ?></span></li>
                        <li><strong>Estimated savings:</strong> <strong><?php echo $scan_results['summary']['estimated_savings']; ?></strong></li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($scan_results['files'])): ?>
                <h3>Files with Unused CSS</h3>
                <p>Files are sorted by potential savings (largest first).</p>

                <?php foreach ($scan_results['files'] as $file_data): ?>
                    <div class="card" style="max-width: 900px; margin: 20px 0;">
                        <h4 style="margin-top: 0;">
                            <?php echo esc_html($file_data['file']); ?>
                        </h4>
                        <div style="margin-bottom: 10px;">
                            <span style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                                Total: <?php echo number_format($file_data['total_selectors']); ?>
                            </span>
                            <span style="background: #d1f0d1; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                                Used: <?php echo number_format($file_data['used_selectors']); ?>
                            </span>
                            <span style="background: #ffd1d1; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                                Unused: <?php echo number_format($file_data['unused_selectors']); ?>
                            </span>
                            <span style="background: #fff3cd; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                                ~<?php echo $file_data['size_kb']; ?>KB
                            </span>
                        </div>

                        <?php if (!empty($file_data['unused_sample'])): ?>
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; font-weight: bold;">View Sample Unused Selectors (first 20)</summary>
                                <div style="background: #f6f7f7; padding: 10px; margin-top: 10px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                    <?php foreach ($file_data['unused_sample'] as $selector): ?>
                                        <div style="padding: 2px 0; border-bottom: 1px solid #ddd;">
                                            <?php echo esc_html($selector); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>

                        <div style="margin-top: 10px; padding: 10px; background: #e7f5ff; border-left: 3px solid #2271b1;">
                            <strong>Recommendation:</strong>
                            <?php if ($file_data['unused_percent'] > 70): ?>
                                <span style="color: #d63638;">
                                    This file has a very high percentage of unused CSS (<?php echo $file_data['unused_percent']; ?>%).
                                    Consider reviewing and removing unused rules manually.
                                </span>
                            <?php elseif ($file_data['unused_percent'] > 40): ?>
                                <span style="color: #dba617;">
                                    Moderate amount of unused CSS (<?php echo $file_data['unused_percent']; ?>%).
                                    Review carefully before removing.
                                </span>
                            <?php else: ?>
                                <span style="color: #00a32a;">
                                    Low percentage of unused CSS (<?php echo $file_data['unused_percent']; ?>%).
                                    This file is relatively optimized.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="card" style="max-width: 900px; margin: 20px 0; background: #e7f5ff; border-left: 4px solid #2271b1;">
                <h3>Next Steps</h3>
                <ol>
                    <li><strong>Review the results carefully</strong> - Don't remove everything marked as "unused"</li>
                    <li><strong>Test on multiple pages</strong>:
                        <ul>
                            <li>Homepage</li>
                            <li>Product pages</li>
                            <li>Shop/category pages</li>
                            <li>Cart and checkout</li>
                            <li>My Account pages</li>
                        </ul>
                    </li>
                    <li><strong>Create a child theme CSS file</strong> with only the CSS you need</li>
                    <li><strong>Or use WP Rocket's "Remove Unused CSS"</strong> feature (recommended for safety)</li>
                    <li><strong>Consider using PurgeCSS</strong> (advanced) for automated unused CSS removal</li>
                </ol>
            </div>

        <?php endif; ?>

        <div class="card" style="max-width: 900px; margin: 20px 0;">
            <h3>Alternative Solutions (Safer)</h3>
            <p>Instead of manually removing CSS, consider these safer alternatives:</p>
            <ol>
                <li><strong>WP Rocket</strong> - Has built-in "Remove Unused CSS" that's safe and automated</li>
                <li><strong>Asset CleanUp</strong> - Disable entire CSS files on pages where they're not needed</li>
                <li><strong>Autoptimize</strong> - Minify and combine CSS files (reduces file count)</li>
                <li><strong>Critical CSS</strong> - Inline critical CSS and defer the rest</li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * Scan CSS files for unused selectors
 */
function pl_scan_css_files() {
    set_time_limit(300); // Allow up to 5 minutes

    $results = array(
        'files' => array(),
        'summary' => array(
            'files_scanned' => 0,
            'total_selectors' => 0,
            'used_selectors' => 0,
            'unused_selectors' => 0,
            'estimated_savings' => '0KB'
        )
    );

    // Get all enqueued CSS files
    global $wp_styles;
    wp_enqueue_scripts();
    do_action('wp_enqueue_scripts');

    if (!isset($wp_styles) || !is_object($wp_styles)) {
        return array('error' => 'Could not access WordPress styles');
    }

    // Get homepage HTML for comparison
    $homepage_html = pl_get_page_html(home_url());

    // Scan each CSS file
    foreach ($wp_styles->registered as $handle => $style) {
        if (empty($style->src)) {
            continue;
        }

        // Skip external CSS
        if (strpos($style->src, home_url()) === false && strpos($style->src, '/') === 0) {
            $file_path = ABSPATH . ltrim($style->src, '/');
        } elseif (strpos($style->src, home_url()) !== false) {
            $file_path = str_replace(home_url(), ABSPATH, $style->src);
        } else {
            continue; // External file
        }

        // Clean up query strings
        $file_path = preg_replace('/\?.*$/', '', $file_path);

        if (!file_exists($file_path)) {
            continue;
        }

        $file_size = filesize($file_path);

        // Skip very small files (< 1KB)
        if ($file_size < 1024) {
            continue;
        }

        $file_data = pl_analyze_css_file($file_path, $homepage_html);

        if ($file_data && $file_data['unused_selectors'] > 0) {
            $results['files'][] = $file_data;
            $results['summary']['files_scanned']++;
            $results['summary']['total_selectors'] += $file_data['total_selectors'];
            $results['summary']['used_selectors'] += $file_data['used_selectors'];
            $results['summary']['unused_selectors'] += $file_data['unused_selectors'];
        }
    }

    // Sort files by size (largest first)
    usort($results['files'], function($a, $b) {
        return $b['size_kb'] - $a['size_kb'];
    });

    // Limit to top 10 files
    $results['files'] = array_slice($results['files'], 0, 10);

    // Calculate estimated savings (rough estimate)
    $total_kb = 0;
    foreach ($results['files'] as $file) {
        $total_kb += $file['size_kb'] * ($file['unused_percent'] / 100);
    }
    $results['summary']['estimated_savings'] = round($total_kb) . 'KB';

    // Cache results for 1 hour
    set_transient('pl_css_scan_results', $results, HOUR_IN_SECONDS);

    return $results;
}

/**
 * Analyze a single CSS file
 */
function pl_analyze_css_file($file_path, $html) {
    $css_content = file_get_contents($file_path);

    if (empty($css_content)) {
        return null;
    }

    // Remove comments
    $css_content = preg_replace('/\/\*.*?\*\//s', '', $css_content);

    // Extract all CSS selectors (simplified regex - won't catch everything)
    preg_match_all('/([^\{\}]+)\{[^\}]*\}/s', $css_content, $matches);

    if (empty($matches[1])) {
        return null;
    }

    $selectors = array();
    foreach ($matches[1] as $selector_group) {
        // Split multiple selectors (e.g., "h1, h2, h3")
        $parts = explode(',', $selector_group);
        foreach ($parts as $selector) {
            $selector = trim($selector);
            if (!empty($selector) && !in_array($selector, $selectors)) {
                $selectors[] = $selector;
            }
        }
    }

    // Check which selectors are used
    $used = 0;
    $unused = 0;
    $unused_sample = array();

    foreach ($selectors as $selector) {
        // Skip pseudo-classes and media queries (would need dynamic checking)
        if (preg_match('/:(hover|focus|active|visited|before|after|nth-child|first-child|last-child)/i', $selector)) {
            $used++; // Assume these are used
            continue;
        }

        if (preg_match('/@media|@keyframes|@font-face/i', $selector)) {
            $used++; // Assume these are used
            continue;
        }

        // Simplify selector for checking (remove pseudo-elements, attributes)
        $simple_selector = preg_replace('/::?(before|after|hover|focus|active).*$/', '', $selector);
        $simple_selector = preg_replace('/\[.*?\]/', '', $simple_selector);

        // Extract class/id/tag names
        if (preg_match_all('/[.#]?[\w-]+/', $simple_selector, $parts)) {
            $found = false;
            foreach ($parts[0] as $part) {
                $part = trim($part);
                if (empty($part)) continue;

                // Check if this class/id/tag exists in HTML
                if (strpos($part, '.') === 0) {
                    // Class selector
                    $class = substr($part, 1);
                    if (preg_match('/class=["\'][^"\']*\b' . preg_quote($class, '/') . '\b[^"\']*["\']/', $html)) {
                        $found = true;
                        break;
                    }
                } elseif (strpos($part, '#') === 0) {
                    // ID selector
                    $id = substr($part, 1);
                    if (preg_match('/id=["\']' . preg_quote($id, '/') . '["\']/', $html)) {
                        $found = true;
                        break;
                    }
                } else {
                    // Tag selector
                    if (preg_match('/<' . preg_quote($part, '/') . '[\s>]/', $html)) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                $used++;
            } else {
                $unused++;
                if (count($unused_sample) < 20) {
                    $unused_sample[] = $selector;
                }
            }
        }
    }

    $total = $used + $unused;
    $unused_percent = $total > 0 ? round(($unused / $total) * 100) : 0;

    return array(
        'file' => basename($file_path),
        'path' => $file_path,
        'total_selectors' => $total,
        'used_selectors' => $used,
        'unused_selectors' => $unused,
        'unused_percent' => $unused_percent,
        'size_kb' => round(filesize($file_path) / 1024, 1),
        'unused_sample' => $unused_sample
    );
}

/**
 * Get HTML content of a page
 */
function pl_get_page_html($url) {
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'sslverify' => false
    ));

    if (is_wp_error($response)) {
        return '';
    }

    return wp_remote_retrieve_body($response);
}
