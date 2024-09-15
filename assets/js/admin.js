jQuery(document).ready(function($) {
    // Initialize the main tabs
    $('#akka-pro-tabs').tabs({
        activate: function(event, ui) {
            setActiveTab(ui.newPanel.attr('id'));
        }
    });

    // Initialize the sub-tabs within the Reservation tab
    $('#reservation-sub-tabs').tabs();

    // Initialize the sub-tabs within the Bonus tab
    $('#bonus-sub-tabs').tabs();

    // Set active tab based on stored value
    var activeTab = getActiveTab();
    if (activeTab) {
        var index = $('#' + activeTab).index() - 1;
        $('#akka-pro-tabs').tabs('option', 'active', index);
    }

    // Store the active tab in local storage
    function setActiveTab(tabId) {
        localStorage.setItem('activeTab', tabId);
    }

    // Retrieve the active tab from local storage
    function getActiveTab() {
        return localStorage.getItem('activeTab') || 'reservation-tab'; // Default to the reservation tab
    }

    // Initialize Flatpickr for date range in Block form
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#akka-block-date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    } else {
        console.error('Flatpickr is not loaded');
    }

    // Initialize Flatpickr for date range in Room-based Bonus form
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#bonus_date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    } else {
        console.error('Flatpickr is not loaded');
    }


    // Initialize Flatpickr for date range in Discount form
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#akka-discount-date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    } else {
        console.error('Flatpickr is not loaded');
    }

    // AJAX for Bonus tab room selection
    $('#akka-bonus-hotel').on('change', function () {
        var acfHotelId = $(this).find(':selected').data('acf-hotel-id');
        var roomSelect = $('#akka-bonus-room');

        if (acfHotelId) {
            $.ajax({
                url: akkaPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_rooms_by_hotel',
                    acf_hotel_id: acfHotelId,
                    nonce: akkaPro.nonce
                },
                success: function (response) {
                    if (response.success) {
                        roomSelect.empty().append('<option value="">' + akkaPro.selectRoomText + '</option>');
                        $.each(response.data.rooms, function (index, room) {
                            roomSelect.append('<option value="' + room.id + '">' + room.name + '</option>');
                        });
                        roomSelect.prop('disabled', false);
                    } else {
                        roomSelect.empty().append('<option value="">' + akkaPro.noRoomsText + '</option>');
                        roomSelect.prop('disabled', true);
                    }
                },
                error: function () {
                    roomSelect.empty().append('<option value="">' + akkaPro.errorLoadingRoomsText + '</option>');
                    roomSelect.prop('disabled', true);
                }
            });
        } else {
            roomSelect.empty().append('<option value="">' + akkaPro.selectHotelFirstText + '</option>');
            roomSelect.prop('disabled', true);
        }
    });

    // AJAX for Discount tab room selection
    $('#akka-discount-hotel').on('change', function () {
        var acfHotelId = $(this).find(':selected').data('acf-hotel-id');
        var roomSelect = $('#akka-discount-room');

        if (acfHotelId) {
            $.ajax({
                url: akkaPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_rooms_by_hotel',
                    acf_hotel_id: acfHotelId,
                    nonce: akkaPro.nonce
                },
                success: function (response) {
                    if (response.success) {
                        roomSelect.empty().append('<option value="">' + akkaPro.selectRoomText + '</option>');
                        $.each(response.data.rooms, function (index, room) {
                            roomSelect.append('<option value="' + room.id + '">' + room.name + '</option>');
                        });
                        roomSelect.prop('disabled', false);
                    } else {
                        roomSelect.empty().append('<option value="">' + akkaPro.noRoomsText + '</option>');
                        roomSelect.prop('disabled', true);
                    }
                },
                error: function () {
                    roomSelect.empty().append('<option value="">' + akkaPro.errorLoadingRoomsText + '</option>');
                    roomSelect.prop('disabled', true);
                }
            });
        } else {
            roomSelect.empty().append('<option value="">' + akkaPro.selectHotelFirstText + '</option>');
            roomSelect.prop('disabled', true);
        }
    });


    // AJAX for Block tab room selection
    $('#akka-block-hotel').on('change', function () {
        var acfHotelId = $(this).find(':selected').data('acf-hotel-id');
        var roomSelect = $('#akka-block-room');

        if (acfHotelId) {
            $.ajax({
                url: akkaPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_rooms_by_hotel',
                    acf_hotel_id: acfHotelId,
                    nonce: akkaPro.nonce
                },
                success: function (response) {
                    if (response.success) {
                        roomSelect.empty().append('<option value="">' + akkaPro.selectRoomText + '</option>');
                        $.each(response.data.rooms, function (index, room) {
                            roomSelect.append('<option value="' + room.id + '">' + room.name + '</option>');
                        });
                        roomSelect.prop('disabled', false);
                    } else {
                        roomSelect.empty().append('<option value="">' + akkaPro.noRoomsText + '</option>');
                        roomSelect.prop('disabled', true);
                    }
                },
                error: function () {
                    roomSelect.empty().append('<option value="">' + akkaPro.errorLoadingRoomsText + '</option>');
                    roomSelect.prop('disabled', true);
                }
            });
        } else {
            roomSelect.empty().append('<option value="">' + akkaPro.selectHotelFirstText + '</option>');
            roomSelect.prop('disabled', true);
        }
    });

    // Handle Market-based Bonus Settings form submission
    $('#akka-pro-market-bonus-form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = {
            action: 'save_market_bonus_settings',
            market: $('#akka-market').val(),
            bonus_rate: $('#akka-market-rate').val(),
            duration: $('#akka-market-duration').val(),
            nonce: akkaPro.nonce
        };

        $.post(akkaPro.ajaxUrl, formData, function(response) {
            if (response.success) {
                $('#market-bonus-settings-table tbody').append(response.data.row_html);
                $('#akka-pro-market-bonus-form')[0].reset();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('An error occurred while processing the request.');
        });
    });

    // Handle Delete Market Bonus Setting
    $('#market-bonus-settings-table').on('click', '.delete-market-bonus-setting', function() {
        var id = $(this).data('id');
        var row = $('#market-bonus-setting-row-' + id);

        $.post(akkaPro.ajaxUrl, {
            action: 'delete_market_bonus_setting',
            id: id,
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                row.remove();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

    // Handle Discount form submission
    $('#akka-pro-discount-form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = {
            action: 'save_discount_settings',
            akka_discount_hotel: $('#akka-discount-hotel').val(),
            akka_discount_room: $('#akka-discount-room').val(),
            akka_discount_date_range: $('#akka-discount-date-range').val(),
            akka_discount_rate: $('#akka-discount-rate').val(),
            nonce: akkaPro.nonce
        };

        $.post(akkaPro.ajaxUrl, formData, function(response) {
            if (response.success) {
                $('#discount-settings-table tbody').append(response.data.row_html);
                $('#akka-pro-discount-form')[0].reset();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('An error occurred while processing the request.');
        });
    });

    // Handle Delete Discount Setting
    $('#discount-settings-table').on('click', '.delete-discount-setting', function() {
        var id = $(this).data('id');
        var row = $('#discount-setting-row-' + id);

        $.post(akkaPro.ajaxUrl, {
            action: 'delete_discount_setting',
            id: id,
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                row.remove();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });


    // Handle Default Bonus Rate form submission
    $('#akka-pro-default-bonus-form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission
        var defaultBonusRate = $('#akka-default-bonus-rate').val();

        var formData = {
            action: 'save_default_bonus_rate',
            default_bonus_rate: $('#akka-default-bonus-rate').val(),
            nonce: akkaPro.nonce
        };

        $.post(akkaPro.ajaxUrl, formData, function(response) {
            if (response.success) {
                alert('Default bonus rate saved successfully.');
                // Update the input field with the new value
                $('#akka-default-bonus-rate').val(defaultBonusRate);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('An error occurred while processing the request.');
        });
    });


    // Handle Bonus form submission
    $('#akka-pro-bonus-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'save_bonus_settings',
            akka_bonus_hotel: $('#akka-bonus-hotel').val(),
            akka_bonus_room: $('#akka-bonus-room').val(),
            bonus_date_range: $('#bonus_date_range').val(),
            akka_bonus_rate: $('#akka-bonus-rate').val(),
            akka_bonus_duration: $('#akka-bonus-duration').val(),
            nonce: akkaPro.nonce
        };

        $.post(akkaPro.ajaxUrl, formData, function(response) {
            if (response.success) {
                $('#bonus-settings-table tbody').append(response.data.row_html);
                $('#akka-pro-bonus-form')[0].reset();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('An error occurred while processing the request.');
        });
    });

    // Handle Block form submission
    $('#akka-pro-block-form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        var dateRange = $('#akka-block-date-range').val();

        // Log date_range to the console
        console.log('Date Range Selected:', dateRange);

        var formData = {
            action: 'save_block_settings',
            hotel_id: $('#akka-block-hotel').val(),
            room_id: $('#akka-block-room').val(),
            date_range: dateRange,
            nonce: akkaPro.nonce
        };

        $.post(akkaPro.ajaxUrl, formData, function(response) {
            if (response.success) {
                $('#block-settings-table tbody').append(response.data.row_html);
                $('#akka-pro-block-form')[0].reset();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('An error occurred while processing the request.');
        });
    });

    // Handle Delete Bonus Setting
    $('#bonus-settings-table').on('click', '.delete-bonus-setting', function() {
        var id = $(this).data('id');
        var row = $('#bonus-setting-row-' + id);

        $.post(akkaPro.ajaxUrl, {
            action: 'delete_bonus_setting',
            id: id,
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                row.remove();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

    // Handle Delete Block Setting
    $('#block-settings-table').on('click', '.delete-block-setting', function() {
        var id = $(this).data('id');
        var row = $('#block-setting-row-' + id);

        $.post(akkaPro.ajaxUrl, {
            action: 'delete_block_setting',
            id: id,
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                row.remove();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

    // Initialize DataTables for Claim Submissions table
    $('#claim-tab table').DataTable({
        // Optional: Add sorting and searching features
        "order": [[0, "desc"]], // Default sorting by Claim ID in descending order
        "searching": true,

        // Optional: Customize language for pagination, search, etc.
        "language": {
            "search": "Search:",
            "paginate": {
                "previous": "Previous",
                "next": "Next"
            }
        }
    });

    // Handle Delete Claim
    $('#claim-tab').on('click', '.delete-claim', function() {
        var id = $(this).data('id');
        var row = $(this).closest('tr');

        $.post(akkaPro.ajaxUrl, {
            action: 'delete_claim',
            id: id,
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                row.remove();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

    // Handle the approve button click
    $('.approve-claim').on('click', function() {
        var claimId = $(this).data('id');
        var reason = $('#reason-' + claimId).val(); 
        var $button = $(this);
        var $denyButton = $('#deny-' + claimId);

        $.post(akkaPro.ajaxUrl, {
            action: 'approve_claim',
            id: claimId,
            reason: reason, 
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                alert('Claim approved successfully.');
                $button.addClass('claim-status-selected').prop('disabled', true);
                $denyButton.removeClass('claim-status-selected').prop('disabled', false);
                location.reload(); // Refresh the page after success
            } else {
                alert('Error approving claim: ' + response.data.message);
            }
        });
    });

    // Handle the deny button click
    $('.deny-claim').on('click', function() {
        var claimId = $(this).data('id');
        var reason = $('#reason-' + claimId).val();
        var $button = $(this);
        var $approveButton = $('#approve-' + claimId);
    
        $.post(akkaPro.ajaxUrl, {
            action: 'deny_claim',
            id: claimId,
            reason: reason,
            nonce: akkaPro.nonce
        }, function(response) {
            if (response.success) {
                alert('Claim denied successfully.');
                $button.addClass('claim-status-selected').prop('disabled', true);
                $approveButton.removeClass('claim-status-selected').prop('disabled', false);
                location.reload(); // Refresh the page after success
            } else {
                alert('Error denying claim: ' + response.data.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('An error occurred while processing the request.');
        });
    });

});
