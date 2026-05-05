<?php

if (!defined('ABSPATH')) {
    exit;
}

final class ObjectFlow_Setup_Page {
    private const NONCE_ACTION_CREATE = 'oflow_create_tables';
    private const NONCE_ACTION_REMOVE = 'oflow_remove_tables';

    public function __construct() {
        add_action('admin_post_oflow_create_tables', [$this, 'handle_create_tables']);
        add_action('admin_post_oflow_remove_tables', [$this, 'handle_remove_tables']);
    }

    /** @var string[] */
    private array $table_definitions = [
        'oflow_object' => "(
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            serialNO CHAR(64) NOT NULL,
            typeID BIGINT UNSIGNED NOT NULL,
            createdDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            createdUser BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (ID)
        )",
        'oflow_objectType' => "(
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name CHAR(256) NOT NULL,
            description TEXT NULL,
            PRIMARY KEY (ID)
        )",
        'oflow_todo' => "(
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            workflowID BIGINT UNSIGNED NOT NULL,
            objectID BIGINT UNSIGNED NOT NULL,
            stateID BIGINT UNSIGNED NOT NULL,
            title CHAR(256) NOT NULL,
            deadline DATETIME NULL,
            createdDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            createdUser BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (ID)
        )",
        'oflow_state' => "(
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            workflowID BIGINT UNSIGNED NOT NULL,
            name CHAR(256) NOT NULL,
            description TEXT NULL,
            length INT NOT NULL,
            PRIMARY KEY (ID)
        )",
        'oflow_workflow' => "(
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name CHAR(256) NOT NULL,
            definition LONGTEXT NULL,
            PRIMARY KEY (ID)
        )",
    ];

    public function register_admin_menu(): void {
        add_menu_page(
            __('ObjectFlow', 'objectflow-sandbox'),
            __('ObjectFlow', 'objectflow-sandbox'),
            'manage_options',
            'objectflow-setup',
            [$this, 'render_setup_page'],
            'dashicons-admin-generic',
            58
        );

        add_submenu_page(
            'objectflow-setup',
            __('Setup', 'objectflow-sandbox'),
            __('Setup', 'objectflow-sandbox'),
            'manage_options',
            'objectflow-setup',
            [$this, 'render_setup_page']
        );
    }

    public function handle_create_tables(): void {
        $this->authorize_request(self::NONCE_ACTION_CREATE);
        $this->drop_oflow_tables();
        $this->create_tables();
        $this->redirect_with_notice('created');
    }

    public function handle_remove_tables(): void {
        $this->authorize_request(self::NONCE_ACTION_REMOVE);
        $this->drop_oflow_tables();
        $this->redirect_with_notice('removed');
    }

    private function authorize_request(string $nonce_action): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'objectflow-sandbox'));
        }

        check_admin_referer($nonce_action);
    }

    private function get_existing_oflow_tables(): array {
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES LIKE 'oflow\\_%'");

        return is_array($tables) ? array_values(array_filter($tables, fn($table) => str_starts_with($table, 'oflow_'))) : [];
    }

    private function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        foreach ($this->table_definitions as $table => $schema) {
            dbDelta("CREATE TABLE {$table} {$schema} {$charset_collate};");
        }
    }

    private function drop_oflow_tables(): void {
        global $wpdb;

        $existing = $this->get_existing_oflow_tables();
        if (empty($existing)) {
            return;
        }

        $allowed = array_keys($this->table_definitions);

        foreach ($existing as $table) {
            if (!str_starts_with($table, 'oflow_')) {
                continue;
            }

            if (!in_array($table, $allowed, true)) {
                continue;
            }

            $safe_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            if ($safe_table !== $table || !str_starts_with($safe_table, 'oflow_')) {
                continue;
            }

            $wpdb->query("DROP TABLE IF EXISTS `{$safe_table}`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }
    }

    private function redirect_with_notice(string $status): void {
        $url = add_query_arg(
            [
                'page' => 'objectflow-setup',
                'oflow_status' => $status,
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }

    public function render_setup_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'objectflow-sandbox'));
        }

        $status = isset($_GET['oflow_status']) ? sanitize_text_field(wp_unslash($_GET['oflow_status'])) : '';
        $tables = $this->get_existing_oflow_tables();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ObjectFlow Setup', 'objectflow-sandbox'); ?></h1>
            <?php if ($status === 'created') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Tables created/regenerated successfully.', 'objectflow-sandbox'); ?></p></div>
            <?php elseif ($status === 'removed') : ?>
                <div class="notice notice-warning is-dismissible"><p><?php echo esc_html__('Tables removed successfully.', 'objectflow-sandbox'); ?></p></div>
            <?php endif; ?>

            <h2><?php echo esc_html__('Database', 'objectflow-sandbox'); ?></h2>
            <?php if (empty($tables)) : ?>
                <p><?php echo esc_html__('No tables found.', 'objectflow-sandbox'); ?></p>
            <?php else : ?>
                <?php foreach ($tables as $table) : ?>
                    <table class="widefat striped" style="max-width: 900px; margin-bottom: 18px;">
                        <thead>
                            <tr>
                                <th><?php echo esc_html($table); ?></th>
                                <th><?php echo esc_html__('Field / Type', 'objectflow-sandbox'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->get_table_columns($table) as $column) : ?>
                                <tr>
                                    <td><?php echo esc_html($column['Field']); ?></td>
                                    <td><?php echo esc_html($column['Type']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right: 8px;">
                <?php wp_nonce_field(self::NONCE_ACTION_CREATE); ?>
                <input type="hidden" name="action" value="oflow_create_tables" />
                <button type="submit" class="button button-primary" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to recreate ObjectFlow tables?', 'objectflow-sandbox')); ?>');">
                    <?php echo esc_html__('create tables', 'objectflow-sandbox'); ?>
                </button>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                <?php wp_nonce_field(self::NONCE_ACTION_REMOVE); ?>
                <input type="hidden" name="action" value="oflow_remove_tables" />
                <button type="submit" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to remove ObjectFlow tables?', 'objectflow-sandbox')); ?>');">
                    <?php echo esc_html__('remove tables', 'objectflow-sandbox'); ?>
                </button>
            </form>
        </div>
        <?php
    }

    private function get_table_columns(string $table): array {
        global $wpdb;

        if (!str_starts_with($table, 'oflow_')) {
            return [];
        }

        $safe_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($safe_table !== $table || !str_starts_with($safe_table, 'oflow_')) {
            return [];
        }

        $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$safe_table}`", ARRAY_A);

        return is_array($columns) ? $columns : [];
    }
}
