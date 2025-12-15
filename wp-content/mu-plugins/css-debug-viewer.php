<?php
/**
 * Plugin Name: CSS Debug Viewer
 * Description: Shows all CSS files enqueued on the current page (frontend only, for debugging)
 * Version: 1.0
 * Author: Performance Optimization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display enqueued CSS files in the footer (only when ?show_css=1 is in URL)
 */
add_action('wp_footer', 'pl_show_enqueued_css', 999999);
function pl_show_enqueued_css() {
    // Only show if ?show_css=1 is in URL
    if (!isset($_GET['show_css']) || $_GET['show_css'] != '1') {
        return;
    }

    // Only show to logged-in admins
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wp_styles;

    if (!isset($wp_styles) || !is_object($wp_styles)) {
        return;
    }

    $enqueued = array();

    // Get all enqueued styles
    foreach ($wp_styles->queue as $handle) {
        if (isset($wp_styles->registered[$handle])) {
            $style = $wp_styles->registered[$handle];

            // Get the file path
            $src = $style->src;

            // Convert URL to file path if possible
            $file_path = '';
            if (strpos($src, home_url()) !== false) {
                $file_path = str_replace(home_url(), ABSPATH, $src);
                $file_path = preg_replace('/\?.*$/', '', $file_path); // Remove query strings
            } elseif (strpos($src, '/') === 0) {
                $file_path = ABSPATH . ltrim($src, '/');
                $file_path = preg_replace('/\?.*$/', '', $file_path);
            }

            $file_exists = !empty($file_path) && file_exists($file_path);
            $file_size = $file_exists ? filesize($file_path) : 0;

            $enqueued[] = array(
                'handle' => $handle,
                'src' => $src,
                'file_path' => $file_path,
                'exists' => $file_exists,
                'size_kb' => $file_exists ? round($file_size / 1024, 1) : 0,
            );
        }
    }

    // Sort by size (largest first)
    usort($enqueued, function($a, $b) {
        return $b['size_kb'] - $a['size_kb'];
    });

    ?>
    <div style="position: fixed; top: 50px; right: 20px; background: white; border: 2px solid #333; padding: 20px; max-width: 600px; max-height: 80vh; overflow-y: auto; z-index: 999999; box-shadow: 0 4px 20px rgba(0,0,0,0.3); font-family: monospace; font-size: 12px;">
        <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #333; padding-bottom: 10px;">
            CSS Files Enqueued on This Page
        </h3>
        <p style="margin: 0 0 10px 0; color: #666; font-size: 11px;">
            Total: <?php echo count($enqueued); ?> files
        </p>

        <div style="max-height: 500px; overflow-y: auto;">
            <?php foreach ($enqueued as $style): ?>
                <div style="margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-left: 3px solid <?php echo $style['exists'] ? '#0073aa' : '#dc3232'; ?>;">
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">
                        <?php echo esc_html($style['handle']); ?>
                        <span style="float: right; color: #666; font-weight: normal;">
                            <?php echo $style['size_kb']; ?>KB
                        </span>
                    </div>
                    <div style="font-size: 10px; color: #666; word-break: break-all; margin-bottom: 3px;">
                        <?php echo esc_html(basename($style['file_path'] ?: $style['src'])); ?>
                    </div>
                    <?php if ($style['file_path']): ?>
                        <div style="font-size: 9px; color: #999; word-break: break-all;">
                            <?php echo esc_html(str_replace(ABSPATH, '', $style['file_path'])); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$style['exists'] && $style['file_path']): ?>
                        <div style="font-size: 9px; color: #dc3232; margin-top: 3px;">
                            ⚠️ File not found
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10px; color: #666;">
            <strong>How to use:</strong> Visit any page with <code>?show_css=1</code> in the URL to see this debug info.
        </div>

        <button onclick="this.parentElement.remove()" style="position: absolute; top: 10px; right: 10px; background: #dc3232; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">
            Close
        </button>
    </div>
    <?php
}
