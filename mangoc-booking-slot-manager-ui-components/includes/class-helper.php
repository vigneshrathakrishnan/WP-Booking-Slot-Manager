<?php

if (!class_exists('Time_Slot_Manager_UI_Helper')) {

    class Time_Slot_Manager_UI_Helper {

        protected static $instance = null;

        private static $slot_general_setting_option_key = 'pc_time_slot_manager_general_settings_options';
        private static $slot_duration_field_key = 'slot_duration';
        private static $slot_allowed_calendar_days_field_key = 'date_range';
        private static $slot_time_zone_field_key = 'admin_time_zone';

        private static $slot_weekly_hours_option_key = "pc_time_slot_manager_available_hours_options";
        private static $slot_excluded_week_days_field_key = "excluded_week_days";
        private static $slot_speicified_excluded_dates_field_key = "specified_excluded_dates";

        private static $day_group_common_id = 'pc_time_slot_manager_%s_group';

        private static $slot_standard_working_hours_group_key = 'pc_time_slot_manager_grouped_standard_hours';
        private static $slot_standard_working_hours_from_field_key = 'pc_time_slot_manager_standard_hours_from';
        private static $slot_standard_working_hours_to_field_key = 'pc_time_slot_manager_standard_hours_to';

        public static function get_instance() {
            if (null == self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Get the option value from CMB2.
         *
         * @param string $option_key The option key.
         * @param string $field_id The field ID.
         * @param mixed $default Default value if the option is not found.
         *
         * @return mixed
         */
        public static function get_option($option_key, $field_id, $default = false) {
            $options = get_option($option_key, array());
            if (isset($options[$field_id])) {
                return $options[$field_id];
            }
            return $default;
        }

        /**
         * Get a group field value from CMB2.
         *
         * @param string $option_key The option key.
         * @param string $group_id The group ID.
         * @param string $field_id The field ID within the group.
         * @param mixed $default Default value if the option is not found.
         *
         * @return mixed
         */
        public static function get_group_option($option_key, $group_id, $field_id, $default = false) {
            $options = get_option($option_key, array());
            if (isset($options[$group_id])) {
                foreach ($options[$group_id] as $group) {
                    if (isset($group[$field_id])) {
                        return $group[$field_id];
                    }
                }
            }
            return $default;
        }

        public static function get_slot_duration(){
            $general_settings_options = get_option(self::$slot_general_setting_option_key);
            // Get the value for the Meeting Duration field
            return isset($general_settings_options[self::$slot_duration_field_key]) ? $general_settings_options[self::$slot_duration_field_key] : '';
        }

        public static function get_allowed_calendar_days(){
            $general_settings_options = get_option(self::$slot_general_setting_option_key);
            // Get the value for the Meeting Duration field
            return isset($general_settings_options[self::$slot_allowed_calendar_days_field_key]) ? $general_settings_options[self::$slot_allowed_calendar_days_field_key] : '';
        }

        public static function get_admin_time_zone(){
            $general_settings_options = get_option(self::$slot_general_setting_option_key);
            if ( isset($general_settings_options[self::$slot_time_zone_field_key]) ) {
                $admin_zone = $general_settings_options[self::$slot_time_zone_field_key];
                if ( $admin_zone == "IN" ) {
                    return "Asia/Kolkata";
                }else{
                    return "Europe/London";
                }
            } else{
                return "Europe/London";
            }
        }

        public static function get_weekly_working_hours(){
            $available_hours_options = get_option(self::$slot_weekly_hours_option_key);
            return $available_hours_options;
        }

        public static function get_excluded_week_days(){
            $available_hours_options = get_option(self::$slot_weekly_hours_option_key);
            return isset($available_hours_options[self::$slot_excluded_week_days_field_key]) ? $available_hours_options[self::$slot_excluded_week_days_field_key] : '';
        }


        public static function generate_time_slots($schedule, $frequency, $default_start = "Select Time", $default_end = "Select Time") {
            $revised_schedule = [];
            $end_of_day = "11:59 PM";
            $excluded_days = self::get_excluded_week_days();

            $frequency = !$frequency ? 30 : $frequency;

            // Excluding the excluded days from getting scheduled
            if ( !empty($excluded_days) && is_array($excluded_days) ){
                if ( count($excluded_days) ) {
                    foreach ($excluded_days as $key => $day) {
                        $excluded_group = str_replace("%s", $day, self::$day_group_common_id);
                        if ( isset($schedule[$excluded_group]) ) {
                            unset($schedule[$excluded_group]);
                        }
                    }
                }
            }

            foreach ($schedule as $day => $slots) {
                $revised_schedule[$day] = [];

                foreach ($slots as $slot) {
                    // Exclude non day group field
                    if ( !isset($slot['from_time']) && !isset($slot['to_time']) ) {
                        continue;
                    }

                    $start_time = $slot['from_time'];
                    $end_time = $slot['to_time'];

                    // Handle default values
                    if ($start_time == $default_start) {
                        continue;
                    }
                    if ($end_time == $default_end) {
                        $end_time = $end_of_day;
                    }

                    $start_timestamp = strtotime($start_time);
                    $end_timestamp = strtotime($end_time);

                    // Generate time slots
                    for ($timestamp = $start_timestamp; $timestamp < $end_timestamp; $timestamp = strtotime("+$frequency minutes", $timestamp)) {
                        // The final scheduling hour should not exceed the end hour
                        if ( strtotime("+$frequency minutes", $timestamp ) > $end_timestamp ) {
                            break;
                        }

                        $formatted_time = date('h:i a', $timestamp);

                        // Remove duplicate hours from the same day
                        if ( !in_array( date('h:i a', $timestamp), $revised_schedule[$day]) ) {
                            $revised_schedule[$day][] = $formatted_time;
                        }
                    }
                }
            }
            
            foreach ($revised_schedule as $day => $slots) {
                usort($slots, function($a,$b){
                    $timeA = strtotime($a);
                    $timeB = strtotime($b);
                    return $timeA-$timeB;
                });

                $revised_schedule[$day] = $slots;
            }

            return $revised_schedule;
        }

        public static function replace_day_keys_with_indices($schedule) {
            $day_mapping = [
                "pc_time_slot_manager_sun_group" => 0,
                "pc_time_slot_manager_mon_group" => 1,
                "pc_time_slot_manager_tue_group" => 2,
                "pc_time_slot_manager_wed_group" => 3,
                "pc_time_slot_manager_thu_group" => 4,
                "pc_time_slot_manager_fri_group" => 5,
                "pc_time_slot_manager_sat_group" => 6,
            ];

            $revised_schedule = [];

            foreach ($schedule as $day_group => $slots) {
                if (isset($day_mapping[$day_group])) {
                    $revised_schedule[$day_mapping[$day_group]] = $slots;
                }
            }

            return $revised_schedule;
        }

        public static function fetch_gravity_form_entries($form_id=null, $field_ids=null) {


            $form_id = ( is_null($form_id) ) ? 1 : $form_id; // Replace with your form ID
            $field_ids = ( is_null($field_ids) ) ? [4, 17] : $field_ids; // Replace with your form ID

            // Define search criteria to exclude trashed entries
            $search_criteria = [
                'status' => 'active', // Only fetch active entries
                'field_filters' => [
                    [
                        'key' => 9,
                        'value' => 'Booked',
                        'operator' => '='
                    ]
                    /********************(: Notice for Vikee :)***************/ 
                    // Add condition for booked date should be today on wards
                ]
            ];

            $sorting = null;
            // $paging = null;

            // Set pagination to fetch ALL entries
            $paging = [
                'offset' => 0,  // Start from the first entry
                'page_size' => 1000 // Set a high limit (max ~1000 per request)
            ];

            $entries = GFAPI::get_entries($form_id, $search_criteria, $sorting, $paging);
            
            if (is_wp_error($entries)) {
                echo 'Error fetching entries: ' . $entries->get_error_message();
                return;
            }

            $result_array = [];

            foreach ($entries as $entry) {
                $key = isset($entry[$field_ids[0]]) ? $entry[$field_ids[0]] : null;
                $value = isset($entry[$field_ids[1]]) ? $entry[$field_ids[1]] : null;
                
                if ($key !== null && $value !== null) {
                    if (!isset($result_array[$key])) {
                        $result_array[$key] = [];
                    }
                    $result_array[$key][] = $value;
                }
            }

            // Example of how to print the array (for debugging purposes)
            // echo '<pre>' . print_r($result_array, true) . '</pre>';
            
            return $result_array;
        }

        // Helper function to convert 12-hour time to 24-hour time
        public static function convertTo24Hour($time) {
            $time = strtolower($time);
            if (strpos($time, 'am') !== false) {
                $time = str_replace(' am', '', $time);
                $parts = explode(':', $time);
                if ($parts[0] == '12') {
                    $parts[0] = '00';
                }
            } else {
                $time = str_replace(' pm', '', $time);
                $parts = explode(':', $time);
                if ($parts[0] != '12') {
                    $parts[0] = $parts[0] + 12;
                }
            }
            return implode(':', $parts);
        }

        // Helper function to convert 24-hour time to 12-hour time
        public static function convertTo12Hour($time) {
            $parts = explode(':', $time);
            $hour = $parts[0];
            $minute = $parts[1];
            $period = 'am';
            if ($hour >= 12) {
                $period = 'pm';
                if ($hour > 12) {
                    $hour -= 12;
                }
            } elseif ($hour == 0) {
                $hour = 12;
            }
            return sprintf('%02d:%02d %s', $hour, $minute, $period);
        }

        // Helper function to adjust time by the time difference
        public static function adjustTime($time, $timeDifference) {
            $time24 = self::convertTo24Hour($time);
            $datetime = new DateTime("1970-01-01 $time24:00");
            $hours = (int) $timeDifference;
            $minutes = ($timeDifference - $hours) * 60;
            $datetime->modify("$hours hours $minutes minutes");
            return $datetime;
        }

        public static function get_standard_working_hours() {
            $general_settings_options = get_option(self::$slot_general_setting_option_key);

            if ( isset( $general_settings_options[self::$slot_standard_working_hours_group_key][0] ) ) {

                $standard_working_hours = $general_settings_options[self::$slot_standard_working_hours_group_key][0];

                if ( isset( $standard_working_hours[self::$slot_standard_working_hours_from_field_key] ) ) {
                    $standard_working_hours_from = $standard_working_hours[self::$slot_standard_working_hours_from_field_key];
                    $from = ($standard_working_hours_from == 'Select Time') ? 0 : $standard_working_hours_from;
                }

                if ( isset( $standard_working_hours[self::$slot_standard_working_hours_to_field_key] ) ) {
                    $standard_working_hours_to = $standard_working_hours[self::$slot_standard_working_hours_to_field_key];
                    $to = ($standard_working_hours_to == 'Select Time') ? 0 : $standard_working_hours_to;
                }

                if ( !$from && !$to ) {
                    return false;
                }

                return array($from, $to);
            } else{
                return false;
            }
        }

        public static function generate_time_options($start_time, $end_time, $frequency) {

            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);

            if ( !($start_timestamp <= $end_timestamp) ) {
                $start_time = "9.00 AM";
                $end_time = "6.00 PM";

                $start_timestamp = strtotime($start_time);
                $end_timestamp = strtotime($end_time);
            }

            while ($start_timestamp <= $end_timestamp) {
                $time_label = date('g:i A', $start_timestamp);
                $options[$time_label] = esc_html__($time_label, 'cmb2');
                $start_timestamp = strtotime("+$frequency minutes", $start_timestamp);

                // Exclude hour which exceeds the frequency
                if ( strtotime("+$frequency minutes", $start_timestamp ) > $end_timestamp ) {
                    break;
                }
            }

            return $options;
        }

        public static function default_standard_hours(){
            $standard_working_hours = self::get_standard_working_hours();

            $frequency = ( empty(self::get_slot_duration()) ) ?  30 : self::get_slot_duration();

            if ( is_array($standard_working_hours) && count($standard_working_hours) > 0 ) {
                $start_time = ( $standard_working_hours[0] == 'Select Time' ) ? "9.00 AM" : $standard_working_hours[0];
                $end_time   = ( $standard_working_hours[1] == 'Select Time' ) ? "6.00 PM" : $standard_working_hours[1];

                $time_options = self::generate_time_options($start_time, $end_time, $frequency);

            } else{
                // Create time options
                $start_time = '9:00 AM';
                $end_time = '06:00 PM';
                $time_options = self::generate_time_options($start_time, $end_time, $frequency);
            }

            return $time_options;
        }

        public static function get_server_time_diff_from_utc(){
            // Get the current server time as a DateTime object
            $serverTime = new DateTime();

            // Get the offset in seconds from UTC (GMT)
            $offsetInSeconds = $serverTime->getOffset();

            // Convert the offset to minutes
            $offsetInMinutes = $offsetInSeconds / 60;

            return $offsetInMinutes;
        }

        public static function convert_date_time_zone_for_reserved_slot($data, $fromTimeZone, $toTimeZone) {

            // Example usage
            // $data = [
            //     "2024-06-20" => ["03:00 pm", "02:30 pm"],
            //     "2024-06-19" => ["10:00 am", ""],
            //     "2024-06-27" => ["11:30 am"],
            //     "2024-06-29" => ["10:30 pm", "10:30 pm", "10:00 pm"],
            //     "2024-06-17" => ["04:45 pm", "04:00 pm", ""],
            //     "2024-06-25" => ["10:00 AM"],
            //     "2024-06-26" => ["10:00 am"],
            //     "2024-06-22" => ["10:00 am", "01:15 pm"]
            // ];

            // $fromTimeZone = 'UTC';
            // $toTimeZone = 'Asia/Kolkata';

            // $convertedData = convert_date_time_zone_for_reserved_slot($data, $fromTimeZone, $toTimeZone);
            // print_r($convertedData);

            // Example usage


            $convertedData = [];

            // Create DateTimeZone objects
            $fromTZ = new DateTimeZone($fromTimeZone);
            $toTZ = new DateTimeZone($toTimeZone);

            foreach ($data as $date => $times) {
                foreach ($times as $time) {
                    if (empty($time)) continue;

                    // Create DateTime object for each time
                    $dateTime = new DateTime("$date $time", $fromTZ);
                    
                    // Convert to target timezone
                    $dateTime->setTimezone($toTZ);

                    // Format the date and time in the target timezone
                    $newDate = $dateTime->format('Y-m-d');
                    $newTime = $dateTime->format('h:i a');

                    // Add to the converted data array
                    if (!isset($convertedData[$newDate])) {
                        $convertedData[$newDate] = [];
                    }
                    $convertedData[$newDate][] = $newTime;
                }
            }

            return $convertedData;
        }

        
        public static function convert_date_time_zone_for_weeky_hours($data, $fromTimeZone, $toTimeZone) {
            $result = [];

            if ( !count($data) ) return [];

            foreach ($data as $dayIndex => $times) {

                foreach ($times as $time) {
                    // Skip empty times
                    if (empty($time)) {
                        continue;
                    }

                    // Create DateTime object with the given time and from time zone
                    $currentDateTime = new DateTime($time, new DateTimeZone($fromTimeZone));

                    // Clone the currentDateTime object to avoid modifying the original object
                    $targetDateTime = clone $currentDateTime;

                    // Convert to the target time zone
                    $targetDateTime->setTimezone(new DateTimeZone($toTimeZone));

                    $dayDiff = self::getDaysDifference($currentDateTime->format('Y-m-d'), $targetDateTime->format('Y-m-d'));

                    // Format the time in the target time zone
                    $formattedTime = $targetDateTime->format('h:i a');

                    if ( $dayDiff == 0 ) {
                        $result[$dayIndex][] = $formattedTime;
                    } else{
                         $sum = $dayIndex + $dayDiff;
                         $expectedDayIndex = ($sum >= 0) ? ($sum % 7) : (7 + ($sum % 7));
                        $result[$expectedDayIndex][] = $formattedTime;
                    }
                }
            }

            // Sort the array by keys in ascending order
            ksort($result);

            return $result;
        }

        public static function getDaysDifference($date1, $date2) {
            // Create DateTime objects from the input dates
            $datetime1 = new DateTime($date1);
            $datetime2 = new DateTime($date2);

            // Calculate the difference between the two dates
            $interval = $datetime1->diff($datetime2);

            // If the first date is earlier than the second, the interval will be negative
            $daysDifference = $interval->days;
            if ($datetime1 > $datetime2) {
                $daysDifference = -$daysDifference;
            }

            return $daysDifference;
        }


        public static function get_time_zone_from_offset($offsetInMinutes) {

            // Example usage:
            // $offsetInMinutes = 330; // Change this value to the offset you have
            // $timeZone = getTimeZoneFromOffset($offsetInMinutes);
            // echo "Time Zone: " . $timeZone;
            // Example Usage

            // Convert offset to seconds
            $offsetInSeconds = $offsetInMinutes * 60;
            
            // Get the timezone name from the offset
            $timezoneName = timezone_name_from_abbr("", $offsetInSeconds, 0);
            
            // If the above function does not return a valid timezone, try the following loop through all timezones
            if ($timezoneName === false) {
                $timezoneName = 'Unknown';
                $timezones = timezone_identifiers_list();
                foreach ($timezones as $timezone) {
                    $dateTimeZone = new DateTimeZone($timezone);
                    $dateTime = new DateTime("now", $dateTimeZone);
                    if ($dateTimeZone->getOffset($dateTime) === $offsetInSeconds) {
                        $timezoneName = $timezone;
                        break;
                    }
                }
            }
            
            return $timezoneName;
        }

        public static function get_specified_excluded_dates(){
            $available_hours_options = get_option(self::$slot_weekly_hours_option_key);

            if ( isset($available_hours_options[self::$slot_speicified_excluded_dates_field_key]) && !empty($available_hours_options[self::$slot_speicified_excluded_dates_field_key]) ) {

                $explode_dates = array_map('trim', explode(",", $available_hours_options[self::$slot_speicified_excluded_dates_field_key] ) ) ;
                if ( is_array($explode_dates) ) {
                    return $explode_dates;
                }else{
                    return "";
                }
            } else{
                return "" ;
            }
        }
    }
}








