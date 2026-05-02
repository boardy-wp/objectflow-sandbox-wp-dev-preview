<?php
if (!defined('ABSPATH')) { exit; }

class OFS_DB {
    public static function table_names(): array {
        global $wpdb;
        return [
            'workflows' => $wpdb->prefix . 'ofs_workflows',
            'objects' => $wpdb->prefix . 'ofs_objects',
            'cases' => $wpdb->prefix . 'ofs_cases',
            'submissions' => $wpdb->prefix . 'ofs_form_submissions',
            'events' => $wpdb->prefix . 'ofs_events',
        ];
    }

    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        $t=self::table_names();
        dbDelta("CREATE TABLE {$t['workflows']} (id bigint unsigned NOT NULL AUTO_INCREMENT,workflow_key varchar(100) NOT NULL,name varchar(255) NOT NULL,version int NOT NULL DEFAULT 1,definition longtext NOT NULL,is_active tinyint(1) NOT NULL DEFAULT 1,created_at datetime NOT NULL,updated_at datetime NOT NULL,PRIMARY KEY (id),KEY workflow_key (workflow_key),KEY is_active (is_active)) $c;");
        dbDelta("CREATE TABLE {$t['objects']} (id bigint unsigned NOT NULL AUTO_INCREMENT,serial_number varchar(191) NOT NULL,object_type varchar(191) DEFAULT '',manufacturer varchar(191) DEFAULT '',model varchar(191) DEFAULT '',notes text NULL,created_at datetime NOT NULL,updated_at datetime NOT NULL,PRIMARY KEY (id),UNIQUE KEY serial_number (serial_number)) $c;");
        dbDelta("CREATE TABLE {$t['cases']} (id bigint unsigned NOT NULL AUTO_INCREMENT,object_id bigint unsigned NOT NULL,workflow_id bigint unsigned NOT NULL,case_number varchar(100) NOT NULL,current_node_id varchar(100) NOT NULL,current_status varchar(191) NOT NULL,current_location varchar(191) DEFAULT '',responsible_role varchar(100) DEFAULT '',is_active tinyint(1) NOT NULL DEFAULT 1,opened_at datetime NOT NULL,closed_at datetime NULL,created_by bigint unsigned NULL,created_at datetime NOT NULL,updated_at datetime NOT NULL,PRIMARY KEY (id),UNIQUE KEY case_number (case_number),KEY object_id (object_id),KEY workflow_id (workflow_id),KEY current_node_id (current_node_id),KEY is_active (is_active)) $c;");
        dbDelta("CREATE TABLE {$t['submissions']} (id bigint unsigned NOT NULL AUTO_INCREMENT,case_id bigint unsigned NOT NULL,workflow_id bigint unsigned NOT NULL,node_id varchar(100) NOT NULL,form_id varchar(100) NOT NULL,button_id varchar(100) NOT NULL,submitted_by bigint unsigned NULL,payload longtext NULL,created_at datetime NOT NULL,PRIMARY KEY (id),KEY case_id (case_id),KEY workflow_id (workflow_id),KEY node_id (node_id)) $c;");
        dbDelta("CREATE TABLE {$t['events']} (id bigint unsigned NOT NULL AUTO_INCREMENT,object_id bigint unsigned NOT NULL,case_id bigint unsigned NOT NULL,event_type varchar(100) NOT NULL,from_node_id varchar(100) DEFAULT '',to_node_id varchar(100) DEFAULT '',from_status varchar(191) DEFAULT '',to_status varchar(191) DEFAULT '',from_location varchar(191) DEFAULT '',to_location varchar(191) DEFAULT '',actor_id bigint unsigned NULL,form_submission_id bigint unsigned NULL,message text NULL,metadata longtext NULL,created_at datetime NOT NULL,PRIMARY KEY (id),KEY object_id (object_id),KEY case_id (case_id),KEY event_type (event_type),KEY created_at (created_at)) $c;");
    }
}
