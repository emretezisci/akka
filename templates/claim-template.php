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
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group full-width" id="child-ages-group" style="display: none;">
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
