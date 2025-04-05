<?php
/*
Plugin Name: Custom SMTP Settings
Description: A customizable SMTP configuration plugin for WordPress
Version: 1.0.0
Author: Olayinka Aremu
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CUSTOM_SMTP_VERSION', '1.0.0');
define('CUSTOM_SMTP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_SMTP_PLUGIN_URL', plugin_dir_url(__FILE__));


// Include necessary files
require_once CUSTOM_SMTP_PLUGIN_DIR . 'includes/class-smtp-settings.php';
require_once CUSTOM_SMTP_PLUGIN_DIR . 'includes/class-smtp-mailer.php';
require_once CUSTOM_SMTP_PLUGIN_DIR . 'includes/class-smtp-logger.php';

// Initialize the plugin
if (!class_exists('Custom_SMTP_Plugin')) {
    class Custom_SMTP_Plugin
    {
        private static $instance = null;
        private $smtp_settings;
        private $smtp_mailer;

        private function __construct()
        {
            // Initialize classes
            $this->smtp_settings = new SMTP_Settings();
            $this->smtp_mailer = new SMTP_Mailer();

            // Add hooks
            add_action('admin_menu', array($this->smtp_settings, 'add_settings_page'));
            add_action('admin_init', array($this->smtp_settings, 'register_settings'));
            add_action('phpmailer_init', array($this->smtp_mailer, 'configure_smtp'));
        }

        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public static function activate()
        {
            // Initialize the logger to create the table
            $logger = new SMTP_Logger();


            // Activation tasks
            $default_settings = array(
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_encryption' => 'tls',
                'smtp_auth' => 'yes',
                'smtp_username' => '',
                'smtp_password' => '',
                'from_email' => '',
                'from_name' => ''
            );

            add_option('custom_smtp_settings', $default_settings);
        }

        public static function deactivate()
        {
            // Deactivation tasks
        }

        public static function uninstall()
        {
            // Cleanup tasks
            delete_option('custom_smtp_settings');
        }
    }
}

// Initialize the plugin
function custom_smtp_init()
{
    return Custom_SMTP_Plugin::get_instance();
}

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, array('Custom_SMTP_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Custom_SMTP_Plugin', 'deactivate'));
register_uninstall_hook(__FILE__, array('Custom_SMTP_Plugin', 'uninstall'));

// Start the plugin
custom_smtp_init();
