import { isOneHourAhead } from './pc-time-utils.js'

jQuery(document).ready(function($) {

    // Usage example: Call the function with the ID of your form and the field name
    createHiddenField('gform_1', 'input_hidden_date');
    createHiddenField('gform_1', 'input_hidden_time');

    const personalized_weekly_hours = get_personalized_data_set({"user_time_zone":getUserTimeZone()},'get_personalized_weekly_hours_data_set');
    const personalized_booked_slots = get_personalized_data_set({"user_time_zone":getUserTimeZone()},'get_personalized_reserved_data_set');

    console.log(personalized_weekly_hours);
    console.log(personalized_booked_slots);


    var startDate = new Date();
    var endDate = new Date();
    var allowedCalendarDays = parseInt(pc_time_slot.slot_essential_data['calendar_days']);

    // Get URL parameters from the current page URL
    let urlParams = getCurrentUrlParams();

    if ( pc_time_slot.slot_essential_data['excluded_days'] != '' ) {
        var disabledDays = getExcludedDaysIndex(pc_time_slot.slot_essential_data['excluded_days']) ;
    }

    endDate.setDate( startDate.getDate() + allowedCalendarDays - 1);

    // console.info(startDate);
    // console.info(allowedCalendarDays);
    // console.info(endDate);

    $("#pc-slot-date-picker").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: startDate,
        maxDate: endDate,
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        beforeShowDay: function(date) {
            var day = date.getDay();
            if ( typeof disabledDays == 'undefined' ) return [true,''];
            return disabledDays.includes(day) ? [false,''] : [true,''];
        },
        onSelect: function(dateText) {
            // fetchAvailableSlots(dateText);
            let selected_day = get_day_from_date(dateText);
            displayAvailableSlots(dateText,selected_day);
            setInputValue('input_hidden_date', dateText);
        }
    });

    // Set current date and trigger the select event
    $("#pc-slot-date-picker").datepicker("setDate", startDate);
    $("#pc-slot-date-picker").datepicker("option", "onSelect")( $.datepicker.formatDate("yy-mm-dd", startDate) );

   function displayAvailableSlots(dateText, dayIndex,) {

        var slotsContainer = $("#pc-time-slots");
        var loader = $("#pc-time-slots-loader");

        var bookedSlotsUtc = pc_time_slot.slot_essential_data['booked_slots'];
        var bookedSlotsIndia = pc_time_slot['slot_essential_data']['booked_slots_gmt_5_30'];

        if ( urlParams.zone == 'ind' ) {
            var bookedSlots = bookedSlotsIndia;
        } else{
            var bookedSlots = bookedSlotsUtc;
        }

        var meetingDuration = pc_time_slot.slot_essential_data['slot_duration'];
        var dateText = dateText;

        // Show the loader and hide the slots container
        // slotsContainer.append(loader);
        // loader.show();
        // slotsContainer.hide();

         if (slotsContainer.length) {

            if ( !loader.length ) {
                loader = $('<div id="pc-time-slots-loader">Loading...</div>');
            }
            // Append the loader to the slots container
            slotsContainer.append(loader);

            // Show the loader and hide the slots container's content
            slotsContainer.children().hide(); // Hide all children inside the container
            loader.show(); // Show the loader
        } else {
            console.error("Slots container not found.");
        }

        setTimeout(function() {
            // Clear any existing slots
            slotsContainer.empty();

            var $availbleHours = pc_time_slot.slot_essential_data['working_hours'];



            // const fromTimeZone = 'Asia/Kolkata'; // Indian Standard Time
            // const toTimeZone = 'America/New_York'; // Example: US Eastern Time

            const timeDifference = -9.5; // Example: convert from IST (UTC+5:30) to US Eastern Time (UTC-4:00)


            // const $availbleHours = convertTimeZone( pc_time_slot.slot_essential_data['working_hours'], timeDifference);
            // const bookedSlots = convertTimeZone( pc_time_slot.slot_essential_data['booked_slots'], timeDifference);

            // Define time ranges based on the parameter
            var slots = [];
            slots = $availbleHours[dayIndex];


            if ( typeof slots != 'undefined' ) {
                if ( slots.length ) {
                    // Append slots to the container
                    $.each(slots, function(index, slot) {
                        slotsContainer.append('<div class="time-slot"><input onClick="" type="radio" name="time-slot" id="slot-' + index + '"><label for="slot-' + index + '" class="slot-button">' + slot + '</label></div>');

                        if ( isOneHourAhead(slot,dateText) ) {
                            if ( isTimeReserved(bookedSlots, dateText,slot) ){
                                $('#slot-' + index).attr('disabled', true);
                                $('#slot-' + index).parent().addClass('disabled');
                                $('#slot-' + index).parent().css('color', 'red');
                            }
                        } else{
                            $('#slot-' + index).attr('disabled', true);
                            $('#slot-' + index).parent().addClass('disabled');
                            $('#slot-' + index).parent().css('color', 'green');

                        }
                        
                    });
                } else{
                    // slotsContainer.append('<div style="color:red" class="no-slot-found"><p>No slots found!</p></div>');
                    generateDefaultTimeSlots(slotsContainer);
                }
            } else{
                // slotsContainer.append('<div style="color:red" class="no-slot-found"><p>No slots found!</p></div>');
                generateDefaultTimeSlots(slotsContainer);
            } 

            // Hide the loader and show the slots container
            loader.hide();
            slotsContainer.show();

            // Attach click event listener to dynamically generated radio buttons
            slotsContainer.on('click', '.time-slot input[type="radio"]', function() {
                var selectedValue = $(this).siblings('label').text();
                // Usage example
                // setInputValue('input_1_10', selectedValue);

                setInputValue('input_hidden_time', selectedValue);
            });

        }, 200); // Delay of 0.2 seconds (200 milliseconds)
    }

    function isTimeReserved(schedule, date, time) {

        // Check if the date exists in the schedule
        if (schedule.hasOwnProperty(date)) {
            // Get the array of times for the given date
            var times = schedule[date];
            // Check if the provided time exists in the array
            return times.includes(time);
        }
        // If the date does not exist in the schedule, return false
        return false;
    }


    function get_day_from_date(dateString) {
        // Create a date object from the date string "2024-06-15"
        // var dateString = "2024-06-15";
        var dateObject = new Date(dateString);

        // Get the day of the week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        var dayOfWeek = dateObject.getDay();

        // Convert dayOfWeek to the actual day name
        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return dayOfWeek;
        // var dayName = days[dayOfWeek];
    }

    function setInputValue(fieldId, value) {
        var inputField = document.getElementById(fieldId);
        if (inputField) {
            inputField.value = value;
        }
    }

    function createHiddenField(formId, fieldName) {
        var form = document.getElementById(formId);
        if (!form) {
            console.error('Form with ID ' + formId + ' not found.');
            return;
        }

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = fieldName;
        input.id   = fieldName;
        form.appendChild(input);
    }

    function getExcludedDaysIndex(disabledDays){
        // Map day names to day indices
        let dayMap = {
            "sun": 0, 
            "mon": 1, 
            "tue": 2, 
            "wed": 3, 
            "thu": 4, 
            "fri": 5, 
            "sat": 6
        };

        // Convert day names to day indices
        return  disabledDays.map( day => dayMap[day] );
    }

    function convertTimeZone(availableHours, timeDifference) {
        const convertTime = (dateStr, timeStr) => {
            if (!timeStr) return null;

            const [hour, minutePart] = timeStr.split(':');
            const [minute, period] = minutePart.split(' ');
            let hours = parseInt(hour, 10);
            if (period.toLowerCase() === 'pm' && hours !== 12) {
                hours += 12;
            } else if (period.toLowerCase() === 'am' && hours === 12) {
                hours = 0;
            }

            const date = new Date(`${dateStr}T${String(hours).padStart(2, '0')}:${minute.padStart(2, '0')}:00`);
            date.setHours(date.getHours() + Math.floor(timeDifference)); // Use Math.floor to get the integer part
            date.setMinutes(date.getMinutes() + (timeDifference - Math.floor(timeDifference)) * 60); // Calculate the minutes part

            const formattedTime = date.toLocaleString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            return {
                date: date.toISOString().split('T')[0],
                time: formattedTime
            };
        };

        const convertDayToDate = (day) => {
            const today = new Date();
            const currentDay = today.getDay();
            const difference = (day - currentDay + 7) % 7;
            const date = new Date(today);
            date.setDate(today.getDate() + difference);
            return date.toISOString().split('T')[0];
        };

        const convertedHours = {};
        for (const [key, times] of Object.entries(availableHours)) {
            let dateStr;
            if (key.match(/^\d+$/)) {
                // Day-based input (e.g., "1" for Monday)
                dateStr = convertDayToDate(parseInt(key, 10));
            } else {
                // Date-based input (e.g., "2024-06-17")
                dateStr = key;
            }

            times.forEach(timeStr => {
                const converted = convertTime(dateStr, timeStr);
                if (converted) {
                    if (!convertedHours[key]) {
                        convertedHours[key] = [];
                    }
                    convertedHours[key].push(converted.time);
                }
            });
        }

        return convertedHours;
    }

    function generateDefaultTimeSlots(container) {
        // frequency = frequency || 30; // Default frequency is 30 minutes if not provided
        // for (var i = 0; i <= 23; i++) {
        //     var hour = (i % 12 === 0) ? 12 : i % 12; // Convert 24-hour format to 12-hour format
        //     var period = (i < 12 || i === 24) ? 'am' : 'pm'; // Use 'am' for hours before noon (12 pm)
        //     for (var j = 0; j < 60; j += frequency) {
        //         var minute = (j < 10) ? '0' + j : j;
        //         var time = hour + ':' + minute + ' ' + period;
        //         var index = (i * 60 + j) / frequency; // Unique index based on time and frequency
        //         var slotElement = $('<div class="time-slot disabled"><input disabled="disabled" onClick="" type="radio" name="time-slot" id="slot-' + index + '"><label for="slot-' + index + '" class="slot-button">' + time + '</label></div>');
        //         container.append(slotElement);
        //     }
        // }

        const timeSlots = pc_time_slot['slot_essential_data']['default_standard_working_hours'] ;

        Object.keys(timeSlots).forEach((key, index) => {
            const time = timeSlots[key];
            const slotElement = $('<div class="time-slot disabled"><input disabled="disabled" onClick="" type="radio" name="time-slot" id="slot-' + index + '"><label for="slot-' + index + '" class="slot-button">' + time + '</label></div>');
            container.append(slotElement);
        });
    }

    function getCurrentUrlParams() {
        let url = window.location.href;
        let params = {};
        let queryString = url.split('?')[1];
        if (queryString) {
            queryString = queryString.split('#')[0]; // Remove hash if present
            let paramPairs = queryString.split('&');
            paramPairs.forEach(pair => {
                let keyValue = pair.split('=');
                let key = decodeURIComponent(keyValue[0]);
                let value = decodeURIComponent(keyValue[1] || '');
                params[key] = value;
            });
        }
        return params;
    }

    function getUserTimeZone() {
        // Try to get the time zone using Intl.DateTimeFormat
        if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
            try {
                return Intl.DateTimeFormat().resolvedOptions().timeZone;
            } catch (e) {
                console.error('Error getting time zone with Intl.DateTimeFormat:', e);
            }
        }

        // Fallback to calculating the UTC offset manually
        let now = new Date();
        let timeZoneOffset = now.getTimezoneOffset();
        let offsetHours = Math.floor(Math.abs(timeZoneOffset) / 60);
        let offsetMinutes = Math.abs(timeZoneOffset) % 60;
        let offsetSign = timeZoneOffset > 0 ? "-" : "+";
        let formattedOffset = offsetSign + String(offsetHours).padStart(2, '0') + ":" + String(offsetMinutes).padStart(2, '0');

        return formattedOffset;
    }


    function get_personalized_data_set(data,action) {

        // get_personalized_reserved_date_set
        // get_personalized_weekly_hours_date_set

        $.ajax({
            url: pc_time_slot.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: pc_time_slot.nonce,
                data: data // Add any data you want to send to the server
            },
            success: function(response) {
                if (response.success) {
                    // console.log(response.message);
                    // console.log(response["data_set"]);
                    return response["data_set"];
                } else {
                    console.error('Request failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    }


// console.log("User's time zone or offset:", getUserTimeZone());



});






// ------------------------------------------------------------------------------------





// function convertTimeZone(availableHours, timeDifference) {
//     const convertTime = (dateStr, timeStr) => {
//         if (!timeStr) return null;

//         // Create a Date object based on the provided date and time
//         const date = new Date(`${dateStr}T${timeStr.replace(' ', '').toUpperCase()}:00`);
        
//         // Extract the hours and minutes from the time string
//         const [hour, minutePart] = timeStr.split(':');
//         const [minute, period] = minutePart.split(' ');
//         let hours = parseInt(hour, 10);
//         if (period.toLowerCase() === 'pm' && hours !== 12) {
//             hours += 12;
//         } else if (period.toLowerCase() === 'am' && hours === 12) {
//             hours = 0;
//         }
        
//         // Set the correct hours and minutes
//         date.setHours(hours, parseInt(minute, 10), 0, 0);

//         // Adjust the date based on the time difference
//         date.setHours(date.getHours() + timeDifference);

//         // Format the new time
//         const formattedTime = date.toLocaleString('en-US', {
//             hour: '2-digit',
//             minute: '2-digit',
//             hour12: true
//         });

//         return {
//             date: date.toISOString().split('T')[0],
//             time: formattedTime
//         };
//     };

//     const convertDayToDate = (day) => {
//         const today = new Date();
//         const currentDay = today.getDay();
//         const difference = (day - currentDay + 7) % 7;
//         const date = new Date(today);
//         date.setDate(today.getDate() + difference);
//         return date.toISOString().split('T')[0];
//     };

//     const convertedHours = {};
//     for (const [key, times] of Object.entries(availableHours)) {
//         let dateStr;
//         if (key.match(/^\d+$/)) {
//             // Day-based input (e.g., "1" for Monday)
//             dateStr = convertDayToDate(parseInt(key, 10));
//         } else {
//             // Date-based input (e.g., "2024-06-17")
//             dateStr = key;
//         }

//         times.forEach(timeStr => {
//             const converted = convertTime(dateStr, timeStr);
//             if (converted) {
//                 if (!convertedHours[converted.date]) {
//                     convertedHours[converted.date] = [];
//                 }
//                 convertedHours[converted.date].push(converted.time);
//             }
//         });
//     }

//     return convertedHours;
// }

// Example usage:

// Date-based input
// const dateBasedAvailableHours = {
//     "2024-06-17": [
//         "04:45 pm",
//         "04:00 pm",
//         ""
//     ],
//     "2024-06-19": [
//         "10:00 am"
//     ],
//     "2024-06-25": [
//         "10:00 AM"
//     ],
//     "2024-06-26": [
//         "10:00 am"
//     ],
//     "2024-06-22": [
//         "10:00 am",
//         "01:15 pm"
//     ]
// };

// // Day-based input
// const dayBasedAvailableHours = {
//     "1": [
//         "10:00 am",
//         "10:30 am",
//         "11:15 am",
//         "11:45 am",
//         "12:15 pm",
//         "12:45 pm",
//         "02:00 pm",
//         "02:30 pm",
//         "03:00 pm",
//         "03:30 pm",
//         "04:15 pm",
//         "04:45 pm",
//         "05:15 pm",
//         "05:45 pm"
//     ],
//     "2": [
//         "10:00 am",
//         "10:30 am",
//         "11:00 am",
//         "11:30 am"
//     ],
//     "3": [
//         "10:00 am",
//         "10:30 am",
//         "11:00 am",
//         "11:30 am",
//         "12:00 pm",
//         "12:30 pm",
//         "01:00 pm",
//         "01:30 pm",
//         "02:00 pm",
//         "02:30 pm"
//     ],
//     "5": [],
//     "6": [
//         "10:00 am",
//         "10:30 am",
//         "11:15 am",
//         "11:45 am",
//         "12:15 pm",
//         "12:45 pm"
//     ]
// };

// const timeDifference = -9.5; // Example: convert from IST (UTC+5:30) to US Eastern Time (UTC-4:00)

// // Convert date-based input
// const convertedDateBased = convertTimeZone(dateBasedAvailableHours, timeDifference);
// console.log("Converted Date-Based Input:", convertedDateBased);

// // Convert day-based input
// const convertedDayBased = convertTimeZone(dayBasedAvailableHours, timeDifference);
// console.log("Converted Day-Based Input:", convertedDayBased);
