<?php
class Teos_Shortlink_Installer
{
    public static function init()
    {
        register_activation_hook(__FILE__, array('Teos_Shortlink_Installer', 'install'));
    }

    public static function install()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'teo_shortlinks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_name varchar(255) NOT NULL,
            original_link text NOT NULL,
            shortlink varchar(255) NOT NULL UNIQUE,
            image_url text NOT NULL,
            description text NOT NULL,
            sku varchar(255) DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Generate shortlinks for all existing products
        self::generate_shortlinks_for_all_products();
    }

    public static function generate_shortlinks_for_all_products()
    {
        global $wpdb;

        $products = wc_get_products(['limit' => -1]);

        foreach ($products as $product) {
            // Check if shortlink already exists
            $shortlink_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}teo_shortlinks WHERE product_name = %s",
                $product->get_name()
            ));

            if (!$shortlink_exists) {
                // Generate shortlink
                $shortcode = wp_generate_password(6, false, false);

                // Insert shortlink into database
                $wpdb->insert(
                    "{$wpdb->prefix}teo_shortlinks",
                    array(
                        'product_name' => $product->get_name(),
                        'original_link' => $product->get_permalink(),
                        'shortlink' => $shortcode,
                        'image_url' => wp_get_attachment_image_url($product->get_image_id(), 'full'),
                        'description' => $product->get_short_description(),
                        'sku' => $product->get_sku(),
                    )
                );
            }
        }
    }

}