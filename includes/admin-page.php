<?php

// Add a new menu item in the admin panel
add_action( 'admin_menu', 'cps_add_admin_menu' );
function cps_add_admin_menu() {
    add_menu_page(
        'Product Shortlinks',
        'Product Shortlinks',
        'manage_options',
        'product-shortlinks',
        'cps_render_admin_page',
        'dashicons-admin-links',
        20
    );
}


function cps_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>Product Shortlinks</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'cps_settings_group' );
            do_settings_sections( 'product-shortlinks-settings' );
            submit_button( 'Save Custom Domain' );
            ?>
        </form>
        <hr>
        <h2>Products</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Short Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $products = cps_get_products();
                $custom_domain = get_option( 'cps_custom_domain', home_url() );
                foreach ( $products as $product ) {
                    // Fetch the short link for the product
                    $short_link = cps_generate_shortlink( $product->ID, $custom_domain );
                    echo "<tr>
                            <td>{$product->post_title}</td>
                            <td><input type='text' readonly value='{$short_link}' class='shortlink-input'></td>
                            <td><button class='button cps-copy-btn' data-link='{$short_link}'>Copy</button></td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Register settings for the custom domain
add_action( 'admin_init', 'cps_register_settings' );
function cps_register_settings() {
    register_setting( 'cps_settings_group', 'cps_custom_domain' );
    add_settings_section( 'cps_settings_section', 'Settings', null, 'product-shortlinks-settings' );
    add_settings_field(
        'cps_custom_domain',
        'Custom Domain',
        'cps_custom_domain_field',
        'product-shortlinks-settings',
        'cps_settings_section'
    );
}

function cps_custom_domain_field() {
    $value = get_option( 'cps_custom_domain', '' );
    echo "<input type='text' name='cps_custom_domain' value='{$value}' placeholder='https://example.com' />";
}