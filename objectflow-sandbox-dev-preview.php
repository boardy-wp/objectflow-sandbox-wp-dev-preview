<?php
/**
 * Plugin Name: ObjectFlow Sandbox DEV PREVIEW
 * Description: Admin demo page for displaying basic WordPress environment values.
 * Version: 1.0.0
 * Author: ObjectFlow
 * Text Domain: objectflow-sandbox-dev-preview
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'objectflow_sandbox_dev_preview_register_menu');

function objectflow_sandbox_dev_preview_register_menu(): void
{
    add_menu_page(
        __('ObjectFlow Sandbox DEV PREVIEW', 'objectflow-sandbox-dev-preview'),
        __('ObjectFlow', 'objectflow-sandbox-dev-preview'),
        'manage_options',
        'objectflow-sandbox-dev-preview-demo',
        'objectflow_sandbox_dev_preview_render_page',
        'dashicons-networking',
        81
    );

    add_submenu_page(
        'objectflow-sandbox-dev-preview-demo',
        __('ObjectFlow Demo', 'objectflow-sandbox-dev-preview'),
        __('Demo', 'objectflow-sandbox-dev-preview'),
        'manage_options',
        'objectflow-sandbox-dev-preview-demo',
        'objectflow_sandbox_dev_preview_render_page'
    );

    add_submenu_page(
        'objectflow-sandbox-dev-preview-demo',
        __('Workflows', 'objectflow-sandbox-dev-preview'),
        __('Workflows', 'objectflow-sandbox-dev-preview'),
        'manage_options',
        'objectflow-sandbox-dev-preview-workflows',
        'objectflow_sandbox_dev_preview_render_workflows_page'
    );
}

function objectflow_sandbox_dev_preview_render_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $values = [
        'Home URL' => home_url(),
        'Site URL' => site_url(),
        'WP Version' => get_bloginfo('version'),
        'PHP Version' => phpversion(),
        'DB Name' => defined('DB_NAME') ? DB_NAME : 'N/A',
        'DB Prefix' => $wpdb->prefix,
        'Theme' => wp_get_theme()->get('Name'),
        'Debug Mode' => defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false',
    ];
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('ObjectFlow Demo', 'objectflow-sandbox-dev-preview'); ?></h1>
        <table class="widefat striped" style="max-width: 800px; margin-top: 1rem;">
            <tbody>
            <?php foreach ($values as $label => $value) : ?>
                <tr>
                    <th scope="row" style="width: 220px;"><?php echo esc_html($label); ?></th>
                    <td><?php echo esc_html((string) $value); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}


function objectflow_sandbox_dev_preview_render_workflows_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap"> 
        <h1><?php echo esc_html__('Workflows', 'objectflow-sandbox-dev-preview'); ?></h1>
        <p><?php echo esc_html__('Placeholder for future workflow listing/admin features.', 'objectflow-sandbox-dev-preview'); ?></p>
    </div>
    <?php
}
