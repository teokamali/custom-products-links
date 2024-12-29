<?php
/*
Plugin Name: Custom Product Shortlinks
Description: Displays a list of products in the admin panel and generates random short links for each product using a custom domain.
Version: 1.2
Author: Teo Kamalipour
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortlink-generator.php';

register_activation_hook( __FILE__, 'cps_activate_plugin' );
register_activation_hook( __FILE__, 'cps_activate_plugin' );
function cps_activate_plugin() {
    error_log('Plugin activation started');
    
    global $wpdb;

    $table_name = $wpdb->prefix . 'product_shortlinks';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists before attempting to create it
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
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
        error_log('Table created or updated');
    } else {
        error_log('Table already exists');
    }

    add_option( 'cps_custom_domain', '' );
}

// Enqueue admin scripts
add_action( 'admin_enqueue_scripts', 'cps_enqueue_admin_scripts' );
function cps_enqueue_admin_scripts( $hook ) {
    if ( $hook === 'toplevel_page_product-shortlinks' ) {
        wp_enqueue_script( 'cps-admin-js', plugin_dir_url( __FILE__ ) . 'assets/admin.js', [], '1.0', true );
    }
}

// Register REST API route
add_action( 'rest_api_init', function() {
    register_rest_route( 'cps/v1', '/shortlinks', [
        'methods' => 'GET',
        'callback' => 'cps_get_all_shortlinks',
        'permission_callback' => '__return_true', // Adjust the permissions as needed
    ]);
});

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

//Get Single Product
add_action( 'rest_api_init', 'cps_register_shortlink_api' );
function cps_register_shortlink_api() {
    register_rest_route( 'cps/v1', '/shortlinks/(?P<short_code>[a-zA-Z0-9]+)', [
        'methods' => 'GET',
        'callback' => 'cps_get_shortlink_by_code',
        'args' => [
            'short_code' => [
                'validate_callback' => function( $param, $request, $key ) {
                    // Validate the short_code (optional)
                    return preg_match( '/^[a-zA-Z0-9]{6}$/', $param );
                }
            ]
        ]
    ]);
}
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