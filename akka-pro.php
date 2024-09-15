<?php
/**
 * Plugin Name: Akka Pro
 * Description: A plugin to manage hotel reservations and bonus features.
 * Version: 1.0.4
 * Author: Your Name
 * Text Domain: akka-pro
 */

 
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('AKKA_PRO_VERSION', '1.0.4');
define('AKKA_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AKKA_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-admin.php';
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-public.php';
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-search.php';
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-results.php';
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-claim.php';
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-profile.php';
require_once AKKA_PRO_PLUGIN_DIR . 'includes/class-akka-pro-notification.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'akka_pro_activate');
register_deactivation_hook(__FILE__, 'akka_pro_deactivate');

function akka_pro_activate() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Create claim submissions table if it doesn't exist
    $claims_table = $wpdb->prefix . 'akka_pro_claims';
    $claims_sql = "CREATE TABLE IF NOT EXISTS $claims_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        creation_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        expiry_date date NOT NULL,
        adult_count int NOT NULL,
        check_in_date date NOT NULL,
        check_out_date date NOT NULL,
        children_count int NOT NULL,
        child_ages text,
        company_id varchar(255) NOT NULL,
        ip_address varchar(50) NOT NULL,
        room_id varchar(255) NOT NULL,
        total_price decimal(10,2) NOT NULL,
        bonus_rate decimal(5,2) NOT NULL,
        bonus_duration int NOT NULL,
        calculated_bonus decimal(10,2) NOT NULL,
        approval_status varchar(20) DEFAULT '',
        reason text,
        created_by bigint(20) unsigned,
        PRIMARY KEY  (id),
        KEY created_by (created_by)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($claims_sql);

    // Check and add missing columns
    $existing_columns = $wpdb->get_col("DESC $claims_table", 0);

    $columns_to_add = array(
        'voucher_no' => "ALTER TABLE $claims_table ADD voucher_no varchar(255) DEFAULT NULL",
        'operator_id' => "ALTER TABLE $claims_table ADD operator_id int(11) DEFAULT NULL",
        'guest_name' => "ALTER TABLE $claims_table ADD guest_name varchar(255) DEFAULT NULL",
        'guest_surname' => "ALTER TABLE $claims_table ADD guest_surname varchar(255) DEFAULT NULL",
        'notes' => "ALTER TABLE $claims_table ADD notes text DEFAULT NULL"
    );

    foreach ($columns_to_add as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $wpdb->query($sql);
        }
    }
}

function akka_pro_deactivate() {
    // Deactivation code here
}

// Initialize the plugin
function akka_pro_init() {
    $plugin_admin = new Akka_Pro_Admin();
    $plugin_public = new Akka_Pro_Public();
    $plugin_search = new Akka_Pro_Search();
    $plugin_results = new Akka_Pro_Results();
    $plugin_profile = new Akka_Pro_Profile();
    $claim_form = new Akka_Pro_Claim();

    // Admin menu
    add_action('admin_menu', array($plugin_admin, 'add_plugin_admin_menu'));

    // Enqueue scripts and styles for public-facing pages
    add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
    add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));

    // Shortcode for the search form
    add_shortcode('akka_pro_search_form', array($plugin_search, 'display_search_form'));
}

add_action('plugins_loaded', 'akka_pro_init');
