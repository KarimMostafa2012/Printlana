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
    $error_message = '';

    if (isset($_POST['scan_css']) && check_admin_referer('pl_css_scan', 'pl_css_nonce')) {
        // Clear cache before scanning to force fresh results
        delete_transient('pl_css_scan_results');

        $scan_results = pl_scan_css_files();

        // Check for errors
        if (isset($scan_results['error'])) {
            $error_message = $scan_results['error'];
            $scan_results = array();
        }
    }

    if (isset($_POST['clear_cache']) && check_admin_referer('pl_css_scan', 'pl_css_nonce')) {
        delete_transient('pl_css_scan_results');
        echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
    }

    // Check for cached results
    if (empty($scan_results) && empty($error_message)) {
        $cached = get_transient('pl_css_scan_results');
        if ($cached && !isset($cached['error'])) {
            $scan_results = $cached;
        }
    }

    ?>
    <div class="wrap">
        <h1>Unused CSS Detector</h1>
        <p>This tool analyzes CSS files to identify potentially unused selectors. PageSpeed reports <strong>900ms potential savings</strong> from unused CSS.</p>

        <?php if (!empty($error_message)): ?>
            <div class="notice notice-error" style="padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Scan Failed</h3>
                <p><strong>Error:</strong> <?php echo esc_html($error_message); ?></p>
                <p>Common causes:</p>
                <ul>
                    <li>PHP timeout (increase max_execution_time in php.ini)</li>
                    <li>Memory limit too low (increase memory_limit in php.ini)</li>
                    <li>Cannot fetch homepage (check site URL and permalinks)</li>
                </ul>
                <p><strong>Alternative:</strong> Use WP Rocket's "Remove Unused CSS" feature for automated detection.</p>
            </div>
        <?php endif; ?>

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
                This will scan your theme and plugin CSS files. The process may take 30-60 seconds. Results are cached for 10 minutes.
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
    // Increase limits
    @ini_set('memory_limit', '256M');
    @set_time_limit(120);

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

    try {
        // Get homepage HTML for comparison
        $homepage_html = pl_get_page_html(home_url());

        if (empty($homepage_html)) {
            return array('error' => 'Could not fetch homepage HTML. Please check your site URL.');
        }

        // Manually find CSS files in common locations
        $css_files = pl_find_css_files();

        if (empty($css_files)) {
            return array('error' => 'No CSS files found to analyze.');
        }

        // Analyze each CSS file (limit to top 15 largest files)
        $count = 0;
        foreach ($css_files as $file_path) {
            if ($count >= 15) break; // Limit processing

            if (!file_exists($file_path)) {
                continue;
            }

            $file_size = filesize($file_path);

            // Skip very small files (< 2KB) or very large files (> 500KB)
            if ($file_size < 2048 || $file_size > 512000) {
                continue;
            }

            try {
                $file_data = pl_analyze_css_file($file_path, $homepage_html);

                if ($file_data && $file_data['total_selectors'] > 0) {
                    $results['files'][] = $file_data;
                    $results['summary']['files_scanned']++;
                    $results['summary']['total_selectors'] += $file_data['total_selectors'];
                    $results['summary']['used_selectors'] += $file_data['used_selectors'];
                    $results['summary']['unused_selectors'] += $file_data['unused_selectors'];
                    $count++;
                }
            } catch (Exception $e) {
                // Skip problematic files
                continue;
            }
        }

        if (empty($results['files'])) {
            return array('error' => 'No CSS files could be analyzed. Files may be too large or in unsupported format.');
        }

        // Sort files by unused percentage (worst first)
        usort($results['files'], function($a, $b) {
            return $b['unused_percent'] - $a['unused_percent'];
        });

        // Limit to top 10 files
        $results['files'] = array_slice($results['files'], 0, 10);

        // Calculate estimated savings (rough estimate)
        $total_kb = 0;
        foreach ($results['files'] as $file) {
            $total_kb += $file['size_kb'] * ($file['unused_percent'] / 100);
        }
        $results['summary']['estimated_savings'] = round($total_kb) . 'KB';

        // Cache results for 10 minutes
        set_transient('pl_css_scan_results', $results, 10 * MINUTE_IN_SECONDS);

        return $results;

    } catch (Exception $e) {
        return array('error' => 'Scan failed: ' . $e->getMessage());
    }
}

/**
 * Find CSS files in theme and plugin directories
 * Now scans ONLY files that are actually enqueued on the frontend
 */
function pl_find_css_files() {
    $css_files = array();

    // First, get actually enqueued styles by simulating frontend
    $enqueued_files = pl_get_enqueued_frontend_css();

    if (!empty($enqueued_files)) {
        // Use enqueued files as primary source
        $css_files = $enqueued_files;
    }

    // Theme CSS (child theme)
    $theme_dir = get_stylesheet_directory();
    if (is_dir($theme_dir)) {
        $theme_css = glob($theme_dir . '/*.css');
        if ($theme_css) {
            foreach ($theme_css as $file) {
                // Exclude RTL and editor CSS
                if (strpos($file, '-rtl.css') === false &&
                    strpos($file, 'editor-style') === false) {
                    $css_files[] = $file;
                }
            }
        }
        // Check css subdirectory
        if (is_dir($theme_dir . '/css')) {
            $theme_css_dir = glob($theme_dir . '/css/*.css');
            if ($theme_css_dir) {
                $css_files = array_merge($css_files, $theme_css_dir);
            }
        }
        // Check assets subdirectory
        if (is_dir($theme_dir . '/assets/css')) {
            $assets_css = glob($theme_dir . '/assets/css/*.css');
            if ($assets_css) {
                $css_files = array_merge($css_files, $assets_css);
            }
        }
    }

    // Parent theme CSS (hello-elementor)
    $parent_dir = get_template_directory();
    if (is_dir($parent_dir) && $parent_dir !== $theme_dir) {
        $parent_css = glob($parent_dir . '/*.css');
        if ($parent_css) {
            foreach ($parent_css as $file) {
                // Exclude RTL and editor CSS
                if (strpos($file, '-rtl.css') === false &&
                    strpos($file, 'editor-style') === false) {
                    $css_files[] = $file;
                }
            }
        }
    }

    // WooCommerce frontend CSS only (exclude admin and RTL)
    $wc_assets = WP_PLUGIN_DIR . '/woocommerce/assets/css';
    if (is_dir($wc_assets)) {
        $important_wc_files = array(
            'woocommerce.css',
            'woocommerce-layout.css',
            'woocommerce-smallscreen.css',
        );
        foreach ($important_wc_files as $filename) {
            $filepath = $wc_assets . '/' . $filename;
            if (file_exists($filepath)) {
                $css_files[] = $filepath;
            }
        }
    }

    // Elementor frontend CSS (in uploads/elementor/css)
    $elementor_css = WP_CONTENT_DIR . '/uploads/elementor/css';
    if (is_dir($elementor_css)) {
        $elem_files = glob($elementor_css . '/*.css');
        if ($elem_files) {
            foreach ($elem_files as $file) {
                // Include global.css and post-*.css files (exclude kit and custom-*.css)
                if (strpos(basename($file), 'global.css') !== false ||
                    strpos(basename($file), 'post-') === 0) {
                    $css_files[] = $file;
                }
            }
        }
    }

    // Elementor plugin CSS
    $elementor_plugin = WP_PLUGIN_DIR . '/elementor/assets/css';
    if (is_dir($elementor_plugin)) {
        $elem_plugin_files = array(
            'frontend.min.css',
            'frontend-legacy.min.css',
        );
        foreach ($elem_plugin_files as $filename) {
            $filepath = $elementor_plugin . '/' . $filename;
            if (file_exists($filepath)) {
                $css_files[] = $filepath;
            }
        }
    }

    // Elementor Pro CSS
    $elementor_pro = WP_PLUGIN_DIR . '/elementor-pro/assets/css';
    if (is_dir($elementor_pro)) {
        if (file_exists($elementor_pro . '/frontend.min.css')) {
            $css_files[] = $elementor_pro . '/frontend.min.css';
        }
    }

    // Common plugin directories (only frontend CSS)
    $plugin_dirs = array(
        WP_PLUGIN_DIR . '/dokan-lite/assets/css',
        WP_PLUGIN_DIR . '/dokan-pro/assets/css',
        WP_PLUGIN_DIR . '/yith-woocommerce-wishlist/assets/css',
        WP_PLUGIN_DIR . '/contact-form-7/includes/css',
    );

    foreach ($plugin_dirs as $plugin_dir) {
        if (is_dir($plugin_dir)) {
            $plugin_css = glob($plugin_dir . '/*.css');
            if ($plugin_css) {
                foreach ($plugin_css as $file) {
                    // Exclude admin, RTL, and editor CSS
                    $basename = basename($file);
                    if (strpos($basename, 'admin') === false &&
                        strpos($basename, '-rtl.css') === false &&
                        strpos($basename, 'editor') === false) {
                        $css_files[] = $file;
                    }
                }
            }
        }
    }

    // Remove duplicates
    $css_files = array_unique($css_files);

    // Filter out very small files (< 2KB) and very large files (> 500KB)
    $css_files = array_filter($css_files, function($file) {
        if (!file_exists($file)) return false;
        $size = filesize($file);
        return ($size >= 2048 && $size <= 512000);
    });

    // Sort by file size (largest first) and return
    usort($css_files, function($a, $b) {
        return filesize($b) - filesize($a);
    });

    return $css_files;
}

/**
 * Analyze a single CSS file (simplified for speed)
 */
function pl_analyze_css_file($file_path, $html) {
    $css_content = @file_get_contents($file_path);

    if (empty($css_content)) {
        return null;
    }

    // Limit analysis to first 100KB for performance
    if (strlen($css_content) > 102400) {
        $css_content = substr($css_content, 0, 102400);
    }

    // Remove comments and minify for faster processing
    $css_content = preg_replace('/\/\*.*?\*\//s', '', $css_content);
    $css_content = preg_replace('/\s+/', ' ', $css_content);

    // Extract selectors with simpler regex (faster but less accurate)
    preg_match_all('/([^{]+)\{/', $css_content, $matches);

    if (empty($matches[1])) {
        return null;
    }

    $selectors = array();
    $total_count = 0;

    foreach ($matches[1] as $selector_group) {
        // Split multiple selectors
        $parts = array_map('trim', explode(',', $selector_group));
        foreach ($parts as $selector) {
            if (!empty($selector) && strlen($selector) < 200) { // Skip malformed selectors
                $selectors[] = $selector;
                $total_count++;
            }
        }
    }

    // Limit to 500 selectors per file for performance
    $selectors = array_slice($selectors, 0, 500);

    // Quick check which selectors are used
    $used = 0;
    $unused = 0;
    $unused_sample = array();

    foreach ($selectors as $selector) {
        // Auto-assume these are used (can't check dynamically)
        if (preg_match('/@|:(hover|focus|active|visited|before|after|nth-|first-|last-|not\(|has\()/i', $selector)) {
            $used++;
            continue;
        }

        // Extract main identifier (class, id, or tag)
        $found = false;

        // Check for class
        if (preg_match('/\.([a-zA-Z0-9_-]+)/', $selector, $class_match)) {
            $class = $class_match[1];
            if (stripos($html, 'class="' . $class) !== false || stripos($html, 'class=\'' . $class) !== false || stripos($html, ' ' . $class . ' ') !== false) {
                $found = true;
            }
        }

        // Check for ID
        if (!$found && preg_match('/#([a-zA-Z0-9_-]+)/', $selector, $id_match)) {
            $id = $id_match[1];
            if (stripos($html, 'id="' . $id . '"') !== false || stripos($html, 'id=\'' . $id . '\'') !== false) {
                $found = true;
            }
        }

        // Check for common tags
        if (!$found && preg_match('/^(div|span|p|a|h[1-6]|ul|li|img|input|button|form|table|tr|td|section|article|nav|header|footer)[\s.#:\[]/', $selector)) {
            $found = true; // Assume common tags are used
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

    $total = $used + $unused;
    $unused_percent = $total > 0 ? round(($unused / $total) * 100) : 0;

    return array(
        'file' => basename($file_path),
        'path' => str_replace(ABSPATH, '', $file_path),
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
