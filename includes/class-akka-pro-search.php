<?php

class Akka_Pro_Search {
    public function __construct() {
        // Get the home page ID from settings
        $this->home_page_id = get_option('akka_pro_home_page');

        add_shortcode('akka_pro_search_form', array($this, 'display_search_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_akka_process_booking', array($this, 'process_booking_form'));
        add_action('wp_ajax_nopriv_akka_process_booking', array($this, 'process_booking_form'));
    }

    public function display_search_form() {
        ob_start();
        $hotels = $this->get_hotels();
        include AKKA_PRO_PLUGIN_DIR . 'templates/search-form-template.php';
        return ob_get_clean();
    }

    private function get_hotels() {
        $args = array(
            'post_type' => 'hotel',
            'posts_per_page' => -1,
        );
        return get_posts($args);
    }

    public function enqueue_scripts() {
        // Check if we are on the home page
        if (is_page($this->home_page_id)) {
            // Enqueue Flatpickr CSS
            wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), null);

            // Enqueue custom styles
            wp_enqueue_style('akka-pro-styles', AKKA_PRO_PLUGIN_URL . 'assets/css/search-form-styles.css', array(), AKKA_PRO_VERSION);

            // Enqueue jQuery (if not already included by WordPress)
            wp_enqueue_script('jquery');

            // Enqueue Flatpickr JS
            wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);

            // Enqueue custom scripts
            wp_enqueue_script('akka-pro-search-scripts', AKKA_PRO_PLUGIN_URL . 'assets/js/search.js', array('jquery', 'flatpickr'), AKKA_PRO_VERSION, true);
        }
    }

    public function process_booking_form() {
        check_ajax_referer('akka_pro_nonce', 'nonce'); 

        $room_request = isset($_POST['room_request']) ? json_decode(stripslashes($_POST['room_request']), true) : array();

        if (empty($room_request)) {
            wp_send_json_error('Invalid request body.');
        }

        $api_endpoint = defined('API_ENDPOINT') ? API_ENDPOINT : '';
        $api_token = defined('API_TOKEN') ? API_TOKEN : '';

        $response = wp_remote_post($api_endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_token
            ),
            'body' => json_encode($room_request)
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Error making API request: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        wp_send_json_success($data);
        wp_die();
    }
}
