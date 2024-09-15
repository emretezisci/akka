window.onload = function () {
    jQuery(function ($) {
        // Initialize Flatpickr
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#date-range", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today"
            });
        } else {
            console.error('Flatpickr is not loaded');
        }

        // Update the hotel change event handler
        $('#hotel').on('change', function () {
            var acfHotelId = $(this).val();
            var roomSelect = $('#room');
            

            if (acfHotelId) {
                $.ajax({
                    url: akka_pro_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_rooms_by_hotel',
                        acf_hotel_id: acfHotelId,
                        nonce: akka_pro_data.nonce
                    },
                    success: function (response) {
                        console.log('Response from get_rooms_by_hotel:', response);

                        if (response.success) {
                            
                            roomSelect.empty().append('<option value="">Select Room</option>');
                            $.each(response.data.rooms, function (index, room) {
                                roomSelect.append('<option value="' + room.id + '">' + room.name + '</option>');
                            });
                            roomSelect.prop('disabled', false);
                        } else {
                            alert('Error: ' + response.data.message);
                            roomSelect.empty().append('<option value="">No rooms available</option>');
                            roomSelect.prop('disabled', true);
                        }
                    },
                    error: function () {
                        alert('An error occurred while fetching rooms.');
                        roomSelect.empty().append('<option value="">Error loading rooms</option>');
                        roomSelect.prop('disabled', true);
                    }
                });
            } else {
                roomSelect.empty().append('<option value="">Select Hotel First</option>');
                roomSelect.prop('disabled', true);
            }
        });

        // Child age validation
        function validateChildAges() {
            var numChildren = parseInt($('#child').val());
            if (numChildren > 0) {
                var allAgesSelected = true;
                $('.child-age-select').each(function () {
                    if ($(this).val() === '') {
                        allAgesSelected = false;
                        return false;
                    }
                });
                return allAgesSelected;
            }
            return true;
        }

        // Handle child age select creation and display
        $('#child').on('change', function () {
            var numChildren = parseInt($(this).val());
            var container = $('#child-ages-container');
            container.empty();

            if (numChildren > 0) {
                $('#child-ages-group').show();
                for (var i = 0; i < numChildren; i++) {
                    var select = $('<select>', {
                        name: 'child_age_' + i,
                        id: 'child_age_' + i,
                        'class': 'child-age-select',
                        required: true
                    });

                    select.append($('<option>', {
                        value: '',
                        text: 'Select Age'
                    }));

                    for (var age = 0; age <= 17; age++) {
                        select.append($('<option>', {
                            value: age,
                            text: age
                        }));
                    }

                    var label = $('<label>', {
                        for: 'child_age_' + i,
                        text: 'Child ' + (i + 1) + ' Age'
                    });

                    var wrapper = $('<div>', {
                        'class': 'child-age-wrapper'
                    }).append(label).append(select);

                    container.append(wrapper);
                }
            } else {
                $('#child-ages-group').hide();
            }
        });


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

        // Handle form submission
        $('#claim-form').on('submit', function(e) {
            e.preventDefault();

            if (!validateChildAges()) {
                alert('Please select ages for all children.');
                return;
            }

            var form = $(this)[0];
            if (form.checkValidity() === false) {
                e.stopPropagation();
                form.reportValidity();
                return;
            }

            var formData = $(this).serializeArray();
            var formObject = {};
            $(formData).each(function(index, obj) {
                formObject[obj.name] = obj.value;
            });

            // Prepare data for API
            getClientIP().then(ip => {
                var dateRange = formObject['date-range'].split(' to ');
                var apiData = {
                    Adult: parseInt(formObject.adult),
                    CheckInDate: dateRange[0],
                    CheckOutDate: dateRange[1] || dateRange[0],
                    Children: parseInt(formObject.child),
                    CompanyId: $('#hotel').val(), // Use the selected hotel as CompanyId
                    Ip: ip,
                    VoucherNo: formObject.voucher_no,
                    OperatorId: formObject.operator,
                    GuestName: formObject.guest_name,
                    GuestSurname: formObject.guest_surname,
                    Notes: formObject.notes
                };

                if (apiData.Children > 0) {
                    apiData.ChildAges = [];
                    for (var i = 0; i < apiData.Children; i++) {
                        var age = parseInt(formObject['child_age_' + i]);
                        if (!isNaN(age)) {
                            apiData.ChildAges.push(age);
                        }
                    }
                } else {
                    apiData.ChildAges = [];
                }

                console.log('Data being sent to API:', apiData);

                $.ajax({
                    url: akka_pro_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'submit_claim',
                        formData: JSON.stringify(apiData),
                        room_id: formObject.room,
                        voucher_no: formObject.voucher_no,
                        operator: formObject.operator,
                        guest_name: formObject.guest_name,
                        guest_surname: formObject.guest_surname,
                        notes: formObject.notes,
                        nonce: akka_pro_data.nonce
                    },
                    success: function (response) {
                        console.log('API Response:', response);
                        if (response.success) {
                            createNotification('Success!', 'Claim submitted successfully!', 'success');
                            $('#claim-form')[0].reset();
                            $('#child-ages-group').hide();
                        } else {
                            alert('Error submitting claim: ' + response.data);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Form submission error:', status, error);
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    });
};
