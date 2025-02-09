<?php
class Teos_Shortlink_Rest_API
{
    public function __construct()
    {
        // Check if REST API is enabled
        if (get_option('teo_enable_rest_api', 1)) {
            add_action('rest_api_init', [$this, 'register_endpoints']);
            add_filter('rest_pre_serve_request', [$this, 'add_cors_headers']);
        }
    }

    public function register_endpoints()
    {
        register_rest_route(
            'teo-shortlinks/v1',
            '/links',
            [
                'methods' => 'GET',
                'callback' => [$this, 'fetch_all_shortlinks'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'teo-shortlinks/v1',
            '/link/(?P<shortcode>[a-zA-Z0-9]+)',
            [
                'methods' => 'GET',
                'callback' => [$this, 'fetch_shortlink'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function fetch_all_shortlinks()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'teo_shortlinks';
        $results = $wpdb->get_results("SELECT * FROM $table_name");
        return rest_ensure_response($results);
    }

    public function fetch_shortlink($request)
    {
        $shortcode = $request['shortcode'];
        global $wpdb;
        $table_name = $wpdb->prefix . 'teo_shortlinks';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE shortlink = %s", $shortcode));
        if ($result) {
            return rest_ensure_response($result);
        }
        return new WP_Error('not_found', 'Shortlink not found', ['status' => 404]);
    }

    // Add CORS headers to REST API responses
    public function add_cors_headers($value)
    {
        header('Access-Control-Allow-Origin: *'); // Allow all origins or specify a domain like 'https://example.com'
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    }
}

new Teos_Shortlink_Rest_API();
