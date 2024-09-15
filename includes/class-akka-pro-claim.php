<?php
class Akka_Pro_Claim
{
    public function __construct()
    {
        // Register the shortcode during the 'init' action
        add_action('init', array($this, 'register_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_claim_scripts'));
    }

    public function register_shortcode()
    {
        add_shortcode('claim_form', array($this, 'render_claim_form'));
    }

    public function enqueue_claim_scripts()
    {
        // Get the selected claim page ID from the options
        $claim_page_id = get_option('akka_pro_claim_page'); 

        // Get the current page ID
        $current_page_id = get_queried_object_id();

        // Conditionally enqueue scripts based on the current page ID
        if ($current_page_id == $claim_page_id) { 
            wp_enqueue_script('akka-pro-claim', plugin_dir_url(__FILE__) . 'assets/js/claim.js', array('jquery'), AKKA_PRO_VERSION, true);
            wp_enqueue_style('akka-pro-claim', plugin_dir_url(__FILE__) . 'assets/css/claim-styles.css', array(), AKKA_PRO_VERSION, 'all'); 

            // Enqueue Flatpickr for date selection
            wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
            wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);

            // Localize script for AJAX
            wp_localize_script('akka-pro-claim', 'akka_pro_data', array(
                'ajax_url' => rest_url('akka/v1/submit-claim'),
                'nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }

    public function render_claim_form()
    {
        ob_start();
        ?>
        <div class="claim-form-container">
            <form id="claim-form" class="claim-form">
                <div class="form-group">
                    <label for="voucher-no">Operator Voucher No</label>
                    <input type="text" id="voucher-no" name="voucher_no" required>
                </div>
                <div class="form-group">
                    <label for="operator">Select Operator</label>
                    <select id="operator" name="operator" required>
                        <option value="">Select</option>
                        <?php
                        $operators = get_posts(array('post_type' => 'operator', 'numberposts' => -1));
                        foreach ($operators as $operator):
                            ?>
                            <option value="<?php echo esc_attr($operator->ID); ?>">
                                <?php echo esc_html($operator->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guest-name">Guest Name</label>
                    <input type="text" id="guest-name" name="guest_name" required>
                </div>
                <div class="form-group">
                    <label for="guest-surname">Guest Surname</label>
                    <input type="text" id="guest-surname" name="guest_surname" required>
                </div>
                <div class="form-group">
                    <label for="date-range">Check-in & Check-out Dates</label>
                    <input type="text" id="date-range" name="date-range" placeholder="Select Date Range" required>
                </div>
                <div class="form-group">
                    <label for="hotel">Select Hotel</label>
                    <select id="hotel" name="hotel" required>
                        <option value="">Select</option>
                        <?php
                        $hotels = get_posts(array('post_type' => 'hotel', 'numberposts' => -1));
                        foreach ($hotels as $hotel):
                            $hotel_acf_id = get_field('acf_hotel_id', $hotel->ID);
                            ?>
                            <option value="<?php echo esc_attr($hotel_acf_id); ?>">
                                <?php echo esc_html($hotel->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full-width" id="room-group">
                    <label for="room">Room</label>
                    <select id="room" name="room" disabled required>
                        <option value="">Select Hotel First</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adult">Adult</label>
                    <select id="adult" name="adult" required>
                        <option value="">Select Adult</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="child">Child</label>
                    <select id="child" name="child" required>
                        <option value="">Select Child</option>
                        <?php for ($i = 0; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class=" form-group full-width" id="child-ages-group" style="display: none;">
                            <label>Child Ages</label>
                            <div id="child-ages-container"></div>
                </div>
                <div class="form-group full-width">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"></textarea>
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="save-btn">Save</button>
                </div>
                <?php wp_nonce_field('akka_pro_claim', 'akka_pro_claim_nonce'); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

}
