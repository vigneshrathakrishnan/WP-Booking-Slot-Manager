<?php

class Time_Slot_Manager_UI_Frontend {

    protected static $instance = null;

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        add_shortcode('pc_time_slot_booking', array($this,'display_time_slot_booking'));
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function admin_page() {
        // require_once MY_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function enqueue_frontend_scripts() {
        // wp_enqueue_style('my-plugin-admin-style', MY_PLUGIN_URL . 'assets/css/admin-style.css', array(), MY_PLUGIN_VERSION);
        // wp_enqueue_script('my-plugin-admin-script', MY_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), MY_PLUGIN_VERSION, true);

        $helper = Time_Slot_Manager_UI_Helper::get_instance();
        $slot_duration = (int) $helper::get_slot_duration();
        $calendar_days = $helper::get_allowed_calendar_days();

        $admin_time_zone = $helper::get_admin_time_zone();

        $working_hours = $helper::get_weekly_working_hours();
        $excluded_days = $helper::get_excluded_week_days();

        $server_time_diff = $helper::get_server_time_diff_from_utc();
        $default_standard_hours = $helper::default_standard_hours();

        $scheduled_hours = $helper::generate_time_slots($working_hours, $slot_duration);
        $revised_schedule = $helper::replace_day_keys_with_indices($scheduled_hours);

        if (class_exists('GFForms')) {
            $booked_slots = $helper::fetch_gravity_form_entries();
        } else {
            $booked_slots = [];
        }

        $specified_excluded_dates = $helper::get_specified_excluded_dates();

        // jQuery UI Datepicker CSS
        wp_enqueue_style('jquery-ui-css', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');

        // Custom CSS for time slot management
        wp_enqueue_style('pc-time-slot-css', TIME_SLOT_MANAGER_UI_COMPONENTS_URL . 'assets/frontend/css/time-slot-ui.css');

        // jQuery and jQuery UI
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');

        // Custom JS for handling date and time slots
        wp_enqueue_script('pc-time-slot-js', TIME_SLOT_MANAGER_UI_COMPONENTS_URL . 'assets/frontend/js/time-slot-ui.js', array('jquery', 'jquery-ui-datepicker'), null, true);

         // Enqueue file as an ES6 module
        wp_enqueue_script( 'pc-time-slot-time-utils-js', TIME_SLOT_MANAGER_UI_COMPONENTS_URL . 'assets/frontend/js/pc-time-utils.js', array('jquery'), null, true );

        // Set the type attribute to module for the ES6 script
        add_filter('script_loader_tag', function($tag, $handle) {
            if ( in_array( $handle, [ 'pc-time-slot-time-utils-js', 'pc-time-slot-js']) ) {
                return str_replace('src', 'type="module" src', $tag);
            }
            return $tag;
        }, 10, 2);

        $slot_essential_data = [
            'slot_duration'  => $slot_duration ,
            'calendar_days'  => $calendar_days ,
            // 'admin_time_zone'      => $admin_time_zone ,
            'working_hours'  => $revised_schedule,
            'booked_slots'   => $booked_slots,
            'excluded_days'  => $excluded_days,
            'default_standard_working_hours'=> $default_standard_hours,
            'time_diff' => $server_time_diff,
            "specified_excluded_dates" => $specified_excluded_dates
        ];

        // Localize script to pass ajax_url and nonce
        wp_localize_script('pc-time-slot-js', 'pc_time_slot', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('time_slot_nonce'),
            "slot_essential_data" => $slot_essential_data
        ));
    }

    public function display_time_slot_booking() {
        ob_start();
        ?>
        <div class="pc slot-booking-block">
            <div id="pc-slot-date-picker"></div>
            <div id="pc-time-slots"></div>
            <div id="pc-main-loader" style="display:block;">Loading...</div>
        </div>

        <?php
        return ob_get_clean();
    }

}
