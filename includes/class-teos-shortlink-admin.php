<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class Teos_Shortlink_Admin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_teos_fetch_products', [$this, 'ajax_fetch_products']);
        // Register AJAX handler

    }


    public function add_menu_pages()
    {
        add_menu_page(
            __('Teo\'s Shortlinks', 'teos-shortlink-plugin'),
            __('Teo\'s Shortlinks', 'teos-shortlink-plugin'),
            'manage_woocommerce',
            'teos-shortlinks',
            [$this, 'render_main_page'],
            'dashicons-admin-links',
            56
        );

        add_submenu_page(
            'teos-shortlinks',
            __('Settings', 'teos-shortlink-plugin'),
            __('Settings', 'teos-shortlink-plugin'),
            'manage_options',
            'teos-shortlinks-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_scripts($hook)
    {
        if ('toplevel_page_teos-shortlinks' !== $hook) {
            return;
        }

        // Enqueue Select2 CSS and JS
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        // Enqueue custom JS for dropdown functionality
        wp_enqueue_script('teos-admin-js', TEOS_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'select2-js'], TEOS_PLUGIN_VERSION, true);

        // Localize script to pass AJAX URL and nonce
        wp_localize_script('teos-admin-js', 'teosAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teos_refresh_nonce'),
        ]);

    }
    public function ajax_fetch_products()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'teos-shortlink-plugin'));
        }

        $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        // Fetch products based on the search term
        $args = [
            'limit' => 20,
            's' => $search,
            'status' => 'publish',
        ];
        $products = wc_get_products($args);

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->get_id(),
                'text' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
            ];
        }

        wp_send_json($results);
    }

    public function render_main_page()
    {
        global $wpdb;

        // Fetch existing shortlinks
        $table_name = $wpdb->prefix . 'teo_shortlinks';

        // Fetch WooCommerce products
        if (!class_exists('WooCommerce')) {
            echo '<div class="error"><p>' . __('WooCommerce is not active.', 'teos-shortlink-plugin') . '</p></div>';
            return;
        }

        // Get custom domain setting
        $custom_domain = get_option('teo_custom_domain', '');

        // Handle search and pagination
        $search_sku = isset($_GET['search_sku']) ? sanitize_text_field($_GET['search_sku']) : '';
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $posts_per_page = 10;
        $offset = ($paged - 1) * $posts_per_page;

        // Query products
        $args = [
            'limit' => $posts_per_page,
            'offset' => $offset,
            'status' => 'publish',
        ];

        if ($search_sku) {
            $args['sku'] = $search_sku;
        }

        $products = wc_get_products($args);

        // Custom query to count total products
        $total_products_query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $total_products = $total_products_query->found_posts;

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Shortlink Management', 'teos-shortlink-plugin'); ?></h1>

            <!-- Search Form -->
            <form method="get" action="">
                <input type="hidden" name="page" value="teos-shortlinks">
                <input type="text" name="search_sku" value="<?php echo esc_attr($search_sku); ?>"
                    placeholder="<?php esc_attr_e('Search by SKU', 'teos-shortlink-plugin'); ?>">
                <button type="submit" class="button"><?php esc_html_e('Search', 'teos-shortlink-plugin'); ?></button>
                <button type="button" id="refresh-shortlinks"
                    class="button"><?php esc_html_e('Refresh', 'teos-shortlink-plugin'); ?></button>
            </form>

            <!-- Shortlinks Table -->
            <h2><?php esc_html_e('Generated Shortlinks', 'teos-shortlink-plugin'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('SKU', 'teos-shortlink-plugin'); ?></th>
                        <th><?php esc_html_e('Product Image', 'teos-shortlink-plugin'); ?></th>
                        <th><?php esc_html_e('Product Name', 'teos-shortlink-plugin'); ?></th>
                        <th><?php esc_html_e('Shortlink', 'teos-shortlink-plugin'); ?></th>
                        <th><?php esc_html_e('Actions', 'teos-shortlink-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo esc_html($product->get_sku()); ?></td>

                            <td>
                                <img src="<?php echo esc_url($product->get_image_id() ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : wc_placeholder_img_src()); ?>"
                                    alt="<?php echo esc_attr($product->get_name()); ?>" width="50" height="50">
                            </td>
                            <td><?php echo esc_html($product->get_name()); ?></td>
                            <td>
                                <?php
                                $shortlink = $wpdb->get_var($wpdb->prepare("SELECT shortlink FROM $table_name WHERE product_name = %s", $product->get_name()));
                                if ($shortlink) {
                                    $shortlink_url = $custom_domain ? trailingslashit($custom_domain) . $shortlink : site_url('/' . $shortlink);
                                    ?>
                                    <a href="<?php echo esc_url($shortlink_url); ?>" target="_blank">
                                        <?php echo esc_html($shortlink_url); ?>
                                    </a>
                                    <?php
                                } else {
                                    echo esc_html__('No shortlink found', 'teos-shortlink-plugin');
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($shortlink): ?>
                                    <button class="button copy-shortlink" data-shortlink="<?php echo esc_url($shortlink_url); ?>">
                                        <?php esc_html_e('Copy Link', 'teos-shortlink-plugin'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php
            $total_pages = ceil($total_products / $posts_per_page);
            if ($total_pages > 1) {
                $current_page = max(1, $paged);
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                ]);
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    public function render_settings_page()
    {
        // Save settings if form is submitted
        if (isset($_POST['teo_settings_nonce']) && wp_verify_nonce($_POST['teo_settings_nonce'], 'save_teo_settings')) {
            update_option('teo_custom_domain', sanitize_text_field($_POST['custom_domain']));
            update_option('teo_enable_rest_api', isset($_POST['enable_rest_api']) ? 1 : 0);
            echo '<div class="updated"><p>' . __('Settings saved.', 'teos-shortlink-plugin') . '</p></div>';
        }

        // Get current settings
        $custom_domain = get_option('teo_custom_domain', '');
        $enable_rest_api = get_option('teo_enable_rest_api', 1);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Teo\'s Shortlink Settings', 'teos-shortlink-plugin'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('save_teo_settings', 'teo_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="custom_domain"><?php esc_html_e('Custom Domain', 'teos-shortlink-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="custom_domain" id="custom_domain"
                                value="<?php echo esc_attr($custom_domain); ?>" placeholder="https://short.ly">
                            <p class="description">
                                <?php esc_html_e('Enter a custom domain for shortlinks.', 'teos-shortlink-plugin'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Enable REST API', 'teos-shortlink-plugin'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_rest_api" id="enable_rest_api" value="1" <?php checked($enable_rest_api, 1); ?>>
                            <label
                                for="enable_rest_api"><?php esc_html_e('Enable public REST API', 'teos-shortlink-plugin'); ?></label>
                            <p class="description">
                                <?php esc_html_e('When enabled, the following API endpoints will be available:', 'teos-shortlink-plugin'); ?>
                            </p>
                            <ul style="margin-top: 5px; padding-left: 20px;">
                                <li>
                                    <strong>GET /wp-json/teo-shortlinks/v1/links</strong><br>
                                    <?php esc_html_e('Fetch all generated shortlinks.', 'teos-shortlink-plugin'); ?>
                                </li>
                                <li>
                                    <strong>GET /wp-json/teo-shortlinks/v1/link/{shortcode}</strong><br>
                                    <?php esc_html_e('Fetch details of a specific shortlink by its shortcode.', 'teos-shortlink-plugin'); ?>
                                </li>
                            </ul>
                            <p class="description">
                                <?php esc_html_e('Example: To fetch all shortlinks, send a GET request to ', 'teos-shortlink-plugin'); ?>
                                <code><?php echo esc_url(site_url('/wp-json/teo-shortlinks/v1/links')); ?></code>
                            </p>
                        </td>
                    </tr>
                </table>
                <p><button type="submit"
                        class="button button-primary"><?php esc_html_e('Save Changes', 'teos-shortlink-plugin'); ?></button></p>
            </form>
        </div>
        <?php
    }
}

new Teos_Shortlink_Admin();