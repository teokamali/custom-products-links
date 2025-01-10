<?php

// Add a new menu item in the admin panel
add_action('admin_menu', 'cps_add_admin_menu');
function cps_add_admin_menu() {
    add_menu_page(
        'Product Shortlinks',
        'Product Shortlinks',
        'manage_woocommerce',
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
   
<div style="display:flex; align-items:center; justify-content:space-between">

    <form method="post" action="options.php">
        <?php
            settings_fields('cps_settings_group');
            do_settings_sections('product-shortlinks-settings');
            submit_button('Save Custom Domain');
            ?>
        </form>
        
        <div>
            <h2>API Doc</h2>
            <ul>
                <li>The API is only available if the option is enabled in the admin settings.</li>
                <li>GET single link: /wp-json/cps/v1/shortlinks/{short_code}</li>
                <li>GET all link: /wp-json/cps/v1/shortlinks</li>
                <li>Example API: https://example.com/wp-json/cps/v1/shortlinks/SHORT_CODE</li>
                <li>Example API: https://example.com/wp-json/cps/v1/shortlinks</li>
            </ul>
        </div>
    </div>

        <hr>
     
        <!-- Search Form with separate fields for name and SKU -->
        <!-- Display Products Table -->
        <h2>Products</h2>
        <form method="get" action="" style="max-width:50%; display:flex; align-items:center; gap:16px">
            <input type="hidden" name="page" value="product-shortlinks" />
            <div>
                <label for="search_name">جستوجو نام محصول:</label>
                <input type="text" name="search_name" id="search_name" value="<?php echo isset($_GET['search_name']) ? esc_attr($_GET['search_name']) : ''; ?>" placeholder="جستوجو نام محصول" />
            </div>
            <div>

                <label for="search_sku">جستوجو با SKU:</label>
                <input type="text" name="search_sku" id="search_sku" value="<?php echo isset($_GET['search_sku']) ? esc_attr($_GET['search_sku']) : ''; ?>" placeholder="جستوجو با SKU" />
            </div>
            
            <button type="submit" class="button" >جستوجو</button>
        </form>
        <table class="widefat fixed" cellspacing="0" style="margin-top:16px;">
            <thead>
                <tr>
                    <th>Product SKU</th> <!-- New Column for SKU -->
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Product URL</th>
                    <th>Short Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get the search queries for name and SKU
                $search_name = isset($_GET['search_name']) ? sanitize_text_field($_GET['search_name']) : '';
                $search_sku = isset($_GET['search_sku']) ? sanitize_text_field($_GET['search_sku']) : '';

                $products = cps_get_products($search_name, $search_sku); // Pass the search queries to the function
                
                $custom_domain = get_option('cps_custom_domain', home_url());
                foreach ($products as $product) {
                    // Fetch the SKU and other product details
                    $sku = get_post_meta($product->ID, '_sku', true); // Get SKU
                    $short_link = cps_generate_shortlink($product->ID, $custom_domain);
                    $image_url = get_the_post_thumbnail_url($product->ID, 'thumbnail') ?: 'https://via.placeholder.com/64';
                    $product_url = get_permalink($product->ID);

                    echo "<tr>
                            <td>{$sku}</td> <!-- Display SKU -->
                            <td><img src='{$image_url}' width='64' height='64' style='border-radius: 12px;'></td>
                            <td>{$product->post_title}</td>
                            <td><a href='{$product_url}' target='_blank'>{$product_url}</a></td>
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

// Modify the `cps_get_products` function to include search functionality for both name and SKU
function cps_get_products($search_name = '', $search_sku = '') {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];

    // Add search by name if provided
    if ($search_name) {
        $args['s'] = $search_name; // Search by product name
    }

    // Add search by SKU if provided
    if ($search_sku) {
        $args['meta_query'] = [
            [
                'key' => '_sku', 
                'value' => $search_sku, 
                'compare' => 'LIKE',
            ],
        ];
    }

    return get_posts($args);
}

// Register settings
add_action('admin_init', 'cps_register_settings');
function cps_register_settings() {
    register_setting('cps_settings_group', 'cps_custom_domain');
    register_setting('cps_settings_group', 'cps_enable_api');

    add_settings_section('cps_settings_section', 'Settings', null, 'product-shortlinks-settings');
    add_settings_field('cps_custom_domain', 'Custom Domain', 'cps_custom_domain_field', 'product-shortlinks-settings', 'cps_settings_section');

    add_settings_section('cps_api_settings_section', 'REST API Settings', null, 'product-shortlinks-settings');
    add_settings_field('cps_enable_api', 'Enable REST API', 'cps_enable_api_field', 'product-shortlinks-settings', 'cps_api_settings_section');
}

function cps_custom_domain_field() {
    $value = get_option('cps_custom_domain', '');
    echo "<input type='text' name='cps_custom_domain' value='{$value}' placeholder='https://example.com' />";
}

function cps_enable_api_field() {
    $value = get_option('cps_enable_api', '1');
    echo "<input type='checkbox' name='cps_enable_api' value='1' " . checked(1, $value, false) . " /> Enable REST API";
}
