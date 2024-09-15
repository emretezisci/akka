<?php
class Akka_Pro_Results
{

    // Define the results page URL here
    private $results_page_url = 'test4';
    public function __construct()
    {
        // Get the results page ID from settings
        $this->results_page_url = get_option('akka_pro_results_page');

        add_shortcode('akka_pro_results', array($this, 'display_results'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_get_hotel_name', array($this, 'get_hotel_name'));
        add_action('wp_ajax_nopriv_get_hotel_name', array($this, 'get_hotel_name'));
        add_action('wp_ajax_get_post_details', array($this, 'get_post_details'));
        add_action('wp_ajax_create_woocommerce_product', array($this, 'create_woocommerce_product'));
        add_action('wp_ajax_nopriv_create_woocommerce_product', array($this, 'create_woocommerce_product'));
        add_action('wp_ajax_get_last_product_id', array($this, 'get_last_product_id'));
        add_action('wp_ajax_nopriv_get_last_product_id', array($this, 'get_last_product_id'));
    }

    public function display_results()
    {
        ob_start();
        include AKKA_PRO_PLUGIN_DIR . 'templates/results-template.php';
        return ob_get_clean();
    }

    public function enqueue_styles()
    {
        // Check if we are on the results page
        if (is_page($this->results_page_url)) {
            wp_enqueue_style('akka-pro-results-styles', AKKA_PRO_PLUGIN_URL . 'assets/css/results-styles.css', array(), AKKA_PRO_VERSION, 'all');
        }
    }

    public function enqueue_scripts()
    {
        // Check if we are on the results page
        if (is_page($this->results_page_url)) {
            wp_enqueue_script('akka-pro-results', AKKA_PRO_PLUGIN_URL . 'assets/js/results.js', array('jquery'), AKKA_PRO_VERSION, true);
            wp_localize_script('akka-pro-results', 'akka_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akka_pro_nonce')
            ));

            wp_localize_script('akka-pro-results', 'wc_add_to_cart_params', array(
                'ajax_url' => WC_AJAX::get_endpoint("%%endpoint%%"),
                'cart_url' => wc_get_cart_url(),
            ));
        }
    }

    public function get_post_details()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
        $search_key = isset($_POST['search_key']) ? sanitize_text_field($_POST['search_key']) : '';
        $search_value = isset($_POST['search_value']) ? sanitize_text_field($_POST['search_value']) : '';
        $requested_fields = isset($_POST['requested_fields']) ? $_POST['requested_fields'] : [];
        $batch = isset($_POST['batch']) ? filter_var($_POST['batch'], FILTER_VALIDATE_BOOLEAN) : false;
        $batch_ids = isset($_POST['batch_ids']) ? $_POST['batch_ids'] : [];

        error_log("get_post_details called with: " . print_r($_POST, true));

        if (!$post_type || empty($requested_fields)) {
            wp_send_json_error('Invalid request parameters.');
        }

        $args = [
            'post_type' => $post_type,
            'posts_per_page' => -1,
        ];

        if ($search_key && ($search_value || $batch)) {
            $args['meta_query'] = [
                'relation' => 'OR'
            ];

            if (!empty($search_value)) {
                $args['meta_query'][] = [
                    'key' => $search_key,
                    'value' => $search_value,
                    'compare' => '='
                ];
            }

            if ($batch && !empty($batch_ids)) {
                $args['meta_query'][] = [
                    'key' => $search_key,
                    'value' => $batch_ids,
                    'compare' => 'IN'
                ];
            }
        }

        error_log("WP_Query args: " . print_r($args, true));

        $query = new WP_Query($args);
        error_log("WP_Query SQL: " . $query->request);

        if (!$query->have_posts()) {
            wp_send_json_error('Post not found.');
        }

        $post_details = [];

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $details = [];
            foreach ($requested_fields as $field) {
                switch ($field) {
                    case 'title':
                        $details['title'] = get_the_title($post_id);
                        break;
                    case 'content':
                        $details['content'] = get_the_content(null, false, $post_id);
                        break;
                    case 'featured_image':
                        $details['featured_image'] = get_the_post_thumbnail_url($post_id, 'full');
                        break;
                    default:
                        $details[$field] = get_field($field, $post_id);
                        break;
                }
            }
            $post_details[$post_id] = $details;
        }

        wp_reset_postdata();

        error_log("Post details found: " . print_r($post_details, true));

        wp_send_json_success($post_details);
    }

    public function create_woocommerce_product()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $product_name = sanitize_text_field($_POST['product_name']);
        $product_price = floatval($_POST['product_price']);
        $product_description = sanitize_textarea_field($_POST['product_description']);

        $product = new WC_Product();
        $product->set_name($product_name);
        $product->set_price($product_price);
        $product->set_regular_price($product_price); // Set regular price as well
        $product->set_description($product_description);
        $product->set_short_description($product_description); // Optionally set short description
        $product->set_status('publish');

        $product_id = $product->save();

        if ($product_id) {
            // Add product to cart
            WC()->cart->add_to_cart($product_id);

            wp_send_json_success(['product_id' => $product_id]);
        } else {
            wp_send_json_error('Error creating product');
        }

    }

    public function get_last_product_id()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        global $wpdb;
        $last_product_id = $wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->posts} WHERE post_type = 'product'");

        wp_send_json_success($last_product_id);
    }

}

new Akka_Pro_Results();
