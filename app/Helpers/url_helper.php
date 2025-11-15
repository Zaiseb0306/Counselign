<?php

/**
 * Custom URL Helper Functions
 * 
 * Additional helper functions for URL generation and management
 */

if (!function_exists('dynamic_base_url')) {
    /**
     * Get dynamic base URL based on current request
     * 
     * This function automatically detects the current host and protocol
     * to generate the appropriate base URL, making the application work
     * seamlessly with both localhost and IP addresses.
     * 
     * @param string $uri Optional URI to append to base URL
     * @return string The complete dynamic base URL
     */
    function dynamic_base_url(string $uri = ''): string
    {
        // Get the current host from server variables
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        
        // Remove port from host if it's the default port
        if (strpos($host, ':80') !== false && $protocol === 'http') {
            $host = str_replace(':80', '', $host);
        } elseif (strpos($host, ':443') !== false && $protocol === 'https') {
            $host = str_replace(':443', '', $host);
        }
        
        // Construct the base URL
        $baseUrl = $protocol . '://' . $host . '/Counselign/public/';
        
        // Append URI if provided
        if (!empty($uri)) {
            $baseUrl .= ltrim($uri, '/');
        }
        
        return $baseUrl;
    }
}

if (!function_exists('dynamic_site_url')) {
    /**
     * Get dynamic site URL for specific routes
     * 
     * @param string $uri The URI/route to append
     * @return string The complete dynamic site URL
     */
    function dynamic_site_url(string $uri = ''): string
    {
        return dynamic_base_url($uri);
    }
}

if (!function_exists('api_url')) {
    /**
     * Generate API endpoint URL
     * 
     * @param string $endpoint The API endpoint
     * @return string The complete API URL
     */
    function api_url(string $endpoint): string
    {
        return dynamic_base_url('api/' . ltrim($endpoint, '/'));
    }
}
