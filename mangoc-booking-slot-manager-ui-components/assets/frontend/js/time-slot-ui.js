import { isOneHourAhead } from './pc-time-utils.js'

jQuery(document).ready(function ($) {

    if ($('#pc-slot-date-picker').length == 0) return;

    // Display Booking slot missing error validation message of Gform
    (function () {
        let validationContainer = document.querySelector('.gform_validation_errors');
        if (validationContainer) {
            let errorChild = validationContainer.querySelector('.no_booking_date');

            if (errorChild && validationContainer.children.length <= 2) {
                validationContainer.style.display = "block"; // Show if hidden
            }
        }
    })();

    (async () => {
        try {
            const timezone = getUserTimeZone();
            const personalized_weekly_hours = await get_personalized_data_set({ user_time_zone: timezone }, 'get_personalized_weekly_hours_data_set');
            const personalized_booked_slots = await get_personalized_data_set({ user_time_zone: timezone }, 'get_personalized_reserved_data_set');

            $("#pc-main-loader").remove();

            // ✅ ensure data exists before calling
            if (personalized_weekly_hours && personalized_booked_slots) {
                calendarInit(personalized_weekly_hours, personalized_booked_slots);
            } else {
                throw new Error('Data missing for calendarInit');
            }
        } catch (error) {
            console.error("Calendar failed to load:", error);
            $("#pc-main-loader").remove();
            $(".pc.slot-booking-block").before(`<p style="text-align:center;color: #c81620;">${error}</p>`);
            $(".pc.slot-booking-block").hide();
        }
    })();


    function calendarInit(weeklyHours, bookedSlots) {
        // Gravity form at contact page 
        var contactFromId = $(".contact-wrapper form").attr("id")

        // Call the function with the ID of the form and the field name
        createHiddenField(contactFromId, 'input_hidden_date');
        createHiddenField(contactFromId, 'input_hidden_time');
        createHiddenField(contactFromId, 'input_hidden_user_zone');

        // Example of specific dates to disable in 'yyyy-mm-dd' format
        var excludedDates = pc_time_slot.slot_essential_data['specified_excluded_dates'];
        if (!Array.isArray(excludedDates)) excludedDates = [];

        // if (excludedDates == "") excludedDates = "undefined";

        if (pc_time_slot.slot_essential_data['excluded_days'] != '') {
            var disabledDays = getExcludedDaysIndex(pc_time_slot.slot_essential_data['excluded_days']);
        }

        if (todayCurrentLondonHours() >= 13) {
            let today = new Date().toISOString().split('T')[0];
            if (!excludedDates.includes(today)) {
                excludedDates.push(today); // Add today's date if not already in the array
            }
        }

        // Find the next available date if the current date is disabled
        var initialDate = new Date();

        if (typeof (excludedDates) != 'undefined') {
            if (isDateDisabled(initialDate, excludedDates, disabledDays)) {
                initialDate = findNextAvailableDate(initialDate, excludedDates, disabledDays);
            }
        }

        var startDate = initialDate;
        var currentDate = new Date();

        // console.log("inital date: " + startDate);
        var endDate = new Date();
        var allowedCalendarDays = parseInt(pc_time_slot.slot_essential_data['calendar_days']);

        endDate.setDate(currentDate.getDate() + allowedCalendarDays + 1);// excluding current date

        $("#pc-slot-date-picker").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: startDate,
            maxDate: endDate,
            dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            beforeShowDay: function (date) {
                var day = date.getDay();
                var dateStr = $.datepicker.formatDate("yy-mm-dd", date);

                if (typeof excludedDates != 'undefined') {
                    if (excludedDates.includes(dateStr)) return [false, '', 'Disabled Date'];
                }

                if (typeof disabledDays == 'undefined') return [true, ''];
                return disabledDays.includes(day) ? [false, ''] : [true, ''];
            },
            onSelect: function (dateText) {
                // fetchAvailableSlots(dateText);
                let selected_day = get_day_from_date(dateText);
                displayAvailableSlots(dateText, selected_day, weeklyHours, bookedSlots);
                setInputValue('input_hidden_date', dateText);
            },
            // Fix month navigation disabled, if all dates are set to disabled in the given month
            // onChangeMonthYear: function(year, month, inst) {
            //     var nextMonthDate = new Date(year, month - 1, 1);
            //     var nextAvailableDate = findNextAvailableDate(nextMonthDate,excludedDates,disabledDays);

            //     if (nextAvailableDate.getMonth() === (month - 1) && nextAvailableDate.getFullYear() === year) {
            //         $(this).datepicker("setDate", nextAvailableDate);
            //     }
            // }
        });

        // Set current date and trigger the select event
        $("#pc-slot-date-picker").datepicker("setDate", startDate);
        $("#pc-slot-date-picker").datepicker("option", "onSelect")($.datepicker.formatDate("yy-mm-dd", startDate));
    }

    function isDateDisabled(date, excludedDates, disabledDays) {
        var dateString = $.datepicker.formatDate('yy-mm-dd', date);
        var day = date.getDay();
        return excludedDates.includes(dateString) || (typeof disabledDays !== 'undefined' && disabledDays.includes(day));
    }

    function findNextAvailableDate(startDate, excludedDates, disabledDays) {
        var nextDate = new Date(startDate);
        while (isDateDisabled(nextDate, excludedDates, disabledDays)) {
            nextDate.setDate(nextDate.getDate() + 1);
        }
        return nextDate;
    }


    function displayAvailableSlots(dateText, dayIndex, weeklyHours, bookedSlots) {
        var slotsContainer = $("#pc-time-slots");
        var loader = $("#pc-time-slots-loader");

        var bookedSlots = bookedSlots;
        var meetingDuration = pc_time_slot.slot_essential_data['slot_duration'];
        var dateText = dateText;

        // Show the loader and hide the slots container
        if (slotsContainer.length) {

            if (!loader.length) {
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

        setTimeout(function () {
            // Clear any existing slots
            slotsContainer.empty();

            // var $availbleHours = pc_time_slot.slot_essential_data['working_hours'];
            var $availbleHours = weeklyHours;

            // Define time ranges based on the parameter
            var slots = [];
            slots = $availbleHours[dayIndex];

            if (typeof slots != 'undefined') {
                if (slots.length) {
                    // Append slots to the container
                    $.each(slots, function (index, slot) {
                        // New test
                        // let formattedDate = formatDateToISO(dateText); // Convert "21-02-2025" to "2025-02-21"
                        let formattedDate = dateText;

                        // Create a valid Date object before conversion
                        let slotDateTime = new Date(formattedDate + 'T' + convertTo24HourFormat(slot) + ':00'); // ISO 8601 format

                        // console.log(formattedDate + 'T' + convertTo24HourFormat(slot) + ':00');
                        // Convert slot time to London Time
                        let londonTime = convertToLondonTime(slotDateTime);

                        // Ensure slotDateTime is valid before proceeding
                        // if (isNaN(londonTime.getTime())) {
                        //     console.error("Invalid London time for slot:", slot);
                        // }

                        // Check if today’s date matches the slot date (in London Time)
                        // let todayLondon = new Date().toLocaleDateString("en-GB", { timeZone: "Europe/London" });
                        // let slotLondonDate = londonTime.toLocaleDateString("en-GB");

                        // console.log("todayLondon === slotLondonDate"+ (todayLondon === slotLondonDate));

                        slotsContainer.append('<div class="time-slot"><input onClick="" type="radio" name="time-slot" id="slot-' + index + '"><label for="slot-' + index + '" class="slot-button">' + slot + '</label></div>');

                        // let londonHourNow = todayCurrentLondonHours();
                        // console.log("Current London Hour:", londonHourNow);

                        // if (todayLondon === slotLondonDate) { // Only disable for today
                        //     if ( londonHourNow >= 5 ) { // 1 PM London time

                        //         $('#slot-' + index).attr('disabled', true);
                        //         $('#slot-' + index).parent().addClass('disabled');
                        //         $('#slot-' + index).parent().css('color', 'green');
                        //     }
                        // }

                        if (isOneHourAhead(slot, dateText)) {
                            if (isTimeReserved(bookedSlots, dateText, slot)) {
                                $('#slot-' + index).attr('disabled', true);
                                $('#slot-' + index).parent().addClass('disabled');
                                $('#slot-' + index).parent().css('color', 'red');
                            }
                        } else {
                            $('#slot-' + index).attr('disabled', true);
                            $('#slot-' + index).parent().addClass('disabled');
                            $('#slot-' + index).parent().css('color', 'green');
                        }
                    });
                } else {
                    slotsContainer.append('<div style="color:red" class="no-slot-found"><p>No slots found!</p></div>');
                    // generateDefaultTimeSlots(slotsContainer);
                }
            } else {
                slotsContainer.append('<div style="color:red" class="no-slot-found"><p>No slots found!</p></div>');
                // generateDefaultTimeSlots(slotsContainer);
            }

            // Hide the loader and show the slots container
            loader.hide();
            slotsContainer.show();

            // Attach click event listener to dynamically generated radio buttons
            slotsContainer.on('click', '.time-slot input[type="radio"]', function () {
                var selectedValue = $(this).siblings('label').text();
                // Usage example
                // setInputValue('input_1_10', selectedValue);
                setInputValue('input_hidden_time', selectedValue);
            });

            setInputValue('input_hidden_user_zone', getUserTimeZone());

        }, 100); // Delay of 0.2 seconds (200 milliseconds)
    }

    /**
     * Fix: Convert given date/time to London Time (Handles DST Automatically)
     */
    function convertToLondonTime(date) {
        return new Date(date.toLocaleString("en-US", { timeZone: "Europe/London" }));
    }
    /**
     * Convert Date Format from "DD-MM-YYYY" to "YYYY-MM-DD" for JS Compatibility
     */
    function formatDateToISO(dateString) {
        let parts = dateString.split('-');
        return `${parts[2]}-${parts[1]}-${parts[0]}`; // Convert "21-02-2025" → "2025-02-21"
    }

    /**
     * Convert 12-hour format (e.g., "03:00 PM") to 24-hour format ("15:00")
     */
    function convertTo24HourFormat(time) {
        let [timePart, modifier] = time.split(' ');
        let [hours, minutes] = timePart.split(':');

        if (modifier.toLowerCase() === 'pm' && hours !== '12') {
            hours = String(parseInt(hours) + 12);
        }
        if (modifier.toLowerCase() === 'am' && hours === '12') {
            hours = '00';
        }

        return `${hours}:${minutes}`;
    }

    function getCurrentLondonTime() {
        let now = new Date();
        let utc = Date.UTC(
            now.getUTCFullYear(),
            now.getUTCMonth(),
            now.getUTCDate(),
            now.getUTCHours(),
            now.getUTCMinutes(),
            now.getUTCSeconds()
        );

        return new Date(utc).toLocaleString("en-GB", { timeZone: "Europe/London" });
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
        var dayOfWeek = getDayNameFromDate(dateString + "T00:00:00Z");

        // Convert dayOfWeek to the actual day name
        var dayIndex = { 'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6 };

        return dayIndex[dayOfWeek];
        // var dayName = days[dayOfWeek];
    }

    function getDayNameFromDate(dateStr) {
        try {
            let date = new Date(dateStr);

            // Set the timezone to UTC
            date.setUTCHours(0, 0, 0, 0);

            // Get the day name in UTC timezone
            let options = { weekday: 'long', timeZone: 'UTC' };
            return date.toLocaleDateString(undefined, options);
        } catch (error) {
            console.error("Invalid date format: ", error);
            return null;
        }
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
            console.info('Form with ID ' + formId + ' not found.');
            return;
        }

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = fieldName;
        input.id = fieldName;
        form.appendChild(input);
    }

    function getExcludedDaysIndex(disabledDays) {
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
        return disabledDays.map(day => dayMap[day]);
    }

    function generateDefaultTimeSlots(container) {
        const timeSlots = pc_time_slot['slot_essential_data']['default_standard_working_hours'];

        Object.keys(timeSlots).forEach((key, index) => {
            const time = timeSlots[key];
            const slotElement = $('<div class="time-slot disabled"><input disabled="disabled" onClick="" type="radio" name="time-slot" id="slot-' + index + '"><label for="slot-' + index + '" class="slot-button">' + time + '</label></div>');
            container.append(slotElement);
        });
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

    // function get_personalized_data_set(data, action) {
    //     return new Promise((resolve, reject) => {
    //         $.ajax({
    //             url: pc_time_slot.ajax_url,
    //             type: 'POST',
    //             data: {
    //                 action: action,
    //                 nonce: pc_time_slot.nonce,
    //                 data: data
    //             },
    //             success: function (response) {

    //                 if (typeof response === 'string' && response !== "") {
    //                     response = JSON.parse(response);
    //                     console.log("Parsed response", response);
    //                 }


    //                 if (response.success) {
    //                     resolve(response["data_set"]);
    //                 } else {
    //                     // console.error("Success not found: see the response below");
    //                     // console.log(response);
    //                     // console.log(typeof response);

    //                     // reject('Request failed');
    //                     get_personalized_data_set(data, action);
    //                 }
    //             },
    //             error: function (xhr, status, error) {
    //                 reject('AJAX error: ' + status + ', ' + error);
    //             }
    //         });
    //     });
    // }

    // function get_personalized_data_set_1(data, action) {
    //     console.log("2 ajax calling...");
    //     return new Promise((resolve, reject) => {
    //         $.ajax({
    //             url: pc_time_slot.ajax_url,
    //             type: 'POST',
    //             data: {
    //                 action: action,
    //                 nonce: pc_time_slot.nonce,
    //                 data: data
    //             },
    //             success: function (response) {

    //                 if (typeof response === 'string' && response !== "") {
    //                     response = JSON.parse(response);
    //                     console.log("Parsed response", response);
    //                 }


    //                 if (response.success) {
    //                     resolve(response["data_set"]);
    //                 } else {
    //                     // console.error("Success not found: see the response below");
    //                     // console.log(response);
    //                     // console.log(typeof response);

    //                     // reject('Request failed');
    //                     get_personalized_data_set_1(data, action);
    //                 }
    //             },
    //             error: function (xhr, status, error) {
    //                 reject('AJAX error: ' + status + ', ' + error);
    //             }
    //         });
    //     });
    // }

    async function get_personalized_data_set(data, action, retries = 3) {
        try {
            const response = await $.ajax({
                url: pc_time_slot.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: pc_time_slot.nonce,
                    data: data
                }
            });

            let parsed = response;
            if (typeof response === 'string' && response !== "") {
                parsed = JSON.parse(response);
            }

            if (parsed.success) {
                return parsed["data_set"];
            } else {
                throw new Error('Success false');
            }

        } catch (error) {
            if (retries > 0) {
                console.warn(`Retrying ${action}... attempts left: ${retries}`);
                await new Promise(res => setTimeout(res, 500)); // wait 0.5s
                return get_personalized_data_set(data, action, retries - 1);
            } else {
                throw new Error(`Failed after retries: ${action}`);
            }
        }
    }


    function todayCurrentLondonHours() {
        let now = new Date();
        let londonHourNow = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Europe/London',
            hour: 'numeric',
            hour12: false
        }).format(now);
        return londonHourNow;
    }

});