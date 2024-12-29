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
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Product URL</th> <!-- Show product URL -->
                    <th>Short Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $products = cps_get_products();
                $custom_domain = get_option( 'cps_custom_domain', home_url() );
                foreach ( $products as $product ) {
                    // Fetch the short link and product image
                    $short_link = cps_generate_shortlink( $product->ID, $custom_domain );
                    $image_url = get_the_post_thumbnail_url( $product->ID, 'thumbnail' ); // Fetch product image
                    $image_url = $image_url ?: 'https://via.placeholder.com/64'; // Placeholder if no image
                    $product_url = get_permalink( $product->ID ); // Get the full product URL

                    echo "<tr>
                            <td><img src='{$image_url}' width='64' height='64' style='border-radius: 12px;'></td>
                            <td>{$product->post_title}</td>
                            <td><a href='{$product_url}' target='_blank'>{$product_url}</a></td> <!-- Display product URL -->
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