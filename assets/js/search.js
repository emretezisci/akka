jQuery(document).ready(function ($) {
    var debug = true; // Toggle debug mode

    function log(...args) {
        if (debug) {
            console.log(...args);
        }
    }

    var dateRangePicker = document.getElementById('date-range');
    if (dateRangePicker) {
        flatpickr(dateRangePicker, {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: "today",
            closeOnSelect: true
        });
    }

    $('#destination').on('change', function () {
        var selectedOption = $(this).find('option:selected');
        $('#company_id').val(selectedOption.data('hotel-id'));
    });

    // Room and guest modal functionality
    var modal = document.getElementById("room-guest-modal");
    var btn = document.getElementById("room-guest-btn");
    var closeBtn = document.querySelector(".close-btn");
    var overlay = document.getElementById("dialog-overlay");
    var cancelBtn = document.querySelector(".btn-cancel");
    var doneBtn = document.querySelector(".btn-done");
    var addRoomBtn = document.getElementById("add-room");
    var roomContainer = document.querySelector(".room-container");

    btn.onclick = function () {
        modal.style.display = "block";
        overlay.style.display = "block";
    };

    closeBtn.onclick = cancelBtn.onclick = overlay.onclick = function () {
        modal.style.display = "none";
        overlay.style.display = "none";
    };

    doneBtn.onclick = function () {
        updateRoomGuestCount();
        modal.style.display = "none";
        overlay.style.display = "none";
    };

    addRoomBtn.onclick = addRoom;

    function addRoom() {
        var roomCount = roomContainer.querySelectorAll('.room-row').length + 1;

        // Create the new room row first
        var newRoom = createRoomRow(roomCount);
        roomContainer.appendChild(newRoom);

        // Now check for kids and update the columns
        updateAllRoomRows(); // This will update the header AND apply classes to the new row
        updateRemoveButtons();
    }

    function createRoomRow(roomNumber) {
        var roomRow = document.createElement('div');
        roomRow.className = 'room-row';
        roomRow.dataset.room = roomNumber;

        // Check if the 'show-age-column' class is on the header
        var hasKidsAgeColumn = $('.room-header').hasClass('show-age-column');

        roomRow.innerHTML = `
            <div class="room-info">
                <span class="remove-room">×</span>
                <span class="room-label">Room ${roomNumber}</span>
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
            <div class="kid-ages-row ${hasKidsAgeColumn ? 'show-age-column' : ''}"></div> 
        `;
        return roomRow;
    }

    function updateRemoveButtons() {
        var removeButtons = roomContainer.querySelectorAll('.remove-room');
        removeButtons.forEach(function (btn, index) {
            if (index === 0 && removeButtons.length === 1) {
                btn.style.visibility = 'hidden';
            } else {
                btn.style.visibility = 'visible';
            }
        });
    }

    roomContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-room')) {
            e.target.closest('.room-row').remove();
            updateRoomNumbers();
            updateRemoveButtons();
            updateKidAgesColumn();
        } else if (e.target.classList.contains('control-btn')) {
            handleGuestControls(e);
        }
    });

    function handleGuestControls(e) {
        var row = e.target.closest('.room-row');
        var adultCount = row.querySelector('.adult-count');
        var kidCount = row.querySelector('.kid-count');
        var decreaseAdultBtn = row.querySelector('.decrease-adult');
        var decreaseKidBtn = row.querySelector('.decrease-kid');
        var kidAgesRow = row.querySelector('.kid-ages-row');

        if (e.target.classList.contains('increase-adult')) {
            updateGuestCount(adultCount, 1, 1);
            decreaseAdultBtn.disabled = false;
        } else if (e.target.classList.contains('decrease-adult')) {
            var newCount = updateGuestCount(adultCount, -1, 1);
            if (newCount === 1) {
                decreaseAdultBtn.disabled = true;
            }
        } else if (e.target.classList.contains('increase-kid')) {
            var newCount = updateGuestCount(kidCount, 1, 0);
            if (newCount === 1) {
                decreaseKidBtn.disabled = false;
            }
            kidAgesRow.appendChild(createKidAgeSelect());
            updateKidAgesColumn();
            updateAllRoomRows();
        } else if (e.target.classList.contains('decrease-kid')) {
            var newCount = updateGuestCount(kidCount, -1, 0);
            if (newCount === 0) {
                decreaseKidBtn.disabled = true;
                kidAgesRow.innerHTML = '';
            } else {
                kidAgesRow.removeChild(kidAgesRow.lastChild);
            }
            updateKidAgesColumn();
            updateAllRoomRows();
        }
    }

    // Function to update all room rows
    function updateAllRoomRows() {
        var hasKids = Array.from(roomContainer.querySelectorAll('.room-row')).some(
            (row) => parseInt(row.querySelector('.kid-count').textContent) > 0
        );

        $('.room-header').toggleClass('show-age-column', hasKids);
        $('.room-row').toggleClass('show-age-column', hasKids);
        modal.classList.toggle('show-age-column', hasKids);
    }

    function updateGuestCount(countElement, increment, minValue) {
        var count = parseInt(countElement.textContent);
        count = Math.max(minValue, count + increment);
        countElement.textContent = count;
        return count;
    }

    function createKidAgeSelect() {
        var select = document.createElement('select');
        select.className = 'kid-age-select';
        for (var i = 0; i <= 17; i++) {
            var option = document.createElement('option');
            option.value = i;
            option.textContent = i === 0 ? '< 1' : i;
            select.appendChild(option);
        }
        return select;
    }

    function updateKidAgesColumn() {
        var hasKids = Array.from(roomContainer.querySelectorAll('.room-row')).some(
            (row) => parseInt(row.querySelector('.kid-count').textContent) > 0
        );

        // Toggle class on header AND all rows
        $('.room-header').toggleClass('show-age-column', hasKids);
        $('.room-row').toggleClass('show-age-column', hasKids);
        modal.classList.toggle('show-age-column', hasKids);
    }

    function updateRoomNumbers() {
        roomContainer.querySelectorAll('.room-row').forEach((row, index) => {
            row.querySelector('.room-label').textContent = `Room ${index + 1}`;
            row.dataset.room = index + 1;
        });
    }

    function updateRoomGuestCount() {
        var totalRooms = roomContainer.querySelectorAll('.room-row').length;
        var adults = [];
        var children = [];
        var childAges = [];

        roomContainer.querySelectorAll('.room-row').forEach(function (row, roomIndex) {
            adults.push(parseInt(row.querySelector('.adult-count').textContent));
            var kidCount = parseInt(row.querySelector('.kid-count').textContent);
            children.push(kidCount);

            var ages = Array.from(row.querySelectorAll('.kid-age-select')).map(function (select) {
                return parseInt(select.value);
            });
            childAges.push(ages);
        });

        $('#room-guest-btn').text(totalRooms + ' Room' + (totalRooms > 1 ? 's' : '') + ', ' +
            (adults.reduce((a, b) => a + b, 0) + children.reduce((a, b) => a + b, 0)) + ' Guest' +
            (adults.reduce((a, b) => a + b, 0) + children.reduce((a, b) => a + b, 0) > 1 ? 's' : ''));

        // Update hidden inputs
        $('#child-ages-container').empty();

        adults.forEach(function (count, index) {
            $('<input>').attr({
                type: 'hidden',
                name: 'adults[]',
                value: count
            }).appendTo('#child-ages-container');
        });

        children.forEach(function (count, index) {
            $('<input>').attr({
                type: 'hidden',
                name: 'children[]',
                value: count
            }).appendTo('#child-ages-container');
        });

        childAges.forEach(function (ages, roomIndex) {
            $('<input>').attr({
                type: 'hidden',
                name: 'child_ages[]',
                value: JSON.stringify(ages)
            }).appendTo('#child-ages-container');
        });
    }

    // Convert IP address to long format
    function ip2long(ip) {
        var components = ip.split('.');
        return components.reduce((acc, octet, index) => {
            return acc + parseInt(octet, 10) * Math.pow(256, (3 - index));
        }, 0) >>> 0;
    }

    // Get client IP
    function getClientIP() {
        return fetch('https://api.ipify.org?format=json')
            .then(response => response.json())
            .then(data => ip2long(data.ip))
            .catch(error => {
                console.error('Error fetching IP:', error);
                return 'UNKNOWN';
            });
    }

    // Fetch blocked rooms and dates
    async function fetchBlockedRooms() {
        try {
            const response = await $.ajax({
                url: akka_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_block_settings',
                    nonce: akka_ajax.nonce
                }
            });
            return response;
        } catch (error) {
            console.error('Error fetching block settings:', error);
            return { success: false, data: error };
        }
    }

    // Form submission
    $('#akka-booking-form').on('submit', async function (e) {
        e.preventDefault();

        // Clear previous data from local storage
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (key.startsWith('room_request_') || 
                key.startsWith('room_response_') || 
                key === 'blockedRooms' || 
                key === 'cartData') { // Add this line to check for cartData
                localStorage.removeItem(key);
            }
        }

        var formData = new FormData(this);
        var formObject = {};
        formData.forEach((value, key) => formObject[key] = value);

        var checkInDate = formObject['date-range'].split(' to ')[0];
        var checkOutDate = formObject['date-range'].split(' to ')[1];
        var companyId = formObject.company_id;
        var clientIP = await getClientIP();

        var blockedRoomsResponse = await fetchBlockedRooms(companyId, formObject['date-range']);
        if (blockedRoomsResponse.success) {
            localStorage.setItem('blockedRooms', JSON.stringify(blockedRoomsResponse.data));
            log('Blocked Rooms Data:', blockedRoomsResponse.data);
        } else {
            console.error('Error fetching blocked rooms:', blockedRoomsResponse.data);
        }

        // Construct the request body for each room
        $('.room-row').each(function (index) {
            var adults = parseInt($(this).find('.adult-count').text());
            var children = parseInt($(this).find('.kid-count').text());
            var childAges = $(this).find('.kid-age-select').map(function () {
                return parseInt($(this).val());
            }).get();

            var roomRequest = {
                "Adult": adults,
                "CheckInDate": checkInDate,
                "CheckOutDate": checkOutDate,
                "ChildAges": childAges,
                "Children": children,
                "CompanyId": companyId,
                "Ip": clientIP
            };

            // Log the request body to the console for debugging
            log('Request Body:', roomRequest);

            // Show loading effect
            $('#booking-loader').show();

            // Send the request to the backend
            fetch(akka_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-WP-Nonce': akka_ajax.nonce
                },
                body: new URLSearchParams({
                    action: 'akka_process_booking',
                    room_request: JSON.stringify(roomRequest),
                    nonce: akka_ajax.nonce
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                log('API Response:', data);

                if (data.success && data.data) {
                    log('Booking processed successfully:', data.data);

                    // Store room data in localStorage
                    localStorage.setItem(`room_request_${index + 1}`, JSON.stringify(roomRequest));
                    localStorage.setItem(`room_response_${index + 1}`, JSON.stringify(data.data));

                    if (index === $('.room-row').length - 1) {
                        // Redirect to results page after the last request
                        window.location.href = akka_ajax.results_page;
                    }
                } else {
                    console.error('Error processing booking:', data);
                    alert('An error occurred while processing your booking. Please try again.');
                    $('#booking-loader').hide();
                    return;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred while submitting your booking. Please try again.');
                $('#booking-loader').hide();
                return;
            });
        });
    });
});
