<?php

if (!class_exists('Time_Slot_Manager_Helper')) {

    class Time_Slot_Manager_Helper {

        protected static $instance = null;

        private static $slot_general_setting_option_key = 'pc_time_slot_manager_general_settings_options';
        private static $slot_duration_field_key = 'slot_duration';
        private static $slot_allowed_calendar_days_field_key = 'date_range';
        private static $slot_time_zone_field_key = 'time_zone';

        private static $slot_weekly_hours_option_key = "pc_time_slot_manager_available_hours_options";

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

        public static function FunctionName()
        {
            $general_settings_options = get_option('pc_time_slot_manager_general_settings_options') ;

            // Get the value for the Meeting Duration field
            $slot_duration = isset($general_settings_options['slot_duration']) ? $general_settings_options['slot_duration'] : '';

            // Get the value for the Allowed Calendar Days field
            $date_range = isset($general_settings_options['date_range']) ? $general_settings_options['date_range'] : '';

            // Get the value for the Time zone field
            $time_zone = isset($general_settings_options['time_zone']) ? $general_settings_options['time_zone'] : '';

            // Get the values for the available hours options page
            $available_hours_options = get_option( 'pc_time_slot_manager_available_hours_options');
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

        public static function get_time_zone(){
            $general_settings_options = get_option(self::$slot_general_setting_option_key);
            // Get the value for the Meeting Duration field
            return isset($general_settings_options[self::$slot_time_zone_field_key]) ? $general_settings_options[self::$slot_time_zone_field_key] : '';
        }

        public static function get_weekly_working_hours(){
            $available_hours_options = get_option(self::$slot_weekly_hours_option_key);
            // Get the value for the Meeting Duration field
            // return isset($general_settings_options[self::$slot_time_zone_field_key]) ? $general_settings_options[self::$slot_time_zone_field_key] : '';
            return $available_hours_options;
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

            while ($start_timestamp <= $end_timestamp) {
                $time_label = date('g:i A', $start_timestamp);
                $options[$time_label] = esc_html__($time_label, 'cmb2');
                $start_timestamp = strtotime("+$frequency minutes", $start_timestamp);
            }

            return $options;
        }


    }

}
