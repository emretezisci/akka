<?php

class Akka_Pro_Public {
    public function __construct() {
        // Initialization code here
    }

    public function enqueue_styles() {
        wp_enqueue_style('akka-pro-styles', AKKA_PRO_PLUGIN_URL . 'assets/css/styles.css', array(), AKKA_PRO_VERSION);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('akka-pro-scripts', AKKA_PRO_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), AKKA_PRO_VERSION, true);

        // Assuming you have a page with the slug 'results' or you can change it accordingly
        $results_page_url = home_url('/booking/results');

        // Localize script for AJAX (use consistent nonce name)
        wp_localize_script('akka-pro-scripts', 'akka_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('akka_pro_nonce'), 
            'results_page' => $results_page_url,
        ));
    }

    public function display_results() {
        ob_start();
        include AKKA_PRO_PLUGIN_DIR . 'templates/results-template.php';
        return ob_get_clean();
    }
}
