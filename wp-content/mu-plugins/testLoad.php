<?php
// /wp-content/mu-plugins/ajax-profiler.php
if ( defined('DOING_AJAX') && DOING_AJAX ) {
    add_action( 'init', function () {
        global $wpdb;
        $GLOBALS['__ajax_profiler_start'] = microtime(true);
        $GLOBALS['__ajax_profiler_qstart'] = $wpdb->num_queries ?? 0;
    }, 0 );

    add_action( 'shutdown', function () {
        if ( ! isset($_REQUEST['action']) ) return;
        global $wpdb, $current_user;
        $action   = sanitize_text_field($_REQUEST['action']);
        $elapsed  = round( ( microtime(true) - ($GLOBALS['__ajax_profiler_start'] ?? microtime(true)) ) * 1000 );
        $queries  = ($wpdb->num_queries ?? 0) - ($GLOBALS['__ajax_profiler_qstart'] ?? 0);
        $mem      = size_format( memory_get_peak_usage(true) );
        $role     = is_user_logged_in() ? implode(',', $current_user->roles) : 'guest';
        $url      = wp_get_raw_referer() ?: '';
        $logline  = sprintf('[%s] action=%s role=%s ms=%d q=%d mem=%s ip=%s referer=%s',
            gmdate('Y-m-d H:i:s'),
            $action, $role, $elapsed, $queries, $mem, $_SERVER['REMOTE_ADDR'] ?? '-', $url
        );
        error_log( 'AJAXPROF ' . $logline );
    }, PHP_INT_MAX );
}

