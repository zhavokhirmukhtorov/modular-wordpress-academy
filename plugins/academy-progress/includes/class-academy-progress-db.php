<?php

defined('ABSPATH') || exit;

class Academy_Progress_DB {
    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'academy_progress';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            lesson_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'completed',
            completed_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_progress (user_id, lesson_id),
            KEY user_course (user_id, course_id),
            KEY course_lesson (course_id, lesson_id)
        ) {$charset};");
    }

    public static function complete(int $user_id, int $course_id, int $lesson_id): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'academy_progress';
        $now = current_time('mysql', true);
        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (user_id, course_id, lesson_id, status, completed_at, updated_at)
             VALUES (%d, %d, %d, 'completed', %s, %s)
             ON DUPLICATE KEY UPDATE status='completed', updated_at=VALUES(updated_at)",
            $user_id, $course_id, $lesson_id, $now, $now
        );
        $ok = $wpdb->query($sql) !== false;
        if ($ok) {
            do_action('academy_lesson_completed', $user_id, $lesson_id, $course_id);
            if (function_exists('academy_log_event')) {
                academy_log_event('lesson_completed', compact('user_id','course_id','lesson_id'));
            }
        }
        return $ok;
    }
}
