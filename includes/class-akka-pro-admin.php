<?php
class Akka_Pro_Admin
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_get_rooms_by_hotel', array($this, 'get_rooms_by_hotel'));
        add_action('wp_ajax_nopriv_get_rooms_by_hotel', array($this, 'get_rooms_by_hotel'));
        add_action('wp_ajax_save_default_bonus_rate', array($this, 'save_default_bonus_rate'));
        add_action('wp_ajax_save_bonus_settings', array($this, 'save_bonus_settings'));
        add_action('wp_ajax_delete_bonus_setting', array($this, 'delete_bonus_setting'));
        add_action('wp_ajax_save_block_settings', array($this, 'save_block_settings'));
        add_action('wp_ajax_delete_block_setting', array($this, 'delete_block_setting'));
        add_action('wp_ajax_delete_claim', array($this, 'delete_claim'));
        add_action('wp_ajax_approve_claim', array($this, 'approve_claim'));
        add_action('wp_ajax_deny_claim', array($this, 'deny_claim'));
        add_action('wp_ajax_save_market_bonus_settings', array($this, 'save_market_bonus_settings'));
        add_action('wp_ajax_delete_market_bonus_setting', array($this, 'delete_market_bonus_setting'));
        add_action('wp_ajax_save_discount_settings', array($this, 'save_discount_settings'));
        add_action('wp_ajax_delete_discount_setting', array($this, 'delete_discount_setting'));
        add_action('wp_ajax_get_block_settings', array($this, 'get_block_settings'));
        add_action('wp_ajax_nopriv_get_block_settings', array($this, 'get_block_settings')); 
    }

    public function enqueue_admin_scripts($hook_suffix)
    {
        // Check if we are on the correct admin page
        if ($hook_suffix == 'toplevel_page_akka-pro' || $hook_suffix == 'akka-pro_page_akka-pro-settings') {
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);
            wp_enqueue_script('akka-pro-admin-js', AKKA_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), null, true);
            wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
            wp_enqueue_style('akka-pro-admin-tabs', AKKA_PRO_PLUGIN_URL . 'assets/css/admin.css');

            // Include DataTables CSS and JS
            wp_enqueue_style('datatables-css', '//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
            wp_enqueue_script('datatables-js', '//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', array('jquery'), null, true);

            wp_localize_script('akka-pro-admin-js', 'akkaPro', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akka_pro_nonce'),
                'selectRoomText' => __('Select Room', 'akka-pro'),
                'noRoomsText' => __('No rooms available', 'akka-pro'),
                'errorLoadingRoomsText' => __('Error loading rooms', 'akka-pro'),
                'selectHotelFirstText' => __('Select Hotel First', 'akka-pro'),
            ));
        }
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            __('Akka Pro', 'akka-pro'),
            __('Akka Pro', 'akka-pro'),
            'manage_options',
            'akka-pro',
            array($this, 'akka_pro_overview_page'),
            '',
            1
        );

        add_submenu_page(
            'akka-pro',
            __('Overview', 'akka-pro'),
            __('Overview', 'akka-pro'),
            'manage_options',
            'akka-pro',
            array($this, 'akka_pro_overview_page')
        );

        add_submenu_page(
            'akka-pro',
            __('Settings', 'akka-pro'),
            __('Settings', 'akka-pro'),
            'manage_options',
            'akka-pro-settings',
            array($this, 'akka_pro_settings_page')
        );
    }

    public function register_settings()
    {
        add_settings_section(
            'akka_pro_page_selection_section',
            'Page Selection Settings',
            null,
            'akka-pro-settings'
        );

        add_settings_field(
            'akka_pro_claim_page',
            'Claim Page',
            array($this, 'akka_pro_claim_page_callback'),
            'akka-pro-settings',
            'akka_pro_page_selection_section'
        );

        add_settings_field(
            'akka_pro_profile_page',
            'Profile Page',
            array($this, 'akka_pro_profile_page_callback'),
            'akka-pro-settings',
            'akka_pro_page_selection_section'
        );

        add_settings_field(
            'akka_pro_home_page',
            'Home Page',
            array($this, 'akka_pro_home_page_callback'),
            'akka-pro-settings',
            'akka_pro_page_selection_section'
        );

        add_settings_field(
            'akka_pro_results_page',
            'Results Page',
            array($this, 'akka_pro_results_page_callback'),
            'akka-pro-settings',
            'akka_pro_page_selection_section'
        );

        register_setting('akka_pro_settings_group', 'akka_pro_claim_page');
        register_setting('akka_pro_settings_group', 'akka_pro_profile_page');
        register_setting('akka_pro_settings_group', 'akka_pro_home_page');
        register_setting('akka_pro_settings_group', 'akka_pro_results_page');
    }


    public function akka_pro_claim_page_callback()
    {
        $selected = get_option('akka_pro_claim_page');
        wp_dropdown_pages(array(
            'name' => 'akka_pro_claim_page',
            'selected' => $selected,
        ));
    }

    public function akka_pro_profile_page_callback()
    {
        $selected = get_option('akka_pro_profile_page');
        wp_dropdown_pages(array(
            'name' => 'akka_pro_profile_page',
            'selected' => $selected,
        ));
    }

    public function akka_pro_home_page_callback()
    {
        $selected = get_option('akka_pro_home_page');
        wp_dropdown_pages(array(
            'name' => 'akka_pro_home_page',
            'selected' => $selected,
        ));
    }

    public function akka_pro_results_page_callback()
    {
        $selected = get_option('akka_pro_results_page');
        wp_dropdown_pages(array(
            'name' => 'akka_pro_results_page',
            'selected' => $selected,
        ));
    }


    public function akka_pro_overview_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Akka Pro - Overview', 'akka-pro'); ?></h1>
            <p><?php _e('Welcome to the Akka Pro Overview page!', 'akka-pro'); ?></p>
        </div>
        <?php
    }

    public function get_rooms_by_hotel()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $acf_hotel_id = sanitize_text_field($_POST['acf_hotel_id']);
        if (!$acf_hotel_id) {
            wp_send_json_error(array('message' => 'Hotel ID is required.'));
            return;
        }

        // Find the hotel post by ACF hotel ID
        $hotels = get_posts(array(
            'post_type' => 'hotel',
            'meta_key' => 'acf_hotel_id',
            'meta_value' => $acf_hotel_id,
            'posts_per_page' => 1
        ));

        if (empty($hotels)) {
            wp_send_json_error(array('message' => 'Hotel not found.'));
            return;
        }

        $hotel_post = $hotels[0];

        // Fetch rooms based on the ACF relationship
        $associated_rooms = get_field('acf_associated_rooms', $hotel_post->ID);

        if ($associated_rooms && is_array($associated_rooms)) {
            $rooms_data = array();
            foreach ($associated_rooms as $room) {
                $room_acf_id = get_field('acf_room_id', $room->ID);
                $rooms_data[] = array(
                    'id' => $room_acf_id,
                    'name' => $room->post_title
                );
            }
            wp_send_json_success(array('rooms' => $rooms_data));
        } else {
            wp_send_json_error(array('message' => 'No rooms found for this hotel.'));
        }
    }

    public function save_default_bonus_rate()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $default_bonus_rate = sanitize_text_field($_POST['default_bonus_rate']);

        // Log the received value for debugging
        error_log('Received default bonus rate: ' . $default_bonus_rate);

        // Validate that the bonus rate is a number between 0 and 100
        if (is_numeric($default_bonus_rate) && $default_bonus_rate >= 0 && $default_bonus_rate <= 100) {
            update_option('akka_default_bonus_rate', $default_bonus_rate);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Invalid bonus rate value.'));
        }
    }

    public function save_bonus_settings()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $hotel_id = sanitize_text_field($_POST['akka_bonus_hotel']);
        $acf_room_id = sanitize_text_field($_POST['akka_bonus_room']);
        $bonus_rate = sanitize_text_field($_POST['akka_bonus_rate']);
        $duration = sanitize_text_field($_POST['akka_bonus_duration']);
        $bonus_date_range = sanitize_text_field($_POST['akka_bonus_date_range']); // The new date range field

        // Split the date range into start and end date
        $dates = explode(" to ", $bonus_date_range); // Flatpickr uses 'to' by default as the separator
        if (count($dates) == 2) {
            $bonus_start_date = $dates[0];
            $bonus_end_date = $dates[1];
        } else {
            $bonus_start_date = '';
            $bonus_end_date = '';
        }

        // Fetch the room post title based on the room ID
        $room_post = get_posts(array(
            'post_type' => 'room',
            'meta_key' => 'acf_room_id',
            'meta_value' => $acf_room_id,
            'posts_per_page' => 1
        ));

        $room_title = !empty($room_post) ? $room_post[0]->post_title : $acf_room_id;

        // Retrieve existing bonus settings
        $bonus_settings = get_option('akka_bonus_settings', array());
        $id = uniqid(); // Create a unique ID for this setting

        // Add the new bonus settings including the date range
        $bonus_settings[$id] = array(
            'hotel_id' => $hotel_id,
            'acf_room_id' => $acf_room_id,
            'room_title' => $room_title,
            'bonus_rate' => $bonus_rate,
            'duration' => $duration,
            'bonus_start_date' => $bonus_start_date,
            'bonus_end_date' => $bonus_end_date
        );

        // Update the bonus settings in the database
        update_option('akka_bonus_settings', $bonus_settings);

        // Generate the HTML for the new row, including the start and end dates
        $row_html = '<tr id="bonus-setting-row-' . esc_attr($id) . '">';
        $row_html .= '<td>' . esc_html(get_the_title($hotel_id)) . '</td>';
        $row_html .= '<td>' . esc_html($acf_room_id) . '</td>';
        $row_html .= '<td>' . esc_html($room_title) . '</td>';
        $row_html .= '<td>' . esc_html($bonus_rate) . '</td>';
        $row_html .= '<td>' . esc_html($duration) . '</td>';
        $row_html .= '<td>' . esc_html($bonus_start_date) . '</td>';
        $row_html .= '<td>' . esc_html($bonus_end_date) . '</td>';
        $row_html .= '<td><button class="delete-bonus-setting" data-id="' . esc_attr($id) . '">' . __('Delete', 'akka-pro') . '</button></td>';
        $row_html .= '</tr>';

        // Send a successful response with the new row's HTML
        wp_send_json_success(array('row_html' => $row_html));
    }

    public function delete_bonus_setting()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $id = sanitize_text_field($_POST['id']);

        $bonus_settings = get_option('akka_bonus_settings', array());

        if (isset($bonus_settings[$id])) {
            unset($bonus_settings[$id]);
            update_option('akka_bonus_settings', $bonus_settings);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Setting not found.'));
        }
    }

    public function save_block_settings()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        if (empty($_POST['hotel_id']) || empty($_POST['room_id']) || empty($_POST['date_range'])) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }

        $hotel_id = sanitize_text_field($_POST['hotel_id']);
        $acf_room_id = sanitize_text_field($_POST['room_id']);
        $date_range = sanitize_text_field($_POST['date_range']);

        // Split the date range into check-in and check-out dates
        $dates = explode(' to ', $date_range);

        // If only one date is selected, it is used for both check-in and check-out
        if (count($dates) === 1) {
            $check_in_date = $dates[0];
            $check_out_date = $dates[0];
        } else {
            $check_in_date = $dates[0];
            $check_out_date = $dates[1];
        }

        // Find the post ID of the room based on the custom field 'acf_room_id'
        $args = array(
            'post_type' => 'room',  // Assuming 'room' is the custom post type for rooms
            'meta_key' => 'acf_room_id',
            'meta_value' => $acf_room_id,
            'posts_per_page' => 1,
        );
        $room_query = new WP_Query($args);

        if ($room_query->have_posts()) {
            $room_query->the_post();
            $room_id = get_the_ID();
            $room_title = get_the_title();
            wp_reset_postdata();
        } else {
            $room_title = $acf_room_id;  // Fallback
        }

        $block_settings = get_option('akka_block_settings', array()); // Get existing settings
        $id = uniqid(); // Generate a unique ID (optional, but helpful)
        $block_settings[$id] = array(
            'hotel_id' => $hotel_id,
            'acf_room_id' => $acf_room_id,
            'room_title' => $room_title, 
            'check_in_date' => $check_in_date,
            'check_out_date' => $check_out_date,
        );

        update_option('akka_block_settings', $block_settings);

        $row_html = '<tr id="block-setting-row-' . esc_attr($id) . '">';
        $row_html .= '<td>' . esc_html(get_the_title($hotel_id)) . '</td>';
        $row_html .= '<td>' . esc_html($acf_room_id) . '</td>';
        $row_html .= '<td>' . esc_html($room_title) . '</td>';
        $row_html .= '<td>' . esc_html($check_in_date) . '</td>';
        $row_html .= '<td>' . esc_html($check_out_date) . '</td>';
        $row_html .= '<td><button class="delete-block-setting" data-id="' . esc_attr($id) . '">' . __('Delete', 'akka-pro') . '</button></td>';
        $row_html .= '</tr>';

        wp_send_json_success(array('row_html' => $row_html));
    }


    public function delete_block_setting() {
        check_ajax_referer('akka_pro_nonce', 'nonce');
    
        $id = sanitize_text_field($_POST['id']);
        $block_settings = get_option('akka_block_settings', array());
    
        if (isset($block_settings[$id])) {
            unset($block_settings[$id]);
            update_option('akka_block_settings', $block_settings);
            wp_send_json_success(); 
        } else {
            wp_send_json_error(array('message' => 'Block setting not found.'));
        }
    }

    public function get_block_settings() {
        check_ajax_referer('akka_pro_nonce', 'nonce');
        $block_settings = get_option('akka_block_settings', array());
        wp_send_json_success($block_settings);
    }

    public function delete_claim()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'akka_pro_claims';
        $id = sanitize_text_field($_POST['id']);

        $deleted = $wpdb->delete($table_name, array('id' => $id));

        if ($deleted) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Failed to delete claim.'));
        }
    }

    public function approve_claim()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'akka_pro_claims';
        $id = sanitize_text_field($_POST['id']);
        $reason = sanitize_textarea_field($_POST['reason']); // Capture the reason field

        // Fetch the claim details
        $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);

        // Check if the claim exists and is not already approved
        if ($claim && $claim['approval_status'] !== 'approved') {
            // Update the approval_status to 'approved' and save the reason
            $updated = $wpdb->update(
                $table_name,
                array(
                    'approval_status' => 'approved',
                    'reason' => $reason // Save the reason in the database
                ),
                array('id' => $id)
            );

            if ($updated !== false) {
                // Add points using myCRED
                $user_id = $claim['created_by'];
                $bonus_points = $claim['calculated_bonus'];
                $log_entry = 'Points for approved claim #' . $id;

                if (function_exists('mycred_add')) {
                    mycred_add('approved_claim', $user_id, $bonus_points, $log_entry);
                }

                wp_send_json_success();
            } else {
                wp_send_json_error(array('message' => 'Failed to approve claim.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Claim not found or already approved.'));
        }
    }

    public function deny_claim()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'akka_pro_claims';
        $id = sanitize_text_field($_POST['id']);
        $reason = sanitize_textarea_field($_POST['reason']);

        $updated = $wpdb->update(
            $table_name,
            array(
                'approval_status' => 'denied',
                'reason' => $reason // Save the reason in the database
            ),
            array('id' => $id)
        );

        if ($updated !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Failed to deny claim.'));
        }
    }

    public function save_market_bonus_settings() {
        check_ajax_referer('akka_pro_nonce', 'nonce');
    
        $market = sanitize_text_field($_POST['market']);
        $bonus_rate = sanitize_text_field($_POST['bonus_rate']);
        $duration = sanitize_text_field($_POST['duration']);
    
        $market_bonus_settings = get_option('akka_market_bonus_settings', array());
        $id = uniqid();
        $market_bonus_settings[$id] = array(
            'market' => $market,
            'bonus_rate' => $bonus_rate,
            'duration' => $duration,
        );
    
        update_option('akka_market_bonus_settings', $market_bonus_settings);
    
        $row_html = '<tr id="market-bonus-setting-row-' . esc_attr($id) . '">';
        $row_html .= '<td>' . esc_html($market) . '</td>';
        $row_html .= '<td>' . esc_html($bonus_rate) . '</td>';
        $row_html .= '<td>' . esc_html($duration) . '</td>';
        $row_html .= '<td><button class="delete-market-bonus-setting" data-id="' . esc_attr($id) . '">' . __('Delete', 'akka-pro') . '</button></td>';
        $row_html .= '</tr>';
    
        wp_send_json_success(array('row_html' => $row_html));
    }

    public function display_market_bonus_settings() {
        $market_bonus_settings = get_option('akka_market_bonus_settings', array());
    
        if (!empty($market_bonus_settings)) {
            foreach ($market_bonus_settings as $id => $settings) {
                $market = isset($settings['market']) ? esc_html($settings['market']) : ''; // Ensure market is retrieved correctly
                echo '<tr id="market-bonus-setting-row-' . esc_attr($id) . '">';
                echo '<td>' . $market . '</td>';
                echo '<td>' . esc_html($settings['bonus_rate']) . '</td>';
                echo '<td>' . esc_html($settings['duration']) . '</td>';
                echo '<td><button class="delete-market-bonus-setting" data-id="' . esc_attr($id) . '">' . __('Delete', 'akka-pro') . '</button></td>';
                echo '</tr>';
            }
        }
    } 
    
    // Function to delete market bonus setting
    public function delete_market_bonus_setting() {
        check_ajax_referer('akka_pro_nonce', 'nonce');
    
        $id = sanitize_text_field($_POST['id']);
        $market_bonus_settings = get_option('akka_market_bonus_settings', array());
    
        if (isset($market_bonus_settings[$id])) {
            unset($market_bonus_settings[$id]);
            update_option('akka_market_bonus_settings', $market_bonus_settings);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Market setting not found.'));
        }
    }

    public function save_discount_settings() {
        check_ajax_referer('akka_pro_nonce', 'nonce');
    
        $hotel_id = sanitize_text_field($_POST['akka_discount_hotel']);
        $acf_room_id = sanitize_text_field($_POST['akka_discount_room']);
        $discount_rate = sanitize_text_field($_POST['akka_discount_rate']);
        $date_range = sanitize_text_field($_POST['akka_discount_date_range']);
    
        // Split the date range into check-in and check-out dates
        $dates = explode(' to ', $date_range);
    
        // If only one date is selected, it is used for both check-in and check-out
        if (count($dates) === 1) {
            $check_in_date = $dates[0];
            $check_out_date = $dates[0];
        } else {
            $check_in_date = $dates[0];
            $check_out_date = $dates[1];
        }
    
        // Fetch the room post title based on the room ID
        $room_post = get_posts(array(
            'post_type' => 'room',
            'meta_key' => 'acf_room_id',
            'meta_value' => $acf_room_id,
            'posts_per_page' => 1
        ));
    
        $room_title = !empty($room_post) ? $room_post[0]->post_title : $acf_room_id;
    
        $discount_settings = get_option('akka_discount_settings', array());
        $id = uniqid();
        $discount_settings[$id] = array(
            'hotel_id' => $hotel_id,
            'acf_room_id' => $acf_room_id,
            'room_title' => $room_title,
            'check_in_date' => $check_in_date,
            'check_out_date' => $check_out_date,
            'discount_rate' => $discount_rate,
        );
    
        update_option('akka_discount_settings', $discount_settings);
    
        $row_html = '<tr id="discount-setting-row-' . esc_attr($id) . '">';
        $row_html .= '<td>' . esc_html(get_the_title($hotel_id)) . '</td>';
        $row_html .= '<td>' . esc_html($acf_room_id) . '</td>';
        $row_html .= '<td>' . esc_html($room_title) . '</td>';
        $row_html .= '<td>' . esc_html($check_in_date) . '</td>';
        $row_html .= '<td>' . esc_html($check_out_date) . '</td>';
        $row_html .= '<td>' . esc_html($discount_rate) . '</td>';
        $row_html .= '<td><button class="delete-discount-setting" data-id="' . esc_attr($id) . '">' . __('Delete', 'akka-pro') . '</button></td>';
        $row_html .= '</tr>';
    
        wp_send_json_success(array('row_html' => $row_html));
    }    

    public function delete_discount_setting()
    {
        check_ajax_referer('akka_pro_nonce', 'nonce');

        $id = sanitize_text_field($_POST['id']);

        $discount_settings = get_option('akka_discount_settings', array());

        if (isset($discount_settings[$id])) {
            unset($discount_settings[$id]);
            update_option('akka_discount_settings', $discount_settings);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Setting not found.'));
        }
    }

    public function display_discount_settings() {
        $discount_settings = get_option('akka_discount_settings', array());
    
        if (!empty($discount_settings)) {
            foreach ($discount_settings as $id => $settings) {
                echo '<tr id="discount-setting-row-' . esc_attr($id) . '">';
                echo '<td>' . esc_html(get_the_title($settings['hotel_id'])) . '</td>';
                echo '<td>' . esc_html($settings['acf_room_id']) . '</td>';
                echo '<td>' . esc_html($settings['room_title']) . '</td>';
                echo '<td>' . esc_html($settings['check_in_date']) . '</td>';
                echo '<td>' . esc_html($settings['check_out_date']) . '</td>';
                echo '<td>' . esc_html($settings['discount_rate']) . '</td>';
                echo '<td><button class="delete-discount-setting" data-id="' . esc_attr($id) . '">' . __('Delete', 'akka-pro') . '</button></td>';
                echo '</tr>';
            }
        }
    }    


    public function akka_pro_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $bonus_settings = get_option('akka_bonus_settings', array());
        $market_bonus_settings = get_option('akka_market_bonus_settings', array()); // Placeholder for Market-based settings
        $block_settings = get_option('akka_block_settings', array());
        $default_bonus_rate = get_option('akka_default_bonus_rate', '');

        global $wpdb;
        $table_name = $wpdb->prefix . 'akka_pro_claims';
        $claims = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        ?>
        <div class="wrap">
            <h1><?php _e('Akka Pro - Settings', 'akka-pro'); ?></h1>

            <div id="akka-pro-tabs">
                <ul>
                    <li><a href="#reservation-tab"><?php _e('Reservation', 'akka-pro'); ?></a></li>
                    <li><a href="#bonus-tab"><?php _e('Bonus', 'akka-pro'); ?></a></li>
                    <li><a href="#claim-tab"><?php _e('Claim', 'akka-pro'); ?></a></li>
                    <li><a href="#pages-tab"><?php _e('Pages', 'akka-pro'); ?></a></li>
                </ul>

                <!-- Reservation Tab -->
                <div id="reservation-tab">
                    <h2><?php _e('Reservation Settings', 'akka-pro'); ?></h2>
                    <div id="reservation-sub-tabs">
                    <ul>
                        <li><a href="#block-tab"><?php _e('Block', 'akka-pro'); ?></a></li>
                        <li><a href="#discount-tab"><?php _e('Discount', 'akka-pro'); ?></a></li> <!-- New Discount Sub-tab -->
                    </ul>

                        <!-- Block Tab -->
                        <div id="block-tab">
                            <h3><?php _e('Block Settings', 'akka-pro'); ?></h3>
                            <form id="akka-pro-block-form">
                                <div class="form-field">
                                    <label for="akka-block-hotel"><?php _e('Hotel', 'akka-pro'); ?></label>
                                    <select id="akka-block-hotel" name="akka_block_hotel" required>
                                        <option value=""><?php _e('Select a hotel', 'akka-pro'); ?></option>
                                        <?php
                                        $hotels = get_posts(array('post_type' => 'hotel', 'posts_per_page' => -1));
                                        foreach ($hotels as $hotel) {
                                            $acf_hotel_id = get_field('acf_hotel_id', $hotel->ID);
                                            echo '<option value="' . esc_attr($hotel->ID) . '" data-acf-hotel-id="' . esc_attr($acf_hotel_id) . '">' . esc_html($hotel->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="akka-block-room"><?php _e('Room', 'akka-pro'); ?></label>
                                    <select id="akka-block-room" name="akka_block_room" required>
                                        <option value=""><?php _e('Select a room', 'akka-pro'); ?></option>
                                        <!-- Will be populated by AJAX -->
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="akka-block-date-range"><?php _e('Date Range', 'akka-pro'); ?></label>
                                    <input type="text" id="akka-block-date-range" name="akka_block_date_range"
                                        placeholder="<?php _e('Select Date Range', 'akka-pro'); ?>" required>
                                </div>
                                <button type="submit"><?php _e('Save Block Settings', 'akka-pro'); ?></button>
                            </form>

                            <h3><?php _e('Saved Block Settings', 'akka-pro'); ?></h3>
                            <table id="block-settings-table" class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Hotel', 'akka-pro'); ?></th>
                                        <th><?php _e('Room ID', 'akka-pro'); ?></th>
                                        <th><?php _e('Room Title', 'akka-pro'); ?></th>
                                        <th><?php _e('Check-in', 'akka-pro'); ?></th>
                                        <th><?php _e('Check-out', 'akka-pro'); ?></th>
                                        <th><?php _e('Actions', 'akka-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($block_settings as $id => $settings): ?>
                                        <tr id="block-setting-row-<?php echo esc_attr($id); ?>">
                                            <td><?php echo esc_html(get_the_title($settings['hotel_id'])); ?></td>
                                            <td><?php echo esc_html($settings['acf_room_id']); ?></td>
                                            <td><?php echo esc_html($settings['room_title']); ?></td>
                                            <td><?php echo esc_html($settings['check_in_date']); ?></td>
                                            <td><?php echo esc_html($settings['check_out_date']); ?></td>
                                            <td>
                                                <button class="delete-block-setting" data-id="<?php echo esc_attr($id); ?>"><?php _e('Delete', 'akka-pro'); ?></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Discount Tab -->
                        <div id="discount-tab">
                            <h3><?php _e('Discount Settings', 'akka-pro'); ?></h3>

                            <form id="akka-pro-discount-form">
                                <div class="form-field">
                                    <label for="akka-discount-hotel"><?php _e('Hotel', 'akka-pro'); ?></label>
                                    <select id="akka-discount-hotel" name="akka_discount_hotel" required>
                                        <option value=""><?php _e('Select a hotel', 'akka-pro'); ?></option>
                                        <?php
                                        $hotels = get_posts(array('post_type' => 'hotel', 'posts_per_page' => -1));
                                        foreach ($hotels as $hotel) {
                                            $acf_hotel_id = get_field('acf_hotel_id', $hotel->ID);
                                            echo '<option value="' . esc_attr($hotel->ID) . '" data-acf-hotel-id="' . esc_attr($acf_hotel_id) . '">' . esc_html($hotel->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="akka-discount-room"><?php _e('Room', 'akka-pro'); ?></label>
                                    <select id="akka-discount-room" name="akka_discount_room" required>
                                        <option value=""><?php _e('Select a room', 'akka-pro'); ?></option>
                                        <!-- Will be populated by AJAX -->
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="akka-discount-date-range"><?php _e('Date Range', 'akka-pro'); ?></label>
                                    <input type="text" id="akka-discount-date-range" name="akka_discount_date_range" placeholder="<?php _e('Select Date Range', 'akka-pro'); ?>" required>
                                </div>
                                <div class="form-field">
                                    <label for="akka-discount-rate"><?php _e('Discount Rate (%)', 'akka-pro'); ?></label>
                                    <input type="number" id="akka-discount-rate" name="akka_discount_rate" placeholder="<?php _e('Enter Discount Rate', 'akka-pro'); ?>" min="0" max="100" step="0.01" required>
                                </div>
                                <button type="submit"><?php _e('Save Discount Settings', 'akka-pro'); ?></button>
                            </form>

                            <h3><?php _e('Saved Discount Settings', 'akka-pro'); ?></h3>
                            <table id="discount-settings-table" class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Hotel', 'akka-pro'); ?></th>
                                        <th><?php _e('Room ID', 'akka-pro'); ?></th>
                                        <th><?php _e('Room Title', 'akka-pro'); ?></th>
                                        <th><?php _e('Check-in', 'akka-pro'); ?></th>
                                        <th><?php _e('Check-out', 'akka-pro'); ?></th>
                                        <th><?php _e('Discount Rate', 'akka-pro'); ?></th>
                                        <th><?php _e('Actions', 'akka-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $this->display_discount_settings(); ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- Bonus Tab -->
                <div id="bonus-tab">
                    <h2><?php _e('Bonus Settings', 'akka-pro'); ?></h2>
                    <div id="bonus-sub-tabs">
                        <ul>
                            <li><a href="#room-based-tab"><?php _e('Room-based', 'akka-pro'); ?></a></li>
                            <li><a href="#market-based-tab"><?php _e('Market-based', 'akka-pro'); ?></a></li>
                        </ul>

                        <!-- Room-based Bonus Tab -->
                        <div id="room-based-tab">
                            <h3><?php _e('Room-based Bonus Settings', 'akka-pro'); ?></h3>
                            <!-- Default Bonus Rate Form -->
                            <form id="akka-pro-default-bonus-form">
                                <div class="form-field">
                                    <label for="akka-default-bonus-rate"><?php _e('Default Bonus Rate (%)', 'akka-pro'); ?></label>
                                    <input type="number" id="akka-default-bonus-rate" name="akka_default_bonus_rate" value="<?php echo esc_attr($default_bonus_rate); ?>" placeholder="<?php _e('Enter Default Bonus Rate', 'akka-pro'); ?>" min="0" max="100" step="0.01" required>
                                </div>
                                <button type="submit"><?php _e('Save Default Bonus Rate', 'akka-pro'); ?></button>
                            </form>

                            <form id="akka-pro-bonus-form">
                                <div class="form-field">
                                    <label for="akka-bonus-hotel"><?php _e('Hotel', 'akka-pro'); ?></label>
                                    <select id="akka-bonus-hotel" name="akka_bonus_hotel" required>
                                        <option value=""><?php _e('Select a hotel', 'akka-pro'); ?></option>
                                        <?php
                                        $hotels = get_posts(array('post_type' => 'hotel', 'posts_per_page' => -1));
                                        foreach ($hotels as $hotel) {
                                            $acf_hotel_id = get_field('acf_hotel_id', $hotel->ID);
                                            echo '<option value="' . esc_attr($hotel->ID) . '" data-acf-hotel-id="' . esc_attr($acf_hotel_id) . '">' . esc_html($hotel->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="akka-bonus-room"><?php _e('Room', 'akka-pro'); ?></label>
                                    <select id="akka-bonus-room" name="akka_bonus_room" required>
                                        <option value=""><?php _e('Select a room', 'akka-pro'); ?></option>
                                        <!-- Will be populated by AJAX -->
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="bonus_date_range"><?php _e('Bonus Date Range', 'akka-pro'); ?></label>
                                    <input type="text" id="bonus_date_range" name="bonus_date_range" class="datepicker" />
                                </div>
                                <div class="form-field">
                                    <label for="akka-bonus-rate"><?php _e('Bonus Rate (%)', 'akka-pro'); ?></label>
                                    <input type="number" id="akka-bonus-rate" name="akka_bonus_rate" placeholder="<?php _e('Enter Bonus Rate', 'akka-pro'); ?>" min="0" max="100" step="0.01" required>
                                </div>
                                <div class="form-field">
                                    <label for="akka-bonus-duration"><?php _e('Duration (days)', 'akka-pro'); ?></label>
                                    <input type="number" id="akka-bonus-duration" name="akka_bonus_duration" value="730" min="1" required>
                                </div>
                                <button type="submit"><?php _e('Save Bonus Settings', 'akka-pro'); ?></button>
                            </form>

                            <h3><?php _e('Saved Room-based Bonus Settings', 'akka-pro'); ?></h3>
                            <table id="bonus-settings-table" class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Hotel', 'akka-pro'); ?></th>
                                        <th><?php _e('Room ID', 'akka-pro'); ?></th>
                                        <th><?php _e('Room Title', 'akka-pro'); ?></th>
                                        <th><?php _e('Bonus Rate', 'akka-pro'); ?></th>
                                        <th><?php _e('Duration', 'akka-pro'); ?></th>
                                        <th><?php _e('Actions', 'akka-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bonus_settings as $id => $settings): ?>
                                        <tr id="bonus-setting-row-<?php echo esc_attr($id); ?>">
                                            <td><?php echo esc_html(get_the_title($settings['hotel_id'])); ?></td>
                                            <td><?php echo esc_html($settings['acf_room_id']); ?></td>
                                            <td><?php echo esc_html($settings['room_title']); ?></td>
                                            <td><?php echo esc_html($settings['bonus_rate']); ?></td>
                                            <td><?php echo esc_html($settings['duration']); ?></td>
                                            <td>
                                                <button class="delete-bonus-setting" data-id="<?php echo esc_attr($id); ?>"><?php _e('Delete', 'akka-pro'); ?></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Market-based Bonus Tab -->
                        <div id="market-based-tab">
                            <h3><?php _e('Market-based Bonus Settings', 'akka-pro'); ?></h3>

                            <form id="akka-pro-market-bonus-form">
                            <div class="form-field">
                                <label for="akka-market"><?php _e('Market', 'akka-pro'); ?></label>
                                <select id="akka-market" name="akka_market" required>
                                    <option value=""><?php _e('Select a market', 'akka-pro'); ?></option>
                                    <?php
                                    $markets = [
                                        'Afghanistan',
                                        'Åland Islands',
                                        'Albania',
                                        'Algeria',
                                        'American Samoa',
                                        'Andorra',
                                        'Angola',
                                        'Anguilla',
                                        'Antarctica',
                                        'Antigua and Barbuda',
                                        'Argentina',
                                        'Armenia',
                                        'Aruba',
                                        'Australia',
                                        'Austria',
                                        'Azerbaijan',
                                        'Bahamas',
                                        'Bahrain',
                                        'Bangladesh',
                                        'Barbados',
                                        'Belarus',
                                        'Belgium',
                                        'Belize',
                                        'Benin',
                                        'Bermuda',
                                        'Bhutan',
                                        'Bolivia, Plurinational State of',
                                        'Bosnia and Herzegovina',
                                        'Botswana',
                                        'Bouvet Island',
                                        'Brazil',
                                        'British Indian Ocean Territory',
                                        'Brunei Darussalam',
                                        'Bulgaria',
                                        'Burkina Faso',
                                        'Burundi',
                                        'Cambodia',
                                        'Cameroon',
                                        'Canada',
                                        'Cape Verde',
                                        'Cayman Islands',
                                        'Central African Republic',
                                        'Chad',
                                        'Chile',
                                        'China',
                                        'Christmas Island',
                                        'Cocos (Keeling) Islands',
                                        'Colombia',
                                        'Comoros',
                                        'Congo',
                                        'Congo, the Democratic Republic of the',
                                        'Cook Islands',
                                        'Costa Rica',
                                        'Côte d\'Ivoire',
                                        'Croatia',
                                        'Cuba',
                                        'Cyprus',
                                        'Czech Republic',
                                        'Denmark',
                                        'Djibouti',
                                        'Dominica',
                                        'Dominican Republic',
                                        'Ecuador',
                                        'Egypt',
                                        'El Salvador',
                                        'Equatorial Guinea',
                                        'Eritrea',
                                        'Estonia',
                                        'Ethiopia',
                                        'Falkland Islands (Malvinas)',
                                        'Faroe Islands',
                                        'Fiji',
                                        'Finland',
                                        'France',
                                        'French Guiana',
                                        'French Polynesia',
                                        'French Southern Territories',
                                        'Gabon',
                                        'Gambia',
                                        'Sakartvelo',
                                        'Germany',
                                        'Ghana',
                                        'Gibraltar',
                                        'Greece',
                                        'Greenland',
                                        'Grenada',
                                        'Guadeloupe',
                                        'Guam',
                                        'Guatemala',
                                        'Guernsey',
                                        'Guinea',
                                        'Guinea-Bissau',
                                        'Guyana',
                                        'Haiti',
                                        'Heard Island and McDonald Islands',
                                        'Holy See (Vatican City State)',
                                        'Honduras',
                                        'Hong Kong',
                                        'Hungary',
                                        'Iceland',
                                        'India',
                                        'Indonesia',
                                        'Iran, Islamic Republic of',
                                        'Iraq',
                                        'Ireland',
                                        'Isle of Man',
                                        'Israel',
                                        'Italy',
                                        'Jamaica',
                                        'Japan',
                                        'Jersey',
                                        'Jordan',
                                        'Kazakhstan',
                                        'Kenya',
                                        'Kiribati',
                                        'Korea, Democratic People\'s Republic of',
                                        'Korea, Republic of',
                                        'Kuwait',
                                        'Kyrgyzstan',
                                        'Lao People\'s Democratic Republic',
                                        'Latvia',
                                        'Lebanon',
                                        'Lesotho',
                                        'Liberia',
                                        'Libyan Arab Jamahiriya',
                                        'Liechtenstein',
                                        'Lithuania',
                                        'Luxembourg',
                                        'Macao',
                                        'Macedonia, the former Yugoslav Republic of',
                                        'Madagascar',
                                        'Malawi',
                                        'Malaysia',
                                        'Maldives',
                                        'Mali',
                                        'Malta',
                                        'Marshall Islands',
                                        'Martinique',
                                        'Mauritania',
                                        'Mauritius',
                                        'Mayotte',
                                        'Mexico',
                                        'Micronesia, Federated States of',
                                        'Moldova, Republic of',
                                        'Monaco',
                                        'Mongolia',
                                        'Montenegro',
                                        'Montserrat',
                                        'Morocco',
                                        'Mozambique',
                                        'Myanmar',
                                        'Namibia',
                                        'Nauru',
                                        'Nepal',
                                        'Netherlands',
                                        'Netherlands Antilles',
                                        'New Caledonia',
                                        'New Zealand',
                                        'Nicaragua',
                                        'Niger',
                                        'Nigeria',
                                        'Niue',
                                        'Norfolk Island',
                                        'Northern Mariana Islands',
                                        'Norway',
                                        'Oman',
                                        'Pakistan',
                                        'Palau',
                                        'Palestine',
                                        'Panama',
                                        'Papua New Guinea',
                                        'Paraguay',
                                        'Peru',
                                        'Philippines',
                                        'Pitcairn',
                                        'Poland',
                                        'Portugal',
                                        'Puerto Rico',
                                        'Qatar',
                                        'Réunion',
                                        'Romania',
                                        'Russia',
                                        'Rwanda',
                                        'Saint Barthélemy',
                                        'Saint Helena',
                                        'Saint Kitts and Nevis',
                                        'Saint Lucia',
                                        'Saint Martin (French part)',
                                        'Saint Pierre and Miquelon',
                                        'Saint Vincent and the Grenadines',
                                        'Samoa',
                                        'San Marino',
                                        'Sao Tome and Principe',
                                        'Saudi Arabia',
                                        'Senegal',
                                        'Serbia',
                                        'Seychelles',
                                        'Sierra Leone',
                                        'Singapore',
                                        'Slovakia',
                                        'Slovenia',
                                        'Solomon Islands',
                                        'Somalia',
                                        'South Africa',
                                        'South Georgia and the South Sandwich Islands',
                                        'South Sudan',
                                        'Spain',
                                        'Sri Lanka',
                                        'Sudan',
                                        'Suriname',
                                        'Svalbard and Jan Mayen',
                                        'Eswatini',
                                        'Sweden',
                                        'Switzerland',
                                        'Syrian Arab Republic',
                                        'Taiwan, Province of China',
                                        'Tajikistan',
                                        'Tanzania, United Republic of',
                                        'Thailand',
                                        'Timor-Leste',
                                        'Togo',
                                        'Tokelau',
                                        'Tonga',
                                        'Trinidad and Tobago',
                                        'Tunisia',
                                        'Turkey',
                                        'Turkmenistan',
                                        'Turks and Caicos Islands',
                                        'Tuvalu',
                                        'Uganda',
                                        'Ukraine',
                                        'United Arab Emirates',
                                        'United Kingdom',
                                        'United States',
                                        'United States Minor Outlying Islands',
                                        'Uruguay',
                                        'Uzbekistan',
                                        'Vanuatu',
                                        'Venezuela, Bolivarian Republic of',
                                        'Viet Nam',
                                        'Virgin Islands, British',
                                        'Virgin Islands, U.S.',
                                        'Wallis and Futuna',
                                        'Western Sahara',
                                        'Yemen',
                                        'Zambia',
                                        'Zimbabwe'
                                    ];

                                    foreach ($markets as $market) {
                                        echo '<option value="' . esc_attr($market) . '">' . esc_html($market) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                                <div class="form-field">
                                    <label for="akka-market-rate"><?php _e('Bonus Rate (%)', 'akka-pro'); ?></label>
                                    <input type="number" id="akka-market-rate" name="akka_market_rate" placeholder="<?php _e('Enter Bonus Rate', 'akka-pro'); ?>" min="0" max="100" step="0.01" required>
                                </div>
                                <div class="form-field">
                                    <label for="akka-market-duration"><?php _e('Duration (days)', 'akka-pro'); ?></label>
                                    <input type="number" id="akka-market-duration" name="akka_market_duration" value="730" min="1" required>
                                </div>
                                <button type="submit"><?php _e('Save Market-based Bonus Settings', 'akka-pro'); ?></button>
                            </form>

                            <h3><?php _e('Saved Market-based Bonus Settings', 'akka-pro'); ?></h3>
                            <table id="market-bonus-settings-table" class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Market', 'akka-pro'); ?></th>
                                        <th><?php _e('Bonus Rate (%)', 'akka-pro'); ?></th>
                                        <th><?php _e('Duration (days)', 'akka-pro'); ?></th>
                                        <th><?php _e('Actions', 'akka-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $this->display_market_bonus_settings(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Claim Tab -->
                <div id="claim-tab">
                    <h2><?php _e('Claim Submissions', 'akka-pro'); ?></h2>
                    <?php if (empty($claims)) : ?>
                        <p><?php _e('No claims have been submitted yet.', 'akka-pro'); ?></p>
                    <?php else : ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Claim ID', 'akka-pro'); ?></th>
                                    <th><?php _e('Voucher No', 'akka-pro'); ?></th>
                                    <th><?php _e('Operator', 'akka-pro'); ?></th>
                                    <th><?php _e('Guest Name', 'akka-pro'); ?></th>
                                    <th><?php _e('Guest Surname', 'akka-pro'); ?></th>
                                    <th><?php _e('Check-in Date', 'akka-pro'); ?></th>
                                    <th><?php _e('Check-out Date', 'akka-pro'); ?></th>
                                    <th><?php _e('Hotel', 'akka-pro'); ?></th>
                                    <th><?php _e('Room ID', 'akka-pro'); ?></th>
                                    <th><?php _e('Room Title', 'akka-pro'); ?></th>
                                    <th><?php _e('Total Price', 'akka-pro'); ?></th>
                                    <th><?php _e('Bonus Rate', 'akka-pro'); ?></th>
                                    <th><?php _e('Bonus Amount', 'akka-pro'); ?></th>
                                    <th><?php _e('Adults', 'akka-pro'); ?></th>
                                    <th><?php _e('Children', 'akka-pro'); ?></th>
                                    <th><?php _e('Children Ages', 'akka-pro'); ?></th>
                                    <th><?php _e('Notes', 'akka-pro'); ?></th>
                                    <th><?php _e('Created By', 'akka-pro'); ?></th>
                                    <th><?php _e('Creation Date', 'akka-pro'); ?></th>
                                    <th><?php _e('Expiry Date', 'akka-pro'); ?></th>
                                    <th><?php _e('Actions', 'akka-pro'); ?></th>
                                    <th><?php _e('Denial Reason', 'akka-pro'); ?></th>  <!-- Updated column name -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($claims as $claim) : 
                                    // Fetch the room title based on the room ID
                                    $room_post = get_posts(array(
                                        'post_type' => 'room',
                                        'meta_key' => 'acf_room_id',
                                        'meta_value' => $claim['room_id'],
                                        'posts_per_page' => 1
                                    ));

                                    $room_title = !empty($room_post) ? $room_post[0]->post_title : __('Unknown Room', 'akka-pro');

                                    // Fetch the hotel title based on the hotel ID
                                    $hotel_post = get_posts(array(
                                        'post_type' => 'hotel',
                                        'meta_key' => 'acf_hotel_id',
                                        'meta_value' => $claim['hotel_id'],
                                        'posts_per_page' => 1
                                    ));

                                    $hotel_title = !empty($hotel_post) ? $hotel_post[0]->post_title : __('Unknown Hotel', 'akka-pro');

                                    // Fetch the operator title based on the operator ID
                                    $operator_post = get_post($claim['operator_id']);
                                    $operator_title = !empty($operator_post) ? $operator_post->post_title . ' (' . $operator_post->ID . ')' : __('Unknown Operator', 'akka-pro');

                                    // Fetch the user who created the claim
                                    $user_info = get_userdata($claim['created_by']);
                                    $created_by = !empty($user_info) ? $user_info->user_login . ' (' . $user_info->ID . ')' : __('Unknown User', 'akka-pro');
                                ?>
                                    <tr id="claim-row-<?php echo esc_attr($claim['id']); ?>">
                                        <td><?php echo esc_html($claim['id']); ?></td>
                                        <td><?php echo esc_html($claim['voucher_no']); ?></td>
                                        <td><?php echo esc_html($operator_title); ?></td>
                                        <td><?php echo esc_html($claim['guest_name']); ?></td>
                                        <td><?php echo esc_html($claim['guest_surname']); ?></td>
                                        <td><?php echo esc_html($claim['check_in_date']); ?></td>
                                        <td><?php echo esc_html($claim['check_out_date']); ?></td>
                                        <td><?php echo esc_html($hotel_title); ?></td>
                                        <td><?php echo esc_html($claim['room_id']); ?></td>
                                        <td><?php echo esc_html($room_title); ?></td>
                                        <td><?php echo esc_html($claim['total_price']); ?></td>
                                        <td><?php echo esc_html($claim['bonus_rate']); ?></td>
                                        <td><?php echo esc_html($claim['calculated_bonus']); ?></td>
                                        <td><?php echo esc_html($claim['adult_count']); ?></td>
                                        <td><?php echo esc_html($claim['children_count']); ?></td>
                                        <td><?php echo esc_html(implode(', ', json_decode($claim['child_ages'], true))); ?></td>
                                        <td><?php echo esc_html($claim['notes']); ?></td>
                                        <td><?php echo esc_html($created_by); ?></td>
                                        <td><?php echo esc_html($claim['creation_date']); ?></td>
                                        <td><?php echo esc_html($claim['expiry_date']); ?></td>
                                        <td>
                                            <div class="claim-actions">
                                                <button id="approve-<?php echo esc_attr($claim['id']); ?>" class="approve-claim <?php echo $claim['approval_status'] === 'approved' ? 'claim-status-selected' : ''; ?>" data-id="<?php echo esc_attr($claim['id']); ?>">
                                                    <?php _e('Approve', 'akka-pro'); ?>
                                                </button>
                                                <button id="deny-<?php echo esc_attr($claim['id']); ?>" class="deny-claim <?php echo $claim['approval_status'] === 'denied' ? 'claim-status-selected' : ''; ?>" data-id="<?php echo esc_attr($claim['id']); ?>">
                                                    <?php _e('Deny', 'akka-pro'); ?>
                                                </button>
                                            </div>
                                            <textarea id="reason-<?php echo esc_attr($claim['id']); ?>" class="claim-reason" placeholder="<?php _e('Enter denial reason', 'akka-pro'); ?>"><?php echo esc_textarea($claim['reason']); ?></textarea>
                                        </td>
                                        <td class="claim-reason-cell">
                                            <textarea id="reason-<?php echo esc_attr($claim['id']); ?>" class="claim-reason" placeholder="<?php _e('Enter claim reason', 'akka-pro'); ?>"><?php echo esc_textarea($claim['reason']); ?></textarea>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Pages Tab -->
                <div id="pages-tab">
                    <h2><?php _e('Page Selection Settings', 'akka-pro'); ?></h2>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('akka_pro_settings_group');
                        do_settings_sections('akka-pro-settings');
                        submit_button();
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }


}
