<?php
class Akka_Pro_Profile {

    public function __construct() {
        // Add shortcode for profile display
        add_shortcode('akka_pro_profile', array($this, 'display_profile_page'));

        // Handle AJAX for editing claims
        add_action('wp_ajax_save_edited_claim', array($this, 'save_edited_claim'));

        // Enqueue scripts and styles for profile page
        add_action('wp_enqueue_scripts', array($this, 'enqueue_profile_scripts'));
    }

    public function enqueue_profile_scripts() {
        // Get the selected profile page ID from the options
        $profile_page_id = get_option('akka_pro_profile_page');
        
        // Get the current page ID
        $current_page_id = get_queried_object_id();
    
        // Conditionally enqueue scripts based on the current page ID
        if ($current_page_id == $profile_page_id) {
            wp_enqueue_style('akka-pro-profile-css', AKKA_PRO_PLUGIN_URL . 'assets/css/profile.css');
            wp_enqueue_script('akka-pro-profile-js', AKKA_PRO_PLUGIN_URL . 'assets/js/profile.js', array('jquery'), null, true);
    
            wp_localize_script('akka-pro-profile-js', 'akkaPro', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akka_pro_nonce'),
            ));
        }
    }
    

    public function display_profile_page() {
        if (!is_user_logged_in()) {
            return __('You need to be logged in to view this page.', 'akka-pro');
        }
    
        ob_start();
    
        $current_user = wp_get_current_user();
        $claims = $this->get_user_claims($current_user->ID);
    
        echo '<div id="akka-pro-profile">';
        echo '<h2>' . sprintf(__('Welcome %s', 'akka-pro'), esc_html($current_user->display_name)) . '</h2>';
        echo '<div id="akka-pro-user-points">' . __('Your Points: ', 'akka-pro') . mycred_get_users_cred($current_user->ID) . '</div>';
    
        if (!empty($claims)) {
            echo '<h3>' . __('Your Claim Submissions', 'akka-pro') . '</h3>';
            echo '<table id="akka-pro-claims-table" class="widefat">';
            echo '<thead><tr>';
            echo '<th>' . __('Claim ID', 'akka-pro') . '</th>';
            echo '<th>' . __('Voucher No', 'akka-pro') . '</th>';
            echo '<th>' . __('Operator', 'akka-pro') . '</th>';
            echo '<th>' . __('Guest Name', 'akka-pro') . '</th>';
            echo '<th>' . __('Guest Surname', 'akka-pro') . '</th>';
            echo '<th>' . __('Hotel', 'akka-pro') . '</th>';
            echo '<th>' . __('Room', 'akka-pro') . '</th>';
            echo '<th>' . __('Check-in Date', 'akka-pro') . '</th>';
            echo '<th>' . __('Check-out Date', 'akka-pro') . '</th>';
            echo '<th>' . __('Adults', 'akka-pro') . '</th>';
            echo '<th>' . __('Children', 'akka-pro') . '</th>';
            echo '<th>' . __('Child Ages', 'akka-pro') . '</th>';
            echo '<th>' . __('Notes', 'akka-pro') . '</th>';
            echo '<th>' . __('Status', 'akka-pro') . '</th>';
            echo '<th>' . __('Actions', 'akka-pro') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($claims as $claim) {
                $hotel_id = $this->get_hotel_id($claim['hotel_id']);
                $hotel_title = $this->get_hotel_title($claim['hotel_id']);
                $room_id = $this->get_room_id($claim['room_id']);
                $room_title = $this->get_room_title($claim['room_id']);
                $operator_title = $this->get_operator_title($claim['operator_id']);
                $child_ages = !empty($claim['child_ages']) ? implode(', ', array_filter(json_decode($claim['child_ages'], true), function($age) { return $age !== null; })) : __('N/A', 'akka-pro');
    
                echo '<tr>';
                echo '<td>' . esc_html($claim['id']) . '</td>';
                echo '<td>' . esc_html($claim['voucher_no']) . '</td>';
                echo '<td data-id="' . esc_attr($claim['operator_id']) . '">' . esc_html($operator_title) . '</td>';
                echo '<td>' . esc_html($claim['guest_name']) . '</td>';
                echo '<td>' . esc_html($claim['guest_surname']) . '</td>';
                echo '<td data-id="' . esc_attr($hotel_id) . '">' . esc_html($hotel_title) . '</td>';
                echo '<td data-id="' . esc_attr($room_id) . '">' . esc_html($room_title) . '</td>';
                echo '<td>' . esc_html($claim['check_in_date']) . '</td>';
                echo '<td>' . esc_html($claim['check_out_date']) . '</td>';
                echo '<td>' . esc_html($claim['adult_count']) . '</td>';
                echo '<td>' . esc_html($claim['children_count']) . '</td>';
                echo '<td>' . esc_html($child_ages) . '</td>';
                echo '<td>' . esc_html($claim['notes']) . '</td>';
                echo '<td>' . esc_html($claim['approval_status']) . '</td>';
                echo '<td>';
                if (strtotime($claim['check_in_date']) > time()) {
                    echo '<button class="edit-claim" data-id="' . esc_attr($claim['id']) . '">' . __('Edit', 'akka-pro') . '</button>';
                } else {
                    echo __('Editing disabled after check-in date', 'akka-pro');
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No claim submissions found.', 'akka-pro') . '</p>';
        }
    
        echo '<div id="akka-pro-edit-claim-modal" class="akka-pro-edit-claim-modal">';
        echo '<div class="akka-pro-modal-content">';
        echo '<span class="akka-pro-close">&times;</span>';
        echo '<h2>' . __('Edit Claim', 'akka-pro') . '</h2>';
        
        echo '</div>';
        echo '</div>';
    
        echo '</div>'; // End of akka-pro-profile div
    
        return ob_get_clean();
    }
    
    private function get_operator_title($operator_id) {
        $operator_post = get_post($operator_id);
        return !empty($operator_post) ? $operator_post->post_title : __('Unknown Operator', 'akka-pro');
    }
    
    private function get_user_claims($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'akka_pro_claims';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE created_by = %d", $user_id), ARRAY_A);
    }
    
    private function get_hotel_id($hotel_id) {
        $hotel_post = get_posts(array(
            'post_type' => 'hotel',
            'meta_key' => 'acf_hotel_id',
            'meta_value' => $hotel_id,
            'posts_per_page' => 1
        ));
    
        return !empty($hotel_post) ? get_post_meta($hotel_post[0]->ID, 'acf_hotel_id', true) : '';
    }
    
    private function get_hotel_title($hotel_id) {
        $hotel_post = get_posts(array(
            'post_type' => 'hotel',
            'meta_key' => 'acf_hotel_id',
            'meta_value' => $hotel_id,
            'posts_per_page' => 1
        ));
    
        return !empty($hotel_post) ? $hotel_post[0]->post_title : __('Unknown Hotel', 'akka-pro');
    }
    
    private function get_room_id($room_id) {
        $room_post = get_posts(array(
            'post_type' => 'room',
            'meta_key' => 'acf_room_id',
            'meta_value' => $room_id,
            'posts_per_page' => 1
        ));
    
        return !empty($room_post) ? get_post_meta($room_post[0]->ID, 'acf_room_id', true) : '';
    }
    
    private function get_room_title($room_id) {
        $room_post = get_posts(array(
            'post_type' => 'room',
            'meta_key' => 'acf_room_id',
            'meta_value' => $room_id,
            'posts_per_page' => 1
        ));
    
        return !empty($room_post) ? $room_post[0]->post_title : __('Unknown Room', 'akka-pro');
    }

    public function save_edited_claim() {
        check_ajax_referer('akka_pro_nonce', 'nonce');
    
        // Check if edit_claim_id is set
        if (!isset($_POST['edit_claim_id'])) {
            wp_send_json_error(array('message' => __('Missing claim ID.', 'akka-pro')));
            return; 
        }
    
        $claim_id = intval($_POST['edit_claim_id']);
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'akka_pro_claims';
    
        // Fetch the claim by its ID and the current user's ID
        $claim = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND created_by = %d", 
            $claim_id, 
            get_current_user_id()
        ), ARRAY_A);
    
        if (!$claim) {
            wp_send_json_error(array('message' => __('Claim not found or you do not have permission to edit this claim.', 'akka-pro')));
            return;
        }
    
        // Check if the claim can be edited (e.g., before check-in date)
        if (strtotime($claim['check_in_date']) > time()) {
            // Initialize data array for update
            $data_to_update = array();
    
            // Sanitize and add form data to update array only if they exist in $_POST
            if (isset($_POST['voucher-no'])) {
                $data_to_update['voucher_no'] = sanitize_text_field($_POST['voucher-no']);
            }
            if (isset($_POST['operator'])) {
                $data_to_update['operator_id'] = intval($_POST['operator']);
            }
            if (isset($_POST['guest-name'])) {
                $data_to_update['guest_name'] = sanitize_text_field($_POST['guest-name']);
            }
            if (isset($_POST['guest-surname'])) {
                $data_to_update['guest_surname'] = sanitize_text_field($_POST['guest-surname']);
            }
            if (isset($_POST['date-range'])) {
                $date_range = explode(' to ', sanitize_text_field($_POST['date-range']));
                $data_to_update['check_in_date'] = $date_range[0];
                $data_to_update['check_out_date'] = $date_range[1];
            }
            if (isset($_POST['hotel'])) {
                $data_to_update['company_id'] = sanitize_text_field($_POST['hotel']); // Using company_id for hotel ID
            }
            if (isset($_POST['room_id'])) {
                $data_to_update['room_id'] = sanitize_text_field($_POST['room_id']);
            }
            if (isset($_POST['adult'])) {
                $data_to_update['adult_count'] = intval($_POST['adult']);
            }
            if (isset($_POST['child'])) {
                $data_to_update['children_count'] = intval($_POST['child']);
            }
            if (isset($_POST['child_ages'])) {
                // Parse and sanitize child ages
                $child_ages = json_decode(stripslashes($_POST['child_ages']), true);
                $child_ages = array_filter($child_ages, function($age) {
                    return $age !== null && $age !== '';
                });
                $data_to_update['child_ages'] = json_encode(array_values($child_ages));
            }
            if (isset($_POST['notes'])) {
                $data_to_update['notes'] = sanitize_textarea_field($_POST['notes']);
            }
    
            // Reset approval status
            $data_to_update['approval_status'] = ''; 
    
            // Update the claim in the database
            $updated = $wpdb->update(
                $table_name,
                $data_to_update, // Use the dynamically built update array
                array('id' => $claim_id)
            );
    
            if ($updated !== false) {
                wp_send_json_success(array('message' => __('Claim updated successfully.', 'akka-pro')));
                Akka_Pro_Notification::show_notification('Claim submitted successfully!', 'success', 'Success!');
            } else {
                wp_send_json_error(array('message' => __('Failed to update claim.', 'akka-pro')));
            }
        } else {
            wp_send_json_error(array('message' => __('Cannot edit claim after the check-in date.', 'akka-pro')));
        }
    }
}

// Instantiate the class
new Akka_Pro_Profile();
