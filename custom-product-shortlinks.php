<?php
/*
Plugin Name: Custom Product Shortlinks
Description: Displays a list of products in the admin panel and generates short links with a custom domain.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortlink-generator.php';

// Activation hook
register_activation_hook( __FILE__, 'cps_activate_plugin' );
function cps_activate_plugin() {
    add_option( 'cps_custom_domain', '' );
}