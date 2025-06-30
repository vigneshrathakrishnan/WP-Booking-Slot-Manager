<?php
/**
 * Booking Slot Manager Admin Options
 *
 * @package       Booking Slot Manager
 * @author        Price Checker
 *
 * @wordpress-plugin
 * Plugin Name:       Booking Slot Manager Admin Options
 * Plugin URI:        https://www.advancedcustomfields.com
 * Description:       Providing admin option fields for Booking slot manager.
 * Version:           1.0.0
 * Author:            Price Checker
 * Author URI:        https://price-checker.com
 * Text Domain:       booking-slot-manager-admin-options
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


define('TIME_SLOT_MANAGER_VERSION', '1.0.0');
define('TIME_SLOT_MANAGER_DIR', plugin_dir_path(__FILE__));
define('TIME_SLOT_MANAGER_URL', plugin_dir_url(__FILE__));

// Add this at the top of your main plugin file
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Register the activation hook
register_activation_hook(__FILE__, 'check_cmb2_active_on_activation');

function check_cmb2_active_on_activation() {
    if (!is_plugin_active('cmb2/init.php')) {
        // Deactivate your plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Display an admin notice
        add_action('admin_notices', 'cmb2_dependency_admin_notice');

        // Trigger an error to prevent further execution
        wp_die(__('This plugin requires CMB2 to be installed and activated.', 'time-slot-manager-admin-options'));
    }
}

// Admin notice for CMB2 dependency
function cmb2_dependency_admin_notice() {
    echo '<div class="error"><p>';
    _e('This plugin requires CMB2 to be installed and activated.', 'time-slot-manager-admin-options');
    echo '</p></div>';
}


// Include the main class
require_once TIME_SLOT_MANAGER_DIR . 'includes/class-time-slot-manager.php';
require_once TIME_SLOT_MANAGER_DIR . 'includes/class-helper.php';

// Initialize the plugin
add_action('plugins_loaded', array('Time_Slot_Manager_Main', 'get_instance'));