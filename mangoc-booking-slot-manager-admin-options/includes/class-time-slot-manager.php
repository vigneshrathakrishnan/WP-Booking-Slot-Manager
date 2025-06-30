<?php

class Time_Slot_Manager_Main {
    
    protected static $instance = null;

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function includes() {
        require_once TIME_SLOT_MANAGER_DIR . 'includes/class-admin.php';
        // require_once TIME_SLOT_MANAGER_DIR . 'includes/class-frontend.php';
    }

    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));

        if (is_admin()) {
            Time_Slot_Manager_Admin::get_instance();
        }
        //  else {
        //     Time_Slot_Manager_Frontend::get_instance();
        // }
    }

    public function load_textdomain() {
        load_plugin_textdomain('my-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}
