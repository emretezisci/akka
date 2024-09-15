jQuery(document).ready(function($) {
    var modal = $('#akka-pro-edit-claim-modal');
    var span = $('.akka-pro-close');
    var claimId;

    $('.edit-claim').on('click', function () {
        claimId = $(this).data('id'); // Ensure claimId is stored globally

        if (!claimId) {
            alert('Error: Missing claim ID. Cannot edit claim.');
            return;
        }

        var row = $(this).closest('tr');
        $('#voucher-no').val(row.find('td:eq(1)').text());
        $('#operator').val(row.find('td:eq(2)').data('id'));
        $('#guest-name').val(row.find('td:eq(3)').text());
        $('#guest-surname').val(row.find('td:eq(4)').text());
        $('#date-range').val(row.find('td:eq(7)').text() + ' to ' + row.find('td:eq(8)').text());

        // Store the selected room ID
        var selectedRoomId = row.find('td:eq(6)').data('id');
        var selectedHotelId = row.find('td:eq(5)').data('id'); // Get hotel ID

        $('#adult').val(row.find('td:eq(9)').text());
        $('#child').val(row.find('td:eq(10)').text()).trigger('change');
        $('#notes').val(row.find('td:eq(12)').text());

        // Get the raw text
        var rawChildAgesText = row.find('td:eq(11)').text().trim();

        // Convert comma-separated string to JSON array string
        var childAgesString = "[" + rawChildAgesText + "]";

        // Sanitize and parse child ages safely
        var childAges = [];
        if (childAgesString && childAgesString !== 'N/A') {
            try {
                childAges = JSON.parse(childAgesString);
            } catch (e) {
                console.error("Failed to parse child ages JSON:", e);
                alert('Error parsing child ages. Please contact support.');
                return;
            }
        }

        // Populate the child age fields
        var childAgesContainer = $('#child-ages-container');
        childAgesContainer.empty();

        // Create the child age inputs dynamically
        for (var i = 0; i < childAges.length; i++) {
            var select = $('<select>', {
                name: 'child_age_' + i,
                id: 'child_age_' + i,
                class: 'child-age-select',
                required: true
            });

            // Add options to the select
            for (var age = 0; age <= 17; age++) { // Assuming 17 is the max age
                select.append($('<option>', {
                    value: age,
                    text: age
                }));
            }

            // Set the selected value
            select.val(childAges[i]);

            var label = $('<label>', {
                for: 'child_age_' + i,
                text: 'Child ' + (i + 1) + ' Age'
            });

            var wrapper = $('<div>', {
                class: 'child-age-wrapper'
            }).append(label).append(select);

            childAgesContainer.append(wrapper);
        }

        // Show the child ages group if there are child ages
        if (childAges.length > 0) {
            $('#child-ages-group').show();
        }

        // Set hotel value and trigger change event
        $('#hotel').val(selectedHotelId).trigger('change'); // Trigger change here

        // Handle room population after AJAX call
        var roomSelect = $('#room');
        $('#hotel').on('change', function () {
            var hotelId = $(this).val();
            if (hotelId) {
                $.ajax({
                    url: akkaPro.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_rooms',
                        hotel_id: hotelId,
                        nonce: akkaPro.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            roomSelect.empty().append('<option value="">Select Room</option>');

                            $.each(response.data.rooms, function (index, room) {
                                roomSelect.append('<option value="' + room.id + '">' + room.name + '</option>');
                            });

                            // Use a short delay to allow the DOM to update fully
                            setTimeout(function() {
                                $('#room').val(selectedRoomId);
                                roomSelect.prop('disabled', false); // Enable the select after setting the value
                            }, 100); // Adjust the delay (in milliseconds) if needed

                        } else {
                            // ... (error handling)
                        }
                    },
                    error: function () {
                        // ... (error handling)
                    }
                });
            } else {
                // ... (handle case where no hotel is selected)
            }
        });

        // Show the modal
        $('#akka-pro-edit-claim-modal').show();
    });

    span.on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    // Handle form submission
    $('#claim-form').off('submit').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        if (!claimId) {
            alert('Error: Claim ID is missing.');
            return;
        }

        // Explicitly include ALL relevant fields in formData
        var formData = {
            'edit_claim_id': claimId,
            'action': 'save_edited_claim',
            'nonce': akkaPro.nonce,
            'voucher-no': $('#voucher-no').val(),
            'operator': $('#operator').val(),
            'guest-name': $('#guest-name').val(),
            'guest-surname': $('#guest-surname').val(),
            'date-range': $('#date-range').val(),
            'hotel': $('#hotel').val(),
            'room_id': $('#room').val(),
            'adult': $('#adult').val(),
            'child': $('#child').val(),
            'notes': $('#notes').val()
        };

        // Collect child ages
        var childAges = [];
        $('#child-ages-container .child-age-select').each(function () {
            childAges.push($(this).val());
        });
        formData['child_ages'] = JSON.stringify(childAges);

        console.log('Form data being sent:', formData);

        $.post(akkaPro.ajaxUrl, formData, function(response) {
            if (response.success) {
                createNotification('Success!', 'Claim updated successfully!', 'success');
                
                // Close the modal
                modal.hide();

                // Update the table row with the new data
                var row = $('#akka-pro-claims-table').find(`tr td:first-child:contains(${claimId})`).closest('tr');
                row.find('td:eq(1)').text(formData['voucher-no']);
                row.find('td:eq(2)').text($('#operator option:selected').text()).data('id', formData['operator']);
                row.find('td:eq(3)').text(formData['guest-name']);
                row.find('td:eq(4)').text(formData['guest-surname']);
                row.find('td:eq(5)').text($('#hotel option:selected').text()).data('id', formData['hotel']);
                row.find('td:eq(6)').text($('#room option:selected').text()).data('id', formData['room_id']);
                row.find('td:eq(7)').text(formData['date-range'].split(' to ')[0]);
                row.find('td:eq(8)').text(formData['date-range'].split(' to ')[1]);
                row.find('td:eq(9)').text(formData['adult']);
                row.find('td:eq(10)').text(formData['child']);
                row.find('td:eq(11)').text(formData['child_ages']); 
                row.find('td:eq(12)').text(formData['notes']); 
                
            } else {
                console.log('Error: ' + response.data.message, 'error'); // Show error notification
            }
        });
    });

    // Update child ages when the number of children changes
    $('#child').on('change', function() {
        var childCount = parseInt($(this).val());
        var childAgesContainer = $('#child-ages-container');
        childAgesContainer.empty();  // Clear existing inputs

        for (var i = 0; i < childCount; i++) {
            var ageInput = $('<select>', {
                class: 'child-age-select',
                name: 'child_ages[]',
                required: true,
                placeholder: 'Age of child ' + (i + 1)
            });

            for (var age = 0; age <= 11; age++) {
                ageInput.append($('<option>', {
                    value: age,
                    text: age
                }));
            }

            childAgesContainer.append(ageInput);
        }
    });

    // Trigger the change event to populate the child age fields if there are already children selected
    $('#child').trigger('change');
    
});