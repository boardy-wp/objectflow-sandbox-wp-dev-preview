<?php
if (!defined('ABSPATH')) { exit; }

class OFS_Admin_Menu {
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_notices', [__CLASS__, 'notice']);
    }
    public static function assets($hook): void {
        if (strpos((string)$hook, 'objectflow') === false) return;
        wp_enqueue_style('ofs-admin', OFS_PLUGIN_URL . 'assets/admin.css', [], OFS_VERSION);
    }
    public static function notice(): void {
        $page=sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
        if (strpos($page,'objectflow')!==0) return;
        echo '<div class="notice notice-warning"><p>' . esc_html__('ObjectFlow Sandbox is a development preview. Demo data only. Do not use on production websites.','objectflow-sandbox') . '</p></div>';
    }
    public static function register_menu(): void {
        add_menu_page('ObjectFlow','ObjectFlow','manage_options','objectflow',[__CLASS__,'render_dashboard'],'dashicons-networking',56);
        add_submenu_page('objectflow','Dashboard','Dashboard','manage_options','objectflow',[__CLASS__,'render_dashboard']);
        add_submenu_page('objectflow','Workflow Preview','Workflow Preview','manage_options','objectflow-workflow-preview',[__CLASS__,'render_workflow_preview']);
        add_submenu_page('objectflow','Reset Demo Data','Reset Demo Data','manage_options','objectflow-reset-demo',[__CLASS__,'render_reset_demo']);
    }
    public static function render_dashboard(): void {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        global $wpdb; $t=OFS_DB::table_names();
        $objects=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['objects']}");
        $active=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['cases']} WHERE is_active=1");
        $workflows=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['workflows']}");
        $events=$wpdb->get_results("SELECT event_type, message, created_at FROM {$t['events']} ORDER BY id DESC LIMIT 10", ARRAY_A);
        $status=$wpdb->get_results("SELECT current_status, COUNT(*) as total FROM {$t['cases']} GROUP BY current_status", ARRAY_A);
        echo '<div class="wrap"><h1>ObjectFlow Dashboard</h1><div class="ofs-warning"><strong>Development preview</strong></div>';
        echo '<p>Plugin version: <span class="ofs-badge">' . esc_html(OFS_VERSION) . '</span></p>';
        echo '<ul><li>Objects: '.esc_html((string)$objects).'</li><li>Active cases: '.esc_html((string)$active).'</li><li>Workflows: '.esc_html((string)$workflows).'</li></ul>';
        echo '<p><a href="'.esc_url(admin_url('admin.php?page=objectflow-workflow-preview')).'">Workflow Preview</a> | <a href="'.esc_url(admin_url('admin.php?page=objectflow-reset-demo')).'">Reset Demo Data</a></p>';
        echo '<h2>Recent Events</h2><table class="widefat"><thead><tr><th>Type</th><th>Message</th><th>Time</th></tr></thead><tbody>';
        foreach($events as $e){echo '<tr><td>'.esc_html($e['event_type']).'</td><td>'.esc_html((string)$e['message']).'</td><td>'.esc_html($e['created_at']).'</td></tr>';} echo '</tbody></table>';
        echo '<h2>Status Summary</h2><ul>'; foreach($status as $s){echo '<li>'.esc_html($s['current_status']).': '.esc_html((string)$s['total']).'</li>';} echo '</ul></div>';
    }
    public static function render_workflow_preview(): void {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        global $wpdb; $t=OFS_DB::table_names();
        $row=$wpdb->get_row($wpdb->prepare("SELECT name, version, definition FROM {$t['workflows']} WHERE is_active=%d ORDER BY id DESC LIMIT 1",1), ARRAY_A);
        echo '<div class="wrap"><h1>Workflow Preview</h1>';
        if (!$row) { echo '<p>No active workflow found.</p></div>'; return; }
        [$ok,$msg]=OFS_Workflow_Engine::validate_definition($row['definition']);
        $def=json_decode($row['definition'],true);
        echo '<p><strong>'.esc_html($row['name']).'</strong> v'.esc_html((string)$row['version']).'</p><p>Validation: '.esc_html($msg).'</p><div class="ofs-card-grid">';
        foreach(($def['nodes']??[]) as $n){ echo '<div class="ofs-card ofs-node-card"><h3>'.esc_html($n['label']??'').'</h3><p class="ofs-muted">'.esc_html($n['id']??'').'</p><p>Status: '.esc_html($n['status']??'').'</p><p>Role: '.esc_html($n['responsibleRole']??'').'</p><p>Location: '.esc_html($n['defaultLocation']??'').'</p>'; foreach(($n['forms']??[]) as $f){ echo '<p><strong>Form:</strong> '.esc_html($f['id']??'').'</p><ul>'; foreach(($f['buttons']??[]) as $b){echo '<li>'.esc_html(($b['id']??'').' → '.($b['targetNode']??'')).'</li>';} echo '</ul>'; } echo '</div>'; }
        echo '</div></div>';
    }
    public static function render_reset_demo(): void {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            check_admin_referer('ofs_reset_demo_data');
            OFS_Seeder::reset_and_seed();
            wp_safe_redirect(admin_url('admin.php?page=objectflow-reset-demo&reset=1')); exit;
        }
        $reset=isset($_GET['reset']) ? (int)$_GET['reset'] : 0;
        echo '<div class="wrap"><h1>Reset Demo Data</h1><p class="ofs-warning">This deletes ObjectFlow demo data.</p>';
        if ($reset===1) echo '<div class="notice notice-success"><p>Demo data reset completed.</p></div>';
        echo '<form method="post">'; wp_nonce_field('ofs_reset_demo_data'); submit_button('Reset and Reseed Demo Data','delete'); echo '</form></div>';
    }
}
