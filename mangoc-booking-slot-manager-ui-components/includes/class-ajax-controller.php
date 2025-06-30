<?php
// Define a class for your AJAX handler
class UiAjaxController {
    private static $instance;
    private $helper;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_get_personalized_reserved_date_set', array($this, 'get_personalized_reserved_date_set_callback'));
        add_action('wp_ajax_nopriv_get_personalized_reserved_date_set', array($this, 'get_personalized_reserved_date_set_callback')); // Allow non-logged in users to access

        add_action('wp_ajax_get_personalized_weekly_hours_date_set', array($this, 'get_personalized_weekly_hours_date_set_callback'));
        add_action('wp_ajax_nopriv_get_personalized_weekly_hours_date_set', array($this, 'get_personalized_weekly_hours_date_set_callback'));

        require_once TIME_SLOT_MANAGER_UI_COMPONENTS_DIR . 'includes/class-helper.php';

        $this->helper = Time_Slot_Manager_UI_Helper::get_instance();
    }

    public function get_personalized_reserved_date_set_callback() {
        // Your callback logic here
        $data = $_POST['data']; // Example: Getting data from the AJAX request
        $response = array('success' => true, 'message' => 'Data received: ' . $data);
        wp_send_json($response);
    }

    public function get_personalized_weekly_hours_date_set_callback(){
         // Your callback logic here
        $data = $_POST['data']; // Example: Getting data from the AJAX request
        $response = array('success' => true, 'message' => 'Data received: ' . $data);
        wp_send_json($response);
    }
}

