jQuery(document).ready(function ($) {
    console.log('Script started');

    let hotelDetails = {};
    let roomDetails = {};
    let serviceDetails = {};
    let cartData = JSON.parse(localStorage.getItem('cartData')) || { rooms: [], services: [] };
    let lastCreatedProductId = 0; // Keep track of the last created product ID

    function getPostDetails(postType, searchKey, searchValue, requestedFields, batch = false, batchIds = []) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: akka_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_post_details',
                    post_type: postType,
                    search_key: searchKey,
                    search_value: searchValue,
                    requested_fields: requestedFields,
                    nonce: akka_ajax.nonce,
                    batch: batch,
                    batch_ids: batchIds
                },
                success: function (response) {
                    if (response.success) {
                        console.log(`Successfully fetched post details for ${postType}:`, response.data);
                        resolve(response.data);
                    } else {
                        console.error(`Error fetching post details for ${postType}:`, response.data);
                        reject(response.data);
                    }
                },
                error: function (error) {
                    console.error(`Error with AJAX request for ${postType}:`, error);
                    reject(error);
                }
            });
        });
    }

    function formatDateString(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            timeZone: 'UTC'
        });
    }

    function isRoomBlocked(roomId, checkInDate, checkOutDate) {
        console.log(`Checking if room ${roomId} is blocked for dates: ${checkInDate} - ${checkOutDate}`);
        
        var blockedRoomsString = localStorage.getItem('blockedRooms');
        var blockedRooms;
        
        try {
            blockedRooms = JSON.parse(blockedRoomsString);
        } catch (e) {
            console.error("Error parsing blockedRooms:", e);
            return false; // Assume not blocked if we can't parse the data
        }
    
        if (typeof blockedRooms !== 'object' || blockedRooms === null) {
            console.error("blockedRooms is not an object:", blockedRooms);
            return false; // Assume not blocked if data is not in expected format
        }
    
        console.log("Parsed blockedRooms:", JSON.stringify(blockedRooms, null, 2));
    
        var checkIn = new Date(checkInDate);
        var checkOut = new Date(checkOutDate);
        checkIn.setHours(0, 0, 0, 0);
        checkOut.setHours(0, 0, 0, 0);
    
        for (var key in blockedRooms) {
            var blockedRoom = blockedRooms[key];
            
            if (blockedRoom.acf_room_id === roomId) {
                var blockedStart = new Date(blockedRoom.check_in_date);
                var blockedEnd = new Date(blockedRoom.check_out_date);
                blockedStart.setHours(0, 0, 0, 0);
                blockedEnd.setHours(0, 0, 0, 0);
    
                console.log(`Comparing with blocked period: ${blockedStart.toISOString()} - ${blockedEnd.toISOString()}`);
    
                // Check for any overlap
                if (checkIn < blockedEnd && checkOut > blockedStart) {
                    console.log(`Room ${roomId} IS BLOCKED`);
                    return true;
                }
            }
        }
    
        console.log(`Room ${roomId} is NOT blocked`);
        return false;
    }

    function getAvailableRooms(roomResponses, checkInDate, checkOutDate) {
        var availableRooms = [];
        roomResponses.forEach(response => {
            var rooms = response.Rooms || [];
            rooms.forEach(room => {
                console.log(`Checking availability for room ${room.Room}`);
                if (!isRoomBlocked(room.Room, checkInDate, checkOutDate)) {
                    console.log(`Room ${room.Room} is available, adding to list.`);
                    availableRooms.push(room);
                } else {
                    console.log(`Room ${room.Room} is blocked, not adding to list.`);
                }
            });
        });
        console.log("Available Rooms:", availableRooms);
        return availableRooms;
    }

    function updateDOMElements(hotelDetails, roomDetails, checkInDate, checkOutDate, totalRooms, totalAdults, totalKids, nights) {
        hotelDetails = hotelDetails || {};

        $('#stay-details').html(`
            <div class="details">
                <span class="detail-item"><i class="fas fa-hotel"></i> ${hotelDetails.title || 'Hotel Name Not Available'}</span>
                <span class="separator">|</span>
                <span class="detail-item"><i class="fas fa-calendar-alt"></i> ${formatDateString(checkInDate)} – ${formatDateString(checkOutDate)} (${nights} nights)</span>
                <span class="separator">|</span>
                <span class="detail-item"><i class="fas fa-user-friends"></i> ${totalRooms} room${totalRooms > 1 ? 's' : ''} for ${totalAdults} adult${totalAdults > 1 ? 's' : ''}${totalKids > 0 ? ` and ${totalKids} kid${totalKids > 1 ? 's' : ''}` : ''}</span>
            </div>
        `);

        $('#results-hotel-details').html(`
            <div class="hotel-card">
                <img src="${hotelDetails.featured_image || 'https://go.akkahotels.com/wp-content/uploads/2024/05/2799_2.jpg'}" alt="${hotelDetails.title || 'Hotel Image'}" style="width:100%; border-radius:10px; margin-bottom:10px;">
                <h2>${hotelDetails.title || 'Hotel Name Not Available'}</h2>
                <p>${hotelDetails.acf_hotel_address || 'Address Not Available'}</p>
                <a href="#" id="hotel-details-link">Hotel details ></a>
            </div>
        `);

        $('#results-summary-card').html(`
            <div class="summary-card">
                <h2>Reservation Summary</h2>
                <div id="room-summary"></div>
                <div id="service-summary"></div>
                <div id="total-summary"></div>
            </div>
        `);

        $('#results-steps').after('<div id="rooms-info"></div>');
    }

    function handleError(error, context) {
        console.error(`Error in ${context}:`, error);
        $('#results').prepend(`<div class="error-message">An error occurred while loading ${context}. Please try again later or contact customer support.</div>`);
    }

    function loadRoomCards(roomIndex) {
        var roomResponseKey = `room_response_${roomIndex}`;
        var roomResponse = JSON.parse(localStorage.getItem(roomResponseKey)) || {};
    
        var availableRooms = getAvailableRooms([roomResponse[0]], checkInDate, checkOutDate);
        var totalRoomsForStep = roomResponse[0]?.Rooms?.length || 0;
        var blockedRoomsForStep = totalRoomsForStep - availableRooms.length;
    
        var roomIds = availableRooms.map(room => room.Room);
    
        getPostDetails('room', 'acf_room_id', '', ['title', 'featured_image', 'acf_room_id', 'content'], true, roomIds)
            .then(fetchedRoomDetails => {
                roomDetails = fetchedRoomDetails;
                console.log('Room details stored:', roomDetails);
                var roomCardsHtml = '';
    
                availableRooms.forEach(room => {
                    var roomDetail = roomDetails[Object.keys(roomDetails).find(key => roomDetails[key].acf_room_id === room.Room)];
                    roomDetail.TotalPrice = room.TotalPrice;
    
                    var roomTitle = roomDetail ? roomDetail.title : 'Room Name Not Available';
                    var roomImage = roomDetail && roomDetail.featured_image ? roomDetail.featured_image : 'https://go.akkahotels.com/wp-content/uploads/2024/05/2799_2.jpg';
    
                    roomCardsHtml += `
                        <div class="room-card" data-room-id="${room.Room}">
                            <img src="${roomImage}" alt="${roomTitle}" style="width:100%; border-radius:10px; margin-bottom:10px;">
                            <h3>${roomTitle}</h3>
                            <a href="#" class="room-details-link" data-room-id="${room.Room}">Room details ></a>
                            <button class="select-room-button" data-room-id="${room.Room}" data-price="${room.TotalPrice}" data-step="${roomIndex}">Select Room - €${room.TotalPrice || 'N/A'}</button>
                        </div>
                    `;
                });
    
                leftColumn.find('#results-room-cards').html(roomCardsHtml);
    
                $('#rooms-info').html(`
                    ${availableRooms.length} room${availableRooms.length !== 1 ? 's' : ''} found. 
                    ${blockedRoomsForStep} room${blockedRoomsForStep !== 1 ? 's are' : ' is'} not available at these dates. 
                    We're showing the average price per night.
                `);
            })
            .catch(error => handleError(error, 'room details'));
    }

    function openModal(imageUrl, title, description, showButton = false, roomId = null, price = null, step = null) {
        console.log('Opening modal with:', { imageUrl, title, description, showButton });

        $('#modal-image').attr('src', imageUrl || 'https://go.akkahotels.com/wp-content/uploads/2024/05/2799_2.jpg');
        $('#modal-title').text(title || 'Title Not Available');
        $('#modal-description').html(description || 'Description Not Available');

        if (showButton) {
            $('#modal-select-button').data('room-id', roomId).data('price', price).data('step', step).show();
        } else {
            $('#modal-select-button').hide();
        }

        $('#details-modal').css('display', 'block');
        console.log('Modal should now be visible');

        console.log('Modal elements:', {
            image: $('#modal-image').attr('src'),
            title: $('#modal-title').text(),
            description: $('#modal-description').html()
        });
    }

    function closeModal() {
        $('#details-modal').css('display', 'none');
        console.log('Modal closed');
    }

    function loadServices() {
        getPostDetails('service', '', '', ['title', 'featured_image', 'acf_price_fixed', 'acf_price_adult', 'acf_price_child_7-12', 'acf_price_child_0-6', 'acf_price_child_0-2'])
            .then(fetchedServiceDetails => {
                serviceDetails = fetchedServiceDetails;
                console.log('Service details stored:', serviceDetails);
                var serviceCardsHtml = '';

                Object.keys(serviceDetails).forEach(key => {
                    var service = serviceDetails[key];

                    var servicePricingHtml = '';
                    if (service.acf_price_fixed) {
                        servicePricingHtml += `
                            <div class="service-pricing">
                                <p>Fixed Price: €${service.acf_price_fixed}</p>
                                <div>
                                    <button class="decrease-service-button" data-service-id="${key}" data-price="${service.acf_price_fixed}" data-type="fixed">-</button>
                                    <span id="service-count-${key}" class="service-count" data-count="0">0</span>
                                    <button class="increase-service-button" data-service-id="${key}" data-price="${service.acf_price_fixed}" data-type="fixed">+</button>
                                </div>
                            </div>`;
                    } else {
                        if (service.acf_price_adult) {
                            servicePricingHtml += `
                                <div class="service-pricing">
                                    <p>Price per Adult: €${service.acf_price_adult}</p>
                                    <div>
                                        <button class="decrease-service-button" data-service-id="${key}" data-price="${service.acf_price_adult}" data-type="adult">-</button>
                                        <span id="service-count-${key}-adult" class="service-count" data-count="0">0</span>
                                        <button class="increase-service-button" data-service-id="${key}" data-price="${service.acf_price_adult}" data-type="adult">+</button>
                                    </div>
                                </div>`;
                        }
                        if (service['acf_price_child_7-12']) {
                            servicePricingHtml += `
                                <div class="service-pricing">
                                    <p>Price per Child (7-12): €${service['acf_price_child_7-12']}</p>
                                    <div>
                                        <button class="decrease-service-button" data-service-id="${key}" data-price="${service['acf_price_child_7-12']}" data-type="child-7-12">-</button>
                                        <span id="service-count-${key}-child-7-12" class="service-count" data-count="0">0</span>
                                        <button class="increase-service-button" data-service-id="${key}" data-price="${service['acf_price_child_7-12']}" data-type="child-7-12">+</button>
                                    </div>
                                </div>`; 
                        }
                        if (service['acf_price_child_0-6']) {
                            servicePricingHtml += `
                                <div class="service-pricing">
                                    <p>Price per Child (0-6): €${service['acf_price_child_0-6']}</p>
                                    <div>
                                        <button class="decrease-service-button" data-service-id="${key}" data-price="${service['acf_price_child_0-6']}" data-type="child-0-6">-</button>
                                        <span id="service-count-${key}-child-0-6" class="service-count" data-count="0">0</span>
                                        <button class="increase-service-button" data-service-id="${key}" data-price="${service['acf_price_child_0-6']}" data-type="child-0-6">+</button>
                                    </div>
                                </div>`; 
                        }
                        if (service['acf_price_child_0-2']) {
                            servicePricingHtml += `
                                <div class="service-pricing">
                                    <p>Price per Child (0-2): €${service['acf_price_child_0-2']}</p>
                                    <button class="increase-service-button" data-service-id="${key}" data-price="${service['acf_price_child_0-2']}" data-type="child-0-2">+</button>
                                    <span id="service-count-${key}-child-0-2" class="service-count" data-count="0">0</span>
                                    <button class="decrease-service-button" data-service-id="${key}" data-price="${service['acf_price_child_0-2']}" data-type="child-0-2">-</button>
                                </div>`;
                        }
                    }

                    serviceCardsHtml += `
                        <div class="service-card">
                            <img src="${service.featured_image || 'https://go.akkahotels.com/wp-content/uploads/2024/05/2799_2.jpg'}" alt="${service.title || 'Service Image'}" style="width:100%; border-radius:10px; margin-bottom:10px;">
                            <h3>${service.title || 'Service Name Not Available'}</h3>
                            ${servicePricingHtml}
                        </div>
                    `;
                });

                console.log('Updated service cards HTML:', serviceCardsHtml);
                leftColumn.find('#results-room-cards').html(serviceCardsHtml);

                // Show "Book Now" button on services tab
                $('#book-now-button').show();
            })
            .catch(error => handleError(error, 'service details'));
    }

    function updateSummary() {
        let roomSummaryHtml = '';
        let totalRoomCharges = 0;
        cartData.rooms.forEach((room, index) => {
            totalRoomCharges += parseFloat(room.price);
            roomSummaryHtml += `
                <div class="summary-item">
                    <p>Room ${index + 1}</p>
                    <p>${room.title}</p>
                    <p>${nights} x €${(room.price / nights).toFixed(2)}</p>
                    <p><i class="fas fa-user"></i> ${room.adults} adult${room.adults > 1 ? 's' : ''}${room.children ? `, ${room.children} child${room.children > 1 ? 'ren' : ''}` : ''}</p>
                    <p class="summary-price">€${room.price}</p>
                </div>
            `;
        });
    
        let serviceSummaryHtml = '';
        let totalServiceCharges = 0;
        let consolidatedServices = {};
    
        cartData.services.forEach(service => {
            if (!consolidatedServices[service.serviceId]) {
                consolidatedServices[service.serviceId] = {
                    title: service.title,
                    items: []
                };
            }
            consolidatedServices[service.serviceId].items.push({
                count: service.count,
                price: service.price,
                type: service.type
            });
            totalServiceCharges += service.count * service.price;
        });
    
        Object.values(consolidatedServices).forEach(service => {
            serviceSummaryHtml += `
                <div class="summary-item">
                    <p>${service.title}</p>
                    ${service.items.map(item => `<p>${item.count} x €${item.price.toFixed(2)}${item.type !== 'fixed' ? ` (${item.type})` : ''}</p>`).join('')}
                    <p class="summary-price">€${service.items.reduce((total, item) => total + item.count * item.price, 0).toFixed(2)}</p>
                </div>
            `;
        });
    
        let totalStayPrice = totalRoomCharges + totalServiceCharges; 
    
        let totalSummaryHtml = `
            <div class="summary-total">
                <div class="summary-item">
                    <p>Total room charges</p>
                    <p class="summary-price">€${totalRoomCharges.toFixed(2)}</p>
                </div>
                <div class="summary-item">
                    <p>Total services</p>
                    <p class="summary-price">€${totalServiceCharges.toFixed(2)}</p>
                </div>
                <div class="summary-item total">
                    <p>Total for stay:</p>
                    <p class="summary-price" id="total-price">€${totalStayPrice.toFixed(2)}</p>
                </div>
            </div>
        `;
    
        $('#total-summary').html(totalSummaryHtml);
    
        // Update local storage (without bonus)
        localStorage.setItem('cartData', JSON.stringify(cartData));
    
        $('#room-summary').html(roomSummaryHtml);
        $('#service-summary').html(serviceSummaryHtml);
    }

    function handleRoomSelection(roomId, price, step) {
        var roomDetail = roomDetails[Object.keys(roomDetails).find(key => roomDetails[key].acf_room_id === roomId)];

        if (roomDetail) {
            let existingRoomIndex = cartData.rooms.findIndex(room => room.step === step);
            if (existingRoomIndex !== -1) {
                cartData.rooms[existingRoomIndex] = {
                    title: roomDetail.title,
                    price: roomDetail.TotalPrice,
                    roomId: roomDetail.acf_room_id,
                    adults: roomRequests[step - 1].Adult,
                    children: roomRequests[step - 1].Children,
                    step: step
                };
            } else {
                cartData.rooms.push({
                    title: roomDetail.title,
                    price: roomDetail.TotalPrice,
                    roomId: roomDetail.acf_room_id,
                    adults: roomRequests[step - 1].Adult,
                    children: roomRequests[step - 1].Children,
                    step: step
                });
            }

            updateSummary();

            if (step < totalRooms) {
                $('.step.active').removeClass('active').next('.step').addClass('active');
                loadRoomCards(step + 1);
            } else if (step === totalRooms) {
                $('.step.active').removeClass('active');
                $('.step[data-room="services"]').addClass('active');
                loadServices();
            }
        }

        closeModal();
    }

    var stayDetails = $('#your-stay #stay-details');
    var leftColumn = $('#results-left-column');
    var rightColumn = $('#results-right-column');

    var totalRooms = 0;
    var totalAdults = 0;
    var totalKids = 0;
    var checkInDate = null;
    var checkOutDate = null;
    var hotelId = null;
    var nights = 0;

    var roomRequests = [];

    for (var i = 0; i < localStorage.length; i++) {
        var key = localStorage.key(i);
        if (key.startsWith('room_request_')) {
            var roomRequest = JSON.parse(localStorage.getItem(key));
            roomRequests.push(roomRequest);
            totalRooms++;
            totalAdults += roomRequest.Adult;
            totalKids += roomRequest.Children;
            checkInDate = roomRequest.CheckInDate;
            checkOutDate = roomRequest.CheckOutDate;
            hotelId = roomRequest.CompanyId;
        }
    }

    if (checkInDate && checkOutDate) {
        var checkIn = new Date(checkInDate);
        var checkOut = new Date(checkOutDate);
        nights = (checkOut - checkIn) / (1000 * 60 * 60 * 24);
    }

    if (hotelId) {
        console.log('Starting to fetch hotel details for ID:', hotelId);
        getPostDetails('hotel', 'acf_hotel_id', hotelId, ['title', 'acf_hotel_address', 'featured_image', 'content'])
            .then(fetchedHotelDetails => {
                console.log('Successfully fetched hotel details:', fetchedHotelDetails);

                const postId = Object.keys(fetchedHotelDetails)[0];
                hotelDetails = fetchedHotelDetails[postId] || {};
                console.log('Hotel details stored:', hotelDetails);

                getPostDetails('room', 'acf_room_id', '', ['title', 'featured_image', 'acf_room_id', 'content'], true, roomRequests.map(req => req.Room))
                    .then(roomDetails => {
                        updateDOMElements(hotelDetails, roomDetails, checkInDate, checkOutDate, totalRooms, totalAdults, totalKids, nights);

                        var stepsHtml = '';
                        if (totalRooms > 1) {
                            stepsHtml += `<h2>Room 1 of ${totalRooms}</h2>`;
                        }
                        stepsHtml += '<div id="results-steps" class="steps-container">';
                        for (let i = 1; i <= totalRooms; i++) {
                            stepsHtml += `<div class="step${i === 1 ? ' active' : ''}" data-room="${i}">Room ${i}</div>`;
                        }
                        stepsHtml += '<div class="step" data-room="services">Services</div>';
                        stepsHtml += '</div>';
                        leftColumn.find('#results-steps').html(stepsHtml);

                        leftColumn.find('.step').on('click', function () {
                            var step = $(this).data('room');
                            leftColumn.find('.step').removeClass('active');
                            $(this).addClass('active');

                            if (step === 'services') {
                                loadServices();
                                $('#rooms-info').html('');
                            } else {
                                loadRoomCards(step);
                            }
                        });

                        leftColumn.find('.step[data-room="1"]').trigger('click'); 

                    })
                    .catch(error => handleError(error, 'room details'));
            })
            .catch(error => {
                handleError(error, 'hotel details');
                stayDetails.html('<p>Unable to load stay details. Please try again later.</p>');
            });
    } else {
        stayDetails.html('<p>Unable to load stay details. Please ensure you have selected a hotel and dates.</p>');
    }

    $(document).on('click', '#hotel-details-link', function (e) {
        e.preventDefault();
        console.log('Hotel details clicked. hotelDetails:', hotelDetails);
        openModal(
            hotelDetails.featured_image || 'https://go.akkahotels.com/wp-content/uploads/2024/05/2799_2.jpg',
            hotelDetails.title || 'Hotel Name Not Available',
            hotelDetails.content || 'Description Not Available'
        );
    });

    $(document).on('click', '.room-details-link', function (e) {
        e.preventDefault();
        var roomId = $(this).data('room-id');
        var roomDetail = roomDetails[Object.keys(roomDetails).find(key => roomDetails[key].acf_room_id === roomId)];

        if (roomDetail) {
            openModal(
                roomDetail.featured_image || 'https://go.akkahotels.com/wp-content/uploads/2024/05/2799_2.jpg',
                roomDetail.title || 'Room Name Not Available',
                roomDetail.content || 'Description Not Available',
                true,
                roomId,
                roomDetail.TotalPrice,
                $('.step.active').data('room')
            );
        } else {
            console.error('Room details not found for room ID:', roomId);
        }
    });

    $(document).on('click', '.close', function () {
        closeModal();
    });

    $(window).on('click', function (event) {
        if ($(event.target).is('#details-modal')) {
            closeModal();
        }
    });

    $(document).on('click', '.select-room-button, #modal-select-button', function () {
        var roomId = $(this).data('room-id');
        var price = $(this).data('price');
        var step = $(this).data('step');
        handleRoomSelection(roomId, price, step);
    });

    $(document).on('click', '.increase-service-button, .decrease-service-button', function () {
        var serviceId = $(this).data('service-id');
        var price = parseFloat($(this).data('price'));
        var type = $(this).data('type');
        var isIncrease = $(this).hasClass('increase-service-button');

        var serviceCountSpan = $(`#service-count-${serviceId}${type !== 'fixed' ? '-' + type : ''}`);
        var count = parseInt(serviceCountSpan.attr('data-count'));

        if (isIncrease) {
            count++;
        } else if (count > 0) {
            count--;
        }

        serviceCountSpan.attr('data-count', count);
        serviceCountSpan.text(count);

        var existingServiceIndex = cartData.services.findIndex(service =>
            service.serviceId === serviceId && service.type === type
        );

        if (existingServiceIndex !== -1) {
            if (count === 0) {
                cartData.services.splice(existingServiceIndex, 1);
            } else {
                cartData.services[existingServiceIndex].count = count;
            }
        } else if (count > 0) {
            cartData.services.push({
                serviceId: serviceId,
                title: serviceDetails[serviceId].title,
                price: price,
                count: count,
                type: type
            });
        }

        updateSummary();
    });

    // Function to create WooCommerce product
    function createWoocommerceProduct(productName, productPrice, productDescription) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: akka_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'create_woocommerce_product',
                    product_name: productName,
                    product_price: productPrice,
                    product_description: productDescription,
                    nonce: akka_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        resolve(response.data.product_id);
                    } else {
                        reject(new Error('Error creating product: ' + response.data));
                    }
                },
                error: function (xhr, status, error) {
                    reject(new Error('AJAX error: ' + error));
                }
            });
        });
    }

    // Function to add product to cart
    // Function to add product to cart
    function addProductToCart(productId) {
        return new Promise((resolve, reject) => {
            try {
                console.log("Attempting to add product ID:", productId, "to cart...");

                // Check if wc_add_to_cart_params is defined
                if (typeof wc_add_to_cart_params === 'undefined') {
                    throw new Error("wc_add_to_cart_params is not defined. Make sure WooCommerce is active and its scripts are loaded correctly.");
                }

                // Check if AJAX URL exists
                if (!wc_add_to_cart_params.ajax_url) {
                    throw new Error("WooCommerce AJAX URL is missing from wc_add_to_cart_params.");
                }

                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'woocommerce_ajax_add_to_cart',
                        product_id: productId,
                        quantity: 1
                    },
                    success: function (response) {
                        console.log("addProductToCart AJAX response:", response); 
                        if (response.error && response.product_url) {
                            reject(new Error('Error adding to cart: ' + response.error));
                        } else {
                            resolve(true); 
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("addProductToCart AJAX error:", error);
                        reject(new Error('AJAX error: ' + error));
                    }
                });

            } catch (error) {
                console.error("Error in addProductToCart function:", error);
                reject(error);
            }
        });
    }

    // Function to get the next available product ID
    function getNextProductId() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: akka_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_last_product_id',
                    nonce: akka_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        lastCreatedProductId = parseInt(response.data) + 1;
                        resolve(lastCreatedProductId);
                    } else {
                        reject(new Error('Error fetching last product ID: ' + response.data));
                    }
                },
                error: function (xhr, status, error) {
                    reject(new Error('AJAX error: ' + error));
                }
            });
        });
    }

    // "Book Now" button click handler
    $('#book-now-button').on('click', function () {
        const totalPrice = parseFloat($('#total-price').text().replace('$', ''));

        // Get the next available product ID
        getNextProductId()
            .then(nextProductId => {
                // Format cartData for human-readable description
                let description = 'Reservation Details:\n\n';
                description += 'Rooms:\n';
                cartData.rooms.forEach((room, index) => {
                    description += `- Room ${index + 1}: ${room.title} (€${room.price})\n`;
                    // Add other room details as needed
                });
                description += '\nServices:\n';
                cartData.services.forEach(service => {
                    description += `- ${service.title} (x${service.count}): €${(service.count * service.price).toFixed(2)}\n`;
                });

                // Create product with the incremented ID and formatted description
                return createWoocommerceProduct(
                    'Reservation - ' + nextProductId,
                    totalPrice,
                    description
                );
            })
            .then(productId => {
                console.log('Product created with ID:', productId);

                // Add product to cart immediately after product creation
                addProductToCart(productId)
                    .then(() => {
                        console.log('Product added to cart');
                        window.location.href = wc_add_to_cart_params.cart_url;
                    })
                    .catch(error => {
                        console.error('Error adding to cart:', error);
                    });
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    console.log('Script fully loaded and executed');
});