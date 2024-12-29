<?php
/*
Plugin Name: Custom Product Shortlinks
Description: Displays a list of products in the admin panel and generates random short links for each product using a custom domain.
Version: 1.0
Author: Teo Kamalipour
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortlink-generator.php';

register_activation_hook( __FILE__, 'cps_activate_plugin' );
function cps_activate_plugin() {
    global $wpdb;

    // Create table for storing short links
    $table_name = $wpdb->prefix . 'product_shortlinks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        short_code VARCHAR(10) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY short_code (short_code),
        UNIQUE KEY product_id (product_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    add_option( 'cps_custom_domain', '' );
}

// Enqueue admin scripts
add_action( 'admin_enqueue_scripts', 'cps_enqueue_admin_scripts' );
function cps_enqueue_admin_scripts( $hook ) {
    if ( $hook === 'toplevel_page_product-shortlinks' ) {
        wp_enqueue_script( 'cps-admin-js', plugin_dir_url( __FILE__ ) . 'assets/admin.js', [], '1.0', true );
    }
}
