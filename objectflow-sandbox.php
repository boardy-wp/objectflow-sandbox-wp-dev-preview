<?php
/**
 * Plugin Name: ObjectFlow Sandbox
 * Description: Experimental ObjectFlow sandbox plugin for demo and discovery purposes.
 * Version: 0.2.0
 * Author: ObjectFlow
 * Text Domain: objectflow-sandbox
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-objectflow-setup-page.php';

final class ObjectFlow_Sandbox {
    public function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('objectflow-sandbox', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_admin_menu(): void {
        $setup_page = new ObjectFlow_Setup_Page();
        $setup_page->register_admin_menu();
    }
}

new ObjectFlow_Sandbox();
