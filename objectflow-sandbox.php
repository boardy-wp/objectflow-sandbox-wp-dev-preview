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

require_once __DIR__ . '/objectflow-sandbox/objectflow-sandbox.php';
