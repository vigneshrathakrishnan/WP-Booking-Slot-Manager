<?php
/**
 * Booking Slot Manager UI components
 *
 * @package       Booking Slot Manager
 * @author        Vignesh Kumar Radhakrishnan | MangoCommerce 
 *
 * @wordpress-plugin
 * Plugin Name:       Booking Slot Manager UI components
 * Plugin URI:        #
 * Description:       Providing font-end user interface for time slot manager.
 * Version:           1.0.0
 * Author:            Vignesh Kumar Radhakrishnan | MangoCommerce
 * Author URI:        #
 * Text Domain:       booking-slot-manager-ui-components
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define('TIME_SLOT_MANAGER_UI_COMPONENTS_VERSION', '1.0.0');
define('TIME_SLOT_MANAGER_UI_COMPONENTS_DIR', plugin_dir_path(__FILE__));
define('TIME_SLOT_MANAGER_UI_COMPONENTS_URL', plugin_dir_url(__FILE__));

// Add this at the top of your main plugin file
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if ( !function_exists('time_manager_ui_register_activation_hook_callback') ) {
    // Register the activation hook
    register_activation_hook(__FILE__, 'time_manager_ui_register_activation_hook_callback');

    function time_manager_ui_register_activation_hook_callback() {
        // Do nothing for now
    }
}

// Include the main class
require_once TIME_SLOT_MANAGER_UI_COMPONENTS_DIR . 'includes/class-ui-components-main.php';

// Initialize the plugin
add_action('plugins_loaded', array('UI_Components_Main', 'get_instance'));


add_action('wp_ajax_get_personalized_reserved_data_set', 'get_personalized_reserved_data_set_callback');
add_action('wp_ajax_nopriv_get_personalized_reserved_data_set', 'get_personalized_reserved_data_set_callback'); // Allow non-logged in users to access

add_action('wp_ajax_get_personalized_weekly_hours_data_set', 'get_personalized_weekly_hours_data_set_callback');
add_action('wp_ajax_nopriv_get_personalized_weekly_hours_data_set', 'get_personalized_weekly_hours_data_set_callback');

function get_personalized_reserved_data_set_callback() {
    require_once TIME_SLOT_MANAGER_UI_COMPONENTS_DIR . 'includes/class-helper.php';
    $helper = Time_Slot_Manager_UI_Helper::get_instance();

    // Your callback logic here
    $data = $_POST['data']; // Example: Getting data from the AJAX request

    // $server_time_zone = $helper::get_time_zone_from_offset($helper::get_server_time_diff_from_utc());
    // $server_time_zone = $helper::get_admin_time_zone();

    // Entry always save in London time zone
    $server_time_zone = "Europe/London";
    $user_time_zone = $data['user_time_zone'];

    if ( class_exists("GFForms") ){ 
        $booked_slots = $helper::fetch_gravity_form_entries();
    }else {
        $booked_slots = [];
    }

    $personalized_booked_slots= $helper::convert_date_time_zone_for_reserved_slot($booked_slots, $server_time_zone, $user_time_zone);

    $response = array('success' => true, 'data_set' => $personalized_booked_slots);
    wp_send_json($response);
    exit;
}

function get_personalized_weekly_hours_data_set_callback(){
    require_once TIME_SLOT_MANAGER_UI_COMPONENTS_DIR . 'includes/class-helper.php';
    $helper = Time_Slot_Manager_UI_Helper::get_instance();

    // Your callback logic here
    $data = $_POST['data']; // Example: Getting data from the AJAX request

    // $server_time_zone = $helper::get_time_zone_from_offset($helper::get_server_time_diff_from_utc());
    $server_time_zone = $helper::get_admin_time_zone();
    $user_time_zone = $data['user_time_zone'];

    $working_hours = $helper::get_weekly_working_hours();
    $slot_duration = (int) $helper::get_slot_duration();

    $scheduled_weekly_hours = $helper::generate_time_slots($working_hours, $slot_duration);
    $standard_weekly_hours = $helper::replace_day_keys_with_indices($scheduled_weekly_hours);

    $personalized_weekly_hours = $helper::convert_date_time_zone_for_weeky_hours($standard_weekly_hours, $server_time_zone, $user_time_zone);

    $response = array('success' => true, 'data_set' => $personalized_weekly_hours);
    wp_send_json($response);
    exit;
}

/**
 * Get the timezone setting from WordPress
 *
 * @return DateTimeZone The DateTimeZone object representing the WordPress timezone
 */
function getWordpressTimezone() {
    $timezone_string = get_option('timezone_string');

    if ($timezone_string) {
        // If the timezone string is set, use it
        return new DateTimeZone($timezone_string);
    } else {
        // If the timezone string is not set, use the GMT offset
        $gmt_offset = get_option('gmt_offset');
        
        // Handle positive and negative offsets correctly
        if ($gmt_offset == 0) {
            $timezone_string = 'UTC';
        } elseif ($gmt_offset > 0) {
            $timezone_string = sprintf('Etc/GMT-%d', $gmt_offset);
        } else {
            $timezone_string = sprintf('Etc/GMT+%d', abs($gmt_offset));
        }
        return new DateTimeZone($timezone_string);
    }
}

/**
 * Convert a DateTime object to the WordPress timezone
 *
 * @param DateTime $datetime The DateTime object to be converted
 * @return DateTime The DateTime object converted to the WordPress timezone
 */
function convertToWordpressTimezone(DateTime $datetime) {
    $wpTimezone = getWordpressTimezone();
    $datetime->setTimezone($wpTimezone);
    return $datetime;
}