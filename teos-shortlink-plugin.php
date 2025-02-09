<?php
/**
 * Plugin Name: Teo's Shortlink Plugin
 * Description: Generate and manage shortlinks for WooCommerce products.
 * Version: 1.1.0
 * Author: Teo Kamalipour
 * License: GPLv2 or later
 * Text Domain: teos-shortlink-plugin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define constants.
define('TEOS_PLUGIN_VERSION', '1.0.0');
define('TEOS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TEOS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core files.
require_once TEOS_PLUGIN_PATH . 'includes/class-teos-shortlink.php';

// Register activation and deactivation hooks.
register_activation_hook(__FILE__, ['Teos_Shortlink', 'activate']);
register_deactivation_hook(__FILE__, ['Teos_Shortlink', 'deactivate']);

// Register AJAX action for refreshing shortlinks
add_action('wp_ajax_teos_refresh_shortlinks', ['Teos_Shortlink', 'refresh_shortlinks']);

// Initialize the plugin.
Teos_Shortlink::instance();