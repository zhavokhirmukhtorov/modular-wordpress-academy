<?php

defined('ABSPATH') || exit;

class Academy_Core_DB {
    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $access = $wpdb->prefix . 'academy_access';
        $logs   = $wpdb->prefix . 'academy_audit_log';

        dbDelta("CREATE TABLE {$access} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT 'manual',
            source_ref VARCHAR(191) NULL,
            granted_at DATETIME NOT NULL,
            revoked_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_user_course (user_id, course_id),
            KEY course_id (course_id),
            KEY active_access (user_id, revoked_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$logs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event VARCHAR(120) NOT NULL,
            level VARCHAR(20) NOT NULL DEFAULT 'info',
            user_id BIGINT UNSIGNED NULL,
            context LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY event (event),
            KEY level (level),
            KEY created_at (created_at)
        ) {$charset};");
    }

    public static function log(string $event, array $context = [], string $level = 'info'): void {
        global $wpdb;
        $table = $wpdb->prefix . 'academy_audit_log';
        $wpdb->insert($table, [
            'event'      => sanitize_key($event),
            'level'      => sanitize_key($level),
            'user_id'    => get_current_user_id() ?: null,
            'context'    => wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => current_time('mysql', true),
        ], ['%s','%s','%d','%s','%s']);
    }
}
