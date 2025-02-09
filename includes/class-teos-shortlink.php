<?php
class Teos_Shortlink
{
    // Holds the singleton instance of the class
    private static $instance;

    // Returns the singleton instance of the class
    public static function instance()
    {
        // If the instance is not set, create a new one
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        // Return the instance
        return self::$instance;
    }

    // Constructor method
    public function __construct()
    {
        // Include necessary files
        $this->includes();
    }

    // Includes necessary files for the plugin
    private function includes()
    {
        // Include the admin class file
        require_once TEOS_PLUGIN_PATH . 'includes/class-teos-shortlink-admin.php';
        // Include the REST API class file
        require_once TEOS_PLUGIN_PATH . 'includes/class-teos-shortlink-rest-api.php';
    }

    // Activation callback function
    public static function activate()
    {
        // Include the installer class file
        require_once TEOS_PLUGIN_PATH . 'includes/class-teos-shortlink-installer.php';
        // Call the install method of the installer class
        Teos_Shortlink_Installer::install();
    }

    // Deactivation callback function
    public static function deactivate()
    {
        // Add deactivation logic if needed.
    }

    public static function refresh_shortlinks()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'teos-shortlink-plugin'));
        }

        require_once TEOS_PLUGIN_PATH . 'includes/class-teos-shortlink-installer.php';
        Teos_Shortlink_Installer::generate_shortlinks_for_all_products();

        wp_send_json_success(__('Shortlinks refreshed successfully.', 'teos-shortlink-plugin'));
    }
}