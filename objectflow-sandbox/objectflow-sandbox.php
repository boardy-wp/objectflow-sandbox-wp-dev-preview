<?php
/**
 * Plugin Name: ObjectFlow Sandbox DEV PREVIEW
 * Description: Development preview of a JSON-based object workflow sandbox. Do not install on production websites.
 * Version: 0.1.0-dev
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Boardy WP
 * Text Domain: objectflow-sandbox
 * GitHub Plugin URI: boardy-wp/objectflow-sandbox-wp-dev-preview
 * Primary Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OFS_VERSION', '0.1.0-dev');
define('OFS_PLUGIN_FILE', __FILE__);
define('OFS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OFS_DEV_PREVIEW', true);

require_once OFS_PLUGIN_DIR . 'includes/class-ofs-workflow-engine.php';
require_once OFS_PLUGIN_DIR . 'includes/class-ofs-db.php';
require_once OFS_PLUGIN_DIR . 'includes/class-ofs-seeder.php';
require_once OFS_PLUGIN_DIR . 'includes/class-ofs-activator.php';
require_once OFS_PLUGIN_DIR . 'includes/class-ofs-admin-menu.php';

register_activation_hook(OFS_PLUGIN_FILE, ['OFS_Activator', 'activate']);
add_action('plugins_loaded', ['OFS_Admin_Menu', 'init']);
