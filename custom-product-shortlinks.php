<?php
/*
Plugin Name: Custom Product Shortlinks
Description: Displays a list of products in the admin panel and generates random short links for each product using a custom domain.
Version: 2
Author: Teo Kamalipour
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortlink-generator.php';

// Activation hook
register_activation_hook( __FILE__, 'cps_activate_plugin' );
function cps_activate_plugin() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'product_shortlinks';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table exists before attempting to create it
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            short_code VARCHAR(10) NOT NULL,
            product_title TEXT NOT NULL,
            product_url TEXT NOT NULL,
            product_image_url TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY short_code (short_code),
            UNIQUE KEY product_id (product_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    add_option( 'cps_custom_domain', '' );
    add_option( 'cps_enable_api', '1' ); // Default to enabled

    // Grant `manage_woocommerce` capability to `shop_manager` if not already assigned
    $role = get_role( 'shop_manager' );
    if ( $role && !$role->has_cap( 'manage_woocommerce' ) ) {
        $role->add_cap( 'manage_woocommerce' );
    }
}
// Enqueue admin scripts
add_action( 'admin_enqueue_scripts', 'cps_enqueue_admin_scripts' );
function cps_enqueue_admin_scripts( $hook ) {
    if ( $hook === 'toplevel_page_product-shortlinks' ) {
        wp_enqueue_script( 'cps-admin-js', plugin_dir_url( __FILE__ ) . 'assets/admin.js', [], '1.0', true );
    }
}

// Register REST API routes conditionally based on admin settings
add_action( 'rest_api_init', 'cps_register_rest_api_routes' );
function cps_register_rest_api_routes() {
    // Check if the API is enabled
    if ( get_option( 'cps_enable_api', '1' ) != '1' ) {
        return; // Do not register routes if API is disabled
    }

    // Register the route for listing all shortlinks
    register_rest_route( 'cps/v1', '/shortlinks', [
        'methods' => 'GET',
        'callback' => 'cps_get_all_shortlinks',
        'permission_callback' => '__return_true', // Adjust the permissions as needed
    ]);

    // Register the route for getting a single shortlink by short_code
    register_rest_route( 'cps/v1', '/shortlinks/(?P<short_code>[a-zA-Z0-9]+)', [
        'methods' => 'GET',
        'callback' => 'cps_get_shortlink_by_code',
        'args' => [
            'short_code' => [
                'validate_callback' => function( $param, $request, $key ) {
                    return preg_match( '/^[a-zA-Z0-9]{6}$/', $param );
                }
            ]
        ]
    ]);
}

// Callback for getting all shortlinks
function cps_get_all_shortlinks() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'product_shortlinks';

    // Fetch all shortlinks data
    $results = $wpdb->get_results( "SELECT * FROM $table_name" );

    if ( empty( $results ) ) {
        return new WP_REST_Response( 'No data found', 404 );
    }

    // Return the results with product URL
    return new WP_REST_Response( $results, 200 );
}

// Callback for getting a single shortlink by its short_code
function cps_get_shortlink_by_code( $data ) {
    global $wpdb;
    $short_code = sanitize_text_field( $data['short_code'] );  // Get the short_code from the URL

    // Query the database for the row with the given short_code
    $table_name = $wpdb->prefix . 'product_shortlinks';
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE short_code = %s", $short_code ) );

    if ( !$row ) {
        return new WP_REST_Response( 'Shortlink not found', 404 );
    }

    // Format the data to match your response structure
    $response = [
        'id'                => $row->id,
        'product_id'        => $row->product_id,
        'short_code'        => $row->short_code,
        'product_title'     => $row->product_title,
        'product_url'       => $row->product_url,
        'product_image_url' => $row->product_image_url
    ];

    return new WP_REST_Response( $response, 200 );
}