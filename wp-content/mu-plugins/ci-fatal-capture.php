<?php
/**
 * CI Fatal Capture MU Plugin
 * Logs fatal (shutdown) errors with context to wp-content/fatal-requests.log
 * to aid in diagnosing intermittent HTTP 500 issues during CI / production.
 */

if (defined('CI_FATAL_CAPTURE_LOADED')) { return; }
define('CI_FATAL_CAPTURE_LOADED', true);

if (!function_exists('ci_fatal_capture_register')) {
    function ci_fatal_capture_register() {
        register_shutdown_function('ci_fatal_capture_shutdown');
    }
}

if (!function_exists('ci_fatal_capture_is_fatal')) {
    function ci_fatal_capture_is_fatal($type) {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }
}

if (!function_exists('ci_fatal_capture_shutdown')) {
    function ci_fatal_capture_shutdown() {
        $err = error_get_last();
        if (!$err || !ci_fatal_capture_is_fatal($err['type'])) {
            return; // non-fatal or no error
        }
        $log_file = WP_CONTENT_DIR . '/fatal-requests.log';
        $time = date('c');
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $mem = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $active_plugins = function_exists('get_option') ? @get_option('active_plugins') : [];
        if (!is_array($active_plugins)) { $active_plugins = []; }
        $theme = function_exists('wp_get_theme') ? wp_get_theme()->get_stylesheet() : '';
        $wp_version = isset($GLOBALS['wp_version']) ? $GLOBALS['wp_version'] : '';
        $php_version = PHP_VERSION;
        $data = [
            'ts' => $time,
            'type' => $err['type'],
            'message' => $err['message'],
            'file' => $err['file'],
            'line' => $err['line'],
            'request' => [
                'host' => $host,
                'method' => $method,
                'uri' => $uri,
                'user_agent' => $ua,
                'referer' => $ref,
            ],
            'memory' => ['current' => $mem, 'peak' => $peak],
            'plugins_active_count' => count($active_plugins),
            'plugins_active' => $active_plugins,
            'theme' => $theme,
            'wp_version' => $wp_version,
            'php_version' => $php_version,
        ];
        $line = json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
        // Suppress errors to avoid recursive issues
        if (is_writable(WP_CONTENT_DIR) || (!file_exists($log_file) && is_writable(WP_CONTENT_DIR))) {
            @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
        }
    }
}

ci_fatal_capture_register();
