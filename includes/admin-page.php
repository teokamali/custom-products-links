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
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1; // Current page
    $posts_per_page = 10; // Number of products per page

    ?>
    <div class="wrap">
        <h1>Product Shortlinks</h1>
        <!-- Search Form -->
        <form method="get" action="">
            <input type="hidden" name="page" value="product-shortlinks" />
            <input type="text" name="search_name" value="<?php echo esc_attr($_GET['search_name'] ?? ''); ?>" placeholder="Search by name" />
            <input type="text" name="search_sku" value="<?php echo esc_attr($_GET['search_sku'] ?? ''); ?>" placeholder="Search by SKU" />
            <button type="submit" class="button">Search</button>
        </form>

        <!-- Products Table -->
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>URL</th>
                    <th>Short Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search_name = $_GET['search_name'] ?? '';
                $search_sku = $_GET['search_sku'] ?? '';

                $products_query = cps_get_products($search_name, $search_sku, $paged, $posts_per_page);

                if ($products_query->have_posts()) {
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        $product_id = get_the_ID();
                        $sku = get_post_meta($product_id, '_sku', true);
                        $image_url = get_the_post_thumbnail_url($product_id, 'thumbnail') ?: 'https://via.placeholder.com/64';
                        $product_url = get_permalink($product_id);
                        $short_link = cps_generate_shortlink($product_id, get_option('cps_custom_domain', home_url()));

                        echo "<tr>
                                <td>{$sku}</td>
                                <td><img src='{$image_url}' width='64' height='64'></td>
                                <td>" . get_the_title() . "</td>
                                <td><a href='{$product_url}' target='_blank'>{$product_url}</a></td>
                                <td><input type='text' readonly value='{$short_link}'></td>
                                <td><button class='button'>Copy</button></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No products found.</td></tr>";
                }

                wp_reset_postdata();
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $total_pages = $products_query->max_num_pages;
                $base_url = add_query_arg(['paged' => '%#%'], menu_page_url('product-shortlinks', false));
                echo paginate_links([
                    'base'      => $base_url,
                    'format'    => '&paged=%#%',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => __('« Prev'),
                    'next_text' => __('Next »'),
                ]);
                ?>
            </div>
        </div>
    </div>
    <?php
}
// Modify the `cps_get_products` function to include search functionality for both name and SKU
function cps_get_products($search_name = '', $search_sku = '', $paged = 1, $posts_per_page = 10) {
    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'paged' => $paged, // Add current page
        'posts_per_page' => $posts_per_page, // Add items per page
    ];

    // Add search by name if provided
    if ($search_name) {
        $args['s'] = $search_name;
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

    return new WP_Query($args);
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
