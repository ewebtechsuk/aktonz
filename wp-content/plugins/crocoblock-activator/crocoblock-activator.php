<?php
/*
Plugin Name: Crocoblock Activator
Plugin URI: https://www.gpltimes.com
Description: Automatically activates and licenses Crocoblock plugins by intercepting license validation requests.
Version: 1.0.2
Author: GPL Times
Author URI: https://www.gpltimes.com
Text Domain: crocoblock-activator
Domain Path: /languages
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!defined('ABSPATH')) {
    exit;
}

class Crocoblock_Activator {
    private $crocoblock_api_proxy_url = 'https://crocoblock.gpltimes.com';
    private $crocoblock_main_domain = 'crocoblock.com';
    private $is_proxy_request = false;
    
    public function __construct() {
        add_filter('pre_http_request', array($this, 'intercept_crocoblock_requests'), 10, 3);
    }

    private function is_crocoblock_license_url($url) {
        $parsed_url = parse_url($url);

        if (!isset($parsed_url['host'])) {
            return false;
        }

        $host = $parsed_url['host'];

        // Check if the host exactly matches crocoblock.com
        if ($host === $this->crocoblock_main_domain) {
            return true;
        }

        // Check if the host is a subdomain of crocoblock.com
        if (preg_match('/\.' . preg_quote($this->crocoblock_main_domain) . '$/', $host)) {
            return true;
        }

        return false;
    }
    
    private function extract_domain_from_url($url) {
        $parsed_url = parse_url($url);
        if (isset($parsed_url['host'])) {
            return $parsed_url['host'];
        }

        // This should never happen as we already validated the URL contains a Crocoblock domain
        // Log the error for debugging purposes
        error_log('Crocoblock Activator: Failed to extract domain from URL: ' . $url);

        // Use the main domain as fallback only in emergency
        return $this->crocoblock_main_domain;
    }
    
    public function intercept_crocoblock_requests($preempt, $args, $url) {
        if ($this->is_proxy_request) {
            return $preempt;
        }

        $method = isset($args['method']) ? $args['method'] : 'GET';
        if ($method !== 'GET') {
            return $preempt;
        }

        if ($this->is_crocoblock_license_url($url)) {
            $parsed_url = parse_url($url);
            $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
            $query = isset($parsed_url['query']) ? $parsed_url['query'] : '';

            // Check if there's a license parameter in the query
            parse_str($query, $query_params);
            if (!isset($query_params['license']) || empty($query_params['license'])) {
                // No license parameter, don't forward to our proxy
                return $preempt;
            }

            // Extract the original domain to pass to our worker
            $original_domain = $this->extract_domain_from_url($url);

            // Build the proxy URL with the original domain as a parameter
            $proxied_url = $this->crocoblock_api_proxy_url . $path;
            if ($query) {
                $proxied_url .= '?' . $query . '&original_domain=' . urlencode($original_domain);
            } else {
                $proxied_url .= '?original_domain=' . urlencode($original_domain);
            }

            $this->is_proxy_request = true;
            $response = wp_remote_get($proxied_url, $args);
            $this->is_proxy_request = false;

            if (is_wp_error($response)) {
                return $preempt;
            }

            return $response;
        }

        return $preempt;
    }
}

new Crocoblock_Activator();