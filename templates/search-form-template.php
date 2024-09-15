<div class="booking-form-container">
    <div id="booking-loader" class="booking-loader" style="display: none;">
        <div class="loader-content">
            <img src="https://go.akkahotels.com/wp-content/uploads/2024/06/loading.gif" alt="Loading">
        </div>
    </div>
    <form class="booking-form" id="akka-booking-form">
        <input type="hidden" id="company_id" name="company_id" value="">
        <div class="form-group">
            <label for="destination">Where to? (Required)</label>
            <select id="destination" name="destination" required>
                <option value="" disabled selected>Select a destination</option>
                <?php foreach ($hotels as $hotel) :
                    $acf_hotel_id = get_field('acf_hotel_id', $hotel->ID); ?>
                    <option value="<?php echo esc_attr($hotel->post_title); ?>" data-hotel-id="<?php echo esc_attr($acf_hotel_id); ?>">
                        <?php echo esc_html($hotel->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="date-range">When?</label>
            <input type="text" id="date-range" name="date-range" placeholder="Add dates" required>
        </div>
        <div class="form-group">
            <label for="rooms">Rooms & Guests</label>
            <button type="button" id="room-guest-btn" aria-label="Add guests">1 Room, 1 Guest <i class="fas fa-chevron-down"></i></button>
        </div>
        <div id="child-ages-container">
            <!-- Child age selects will be dynamically added here -->
        </div>
        <div class="form-group">
            <button type="submit" class="search-btn">Search</button>
        </div>
    </form>
</div>

<div id="room-guest-modal" class="modal" aria-modal="true" role="dialog" tabindex="-1" aria-label="Rooms and guests">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Rooms and guests</h2>
            <button type="button" class="close-btn" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="room-container">
                <div class="room-header">
                    <span>Rooms</span>
                    <span>Adults</span>
                    <span>Kids</span>
                    <span class="kid-ages-column">Kid's Age</span>
                </div>
                <div class="room-row" data-room="1">
                    <div class="room-info">
                        <span class="remove-room" aria-label="Remove room">&times;</span>
                        <span class="room-label">Room 1</span>
                    </div>
                    <div class="guest-controls">
                        <button class="control-btn decrease-adult" disabled>-</button>
                        <span class="guest-count adult-count">1</span>
                        <button class="control-btn increase-adult">+</button>
                    </div>
                    <div class="guest-controls">
                        <button class="control-btn decrease-kid" disabled>-</button>
                        <span class="guest-count kid-count">0</span>
                        <button class="control-btn increase-kid">+</button>
                    </div>
                    <div class="kid-ages-row"></div>
                </div>
            </div>
            <button type="button" id="add-room" class="add-room-btn">Add Room</button>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel">Cancel</button>
            <button type="button" class="btn btn-done">Done</button>
        </div>
    </div>
</div>
<div id="dialog-overlay" class="dialog-overlay"></div>
