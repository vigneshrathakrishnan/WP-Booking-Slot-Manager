<?php

class Time_Slot_Manager_Admin {

    protected static $instance = null;

    protected static $helper;

    private function __construct() {
        // add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        add_action('cmb2_admin_init', array($this,'cmb2_sample_options_page'));

        // Add validation filter for the option
        add_filter('pre_update_option_pc_time_slot_manager_available_hours_options', [$this,'validate_pc_time_slot_manager_options'], 10, 2);

        self::$helper = Time_Slot_Manager_Helper::get_instance();
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_admin_menu() {
        // add_menu_page(
        //     __('My Plugin Settings', 'my-plugin'),
        //     __('My Plugin', 'my-plugin'),
        //     'manage_options',
        //     'my-plugin',
        //     array($this, 'admin_page'),
        //     'dashicons-admin-generic'
        // );
    }

    public function admin_page() {
        // require_once MY_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('pc-time-slot-manager-admin-style', TIME_SLOT_MANAGER_URL . 'assets/admin/css/style.css', array(), TIME_SLOT_MANAGER_VERSION);
        wp_enqueue_script('pc-time-slot-manager-admin-script', TIME_SLOT_MANAGER_URL . 'assets/admin/js/admin.js', array('jquery'), TIME_SLOT_MANAGER_VERSION, true);
    }

    // Hook in and add an options page
    public function cmb2_sample_options_page() {
        $prefix = "pc_time_slot_manager_";

        // $cmb_main_options = new_cmb2_box(array(
        //     'id'           => $prefix .'main_options_page',
        //     'title'        => esc_html__('Time Slot Management', 'cmb2'),
        //     'object_types' => array('options-page'),
        //     'option_key'   =>  $prefix .'main_options', // The option key and admin menu page slug.
        //     'icon_url'     => 'dashicons-clock', // Menu icon. Only applicable if 'parent_slug' is left empty.
        //     'display_cb'   => false, // Override the options-page form output,
        //     'capability'   => 'manage_options', // Cap required to view options-page.
        // ));

        // General setting
        $cmb_general_settings = new_cmb2_box(array(
            'id'           => $prefix .'general_settings_options_page',
            'title'        => esc_html__('Time slot Manager', 'cmb2'),
            'object_types' => array('options-page'),
            'option_key'   =>  $prefix .'general_settings_options', // The option key and admin menu page slug.
            'icon_url'     => 'dashicons-tickets-alt', // Menu icon. Only applicable if 'parent_slug' is left empty.
            // 'parent_slug'  => $prefix .'main_options'
        ));

        $cmb_available_hours_options = new_cmb2_box(array(
            'id'           => $prefix .'available_hours_options_page',
            'title'        => esc_html__('Available Hours', 'cmb2'),
            'object_types' => array('options-page'),
            'option_key'   =>  $prefix .'available_hours_options', // The option key and admin menu page slug.
            'icon_url'     => 'dashicons-tickets-alt', // Menu icon. Only applicable if 'parent_slug' is left empty.
            'parent_slug'  => $prefix .'general_settings_options'
        ));


         // Add a select field for duration
        $cmb_general_settings->add_field(array(
            'name'             => esc_html__('Slot Duration', 'cmb2'),
            'id'               => 'slot_duration',
            'type'             => 'select',
            'options'          => array(
                '30'      => esc_html__('30 mins', 'cmb2'),
                '45'      => esc_html__('45 mins', 'cmb2'),
                '60'      => esc_html__('60 mins', 'cmb2'),
            ),
            'default'          => '30',
        ));

        // Add a field for date range
        $cmb_general_settings->add_field(array(
            'name' => esc_html__('Allowed Calender Days', 'cmb2'),
            'desc' => esc_html__('The entered no of days from the current date will be available to schedule the meeting.', 'cmb2'),
            'id'   => 'date_range',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min'  => '1',
            ),
        ));

        $cmb_general_settings->add_field(array(
            'name'             => esc_html__('Admin Timezone', 'cmb2'),
            'id'               => 'admin_time_zone',
            'type'             => 'select',
            'options'          => array(
                'UK'      => esc_html__('UK', 'cmb2'),
                'IN'      => esc_html__('India', 'cmb2'),
            ),
            'default'          => 'UK',
        ));

        // Add grouped fields
        $cmb_general_settings->add_field(array(
            'name'    => esc_html__('Standard Working Hours', 'cmb2'),
            'id'      => $prefix . 'grouped_standard_hours',
            'type'    => 'group',
            'repeatable' => false,
            'fields'  => array(
                array(
                    'name'       => esc_html__('From', 'cmb2'),
                    'id'         => $prefix . 'standard_hours_from',
                    'type'       => 'select',
                    'options'    => $this->generate_time_options('12:00 AM', '11:59 PM', 15),
                    'attributes' => array(
                        'style' => 'width: 48%; float: left; margin-right: 2%;', // Adjust width and styling as needed
                    ),
                ),
                array(
                    'name'       => esc_html__('To', 'cmb2'),
                    'id'         => $prefix . 'standard_hours_to',
                    'type'       => 'select',
                    'options'    => $this->generate_time_options('12:00 AM', '11:59 PM', 15),
                    'attributes' => array(
                        'style' => 'width: 48%; float: left; margin-right: 2%;', // Adjust width and styling as needed
                    ),
                ),
            ),
        ));


        $this->weekly_available_hours($cmb_available_hours_options,$prefix);
    }

    public function weekly_available_hours($cmb_options,$prefix)
    {
       $standard_working_hours = self::$helper->get_standard_working_hours();
       $frequency = 15;

        if ( is_array($standard_working_hours) && count($standard_working_hours) > 0 ) {
            $start_time = ( $standard_working_hours[0] == 'Select Time' ) ? "9.00 AM" : $standard_working_hours[0];
            $end_time   = ( $standard_working_hours[1] == 'Select Time' ) ? "6.00 PM" : $standard_working_hours[1];

            $time_options = $this->generate_time_options($start_time, $end_time, $frequency);
        } else{
            // Create time options
            $start_time = '9:00 AM';
            $end_time = '06:00 PM';
            $time_options = $this->generate_time_options($start_time, $end_time, $frequency);
        }

        // Array of days for the sections
        $days_of_week = array(
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday'
        );

        // Add a multi-select field to exclude days of the week
        $cmb_options->add_field(array(
            'name'             => esc_html__('Exclude Days', 'cmb2'),
            'id'               => 'excluded_week_days',
            'type'             => 'multicheck',
            'desc'        => esc_html__('Checked days will be disabled on the calendar', 'cmb2'),
            'options'          => $days_of_week,
            // 'default'          => ['sun','sat'], // Default to selecting all days
        ));

        // Add a text field to exclude dates
        $cmb_options->add_field(array(
            'name'        => esc_html__('Exclude Dates', 'cmb2'),
            'id'          => 'specified_excluded_dates',
            'type'        => 'textarea',
            'desc'        => $this->generate_dynamic_desc(),
            'attributes'  => array(
                'style' => 'height: 100px;', // Set the height of the input box
            ),
        ));

        // Loop through each day to create sections
        foreach ($days_of_week as $day_key => $day_name) {
            // Add a group field for each day
            $day_group_id = $cmb_options->add_field(array(
                'id'          => $prefix . $day_key . '_group',
                'type'        => 'group',
                'name'        => $day_name,
                'description' => esc_html__('Add time slots for ' . $day_name, 'cmb2'),
                'options'     => array(
                    'group_title'   => esc_html__('Time Slot {#}', 'cmb2'), // {#} gets replaced by row number
                    'add_button'    => esc_html__('Add Time Slot', 'cmb2'),
                    'remove_button' => esc_html__('Remove Time Slot', 'cmb2'),
                    'sortable'      => true, // beta
                    'closed'        => true, // true to have the groups closed by default
                ),
            ));

            // Add select fields for time range inside each day group
            $cmb_options->add_group_field($day_group_id, array(
                'name'    => esc_html__('From', 'cmb2'),
                'id'      => 'from_time',
                'type'    => 'select',
                'options' => $time_options,
                'default' => "Select Time",
            ));

            $cmb_options->add_group_field($day_group_id, array(
                'name'    => esc_html__('To', 'cmb2'),
                'id'      => 'to_time',
                'type'    => 'select',
                'options' => $time_options,
                'default' => "Select Time",
            ));

        }
    }

    public function generate_time_options($start_time, $end_time, $frequency) {
        $options = [];
        $options['Select Time'] = 'Select Time';

        $start_timestamp = strtotime($start_time);
        $end_timestamp = strtotime($end_time);

        while ($start_timestamp <= $end_timestamp) {
            $time_label = date('g:i A', $start_timestamp);
            $options[$time_label] = esc_html__($time_label, 'cmb2');
            $start_timestamp = strtotime("+$frequency minutes", $start_timestamp);
        }

        return $options;
    }

    public function generate_dynamic_desc() {
        // Create DateTime objects for tomorrow and the day after tomorrow
        $tomorrow = new DateTime('tomorrow');
        $day_after_tomorrow = new DateTime('tomorrow +1 day');

        // Format the dates as yyyy-mm-dd
        $formatted_tomorrow = $tomorrow->format('Y-m-d');
        $formatted_day_after_tomorrow = $day_after_tomorrow->format('Y-m-d');

        // Return the description with dynamic dates
        return 'Enter dates <span style="font-weight:bold; color:red">(yyyy-mm-dd)</span> to disable on the calendar, separated by commas (e.g., ' . $formatted_tomorrow . ', ' . $formatted_day_after_tomorrow . ').';
    }

    public function validate_pc_time_slot_manager_options($new_value, $old_value) {
        if ( !empty($old_value) ) {
           if ($old_value['specified_excluded_dates'] === $new_value['specified_excluded_dates']) return $new_value;
        }

        if ( empty($new_value['specified_excluded_dates']) ) {
            return $new_value;
        }

        $date_array = explode(',', $new_value['specified_excluded_dates']);
        foreach ($date_array as $key => $date) {
            if ( !$this->validateDateFormat( trim($date) ) ) {
                update_option( "is_pc_validation_on", true);
                return $old_value;
            }
        }

        // If validation passes, return the new value
        return $new_value;
    }


    public function validateDateFormat($dateStr) {
        // Explode the date string by hyphen
        $dateParts = explode('-', $dateStr);

        // Check if we have exactly three parts
        if (count($dateParts) !== 3) {
            return false;
        }

        // Assign the parts to variables
        list($year, $month, $day) = $dateParts;

        // Check if the year, month, and day are numeric and have proper lengths
        if (!is_numeric($year) || strlen($year) !== 4) {
            // echo "year prob";
            return false;
        }
        if (!is_numeric($month) || strlen($month) !== 2 || $month < 1 || $month > 12) {
            // echo "month prob";

            return false;
        }
        if (!is_numeric($day) || strlen($day) !== 2 || $day < 1 || $day > 31) {
            // echo "day prob";
            return false;
        }

        // Check if the date is valid
        if (!checkdate($month, $day, $year)) {
            // echo "check date prob";

            return false;
        }

        return true;
    }


}
