<?php

defined('ABSPATH') || exit;

class Academy_Progress_Admin {
    public static function register_menu(): void {
        add_menu_page(
            'Academy Reports',
            'Academy Reports',
            'manage_academy',
            'academy-reports',
            [self::class, 'render'],
            'dashicons-chart-bar',
            56
        );
    }

    public static function render(): void {
        if (!current_user_can('manage_academy')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'academy-progress'));
        }
        global $wpdb;
        $progress = $wpdb->prefix . 'academy_progress';
        $access = $wpdb->prefix . 'academy_access';
        $logs = $wpdb->prefix . 'academy_audit_log';
        $students = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$access} WHERE revoked_at IS NULL");
        $completed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$progress} WHERE status = 'completed'");
        $events = $wpdb->get_results("SELECT event, level, user_id, context, created_at FROM {$logs} ORDER BY id DESC LIMIT 10", ARRAY_A);

        echo '<div class="wrap"><h1>Academy Reports</h1>';
        echo '<div style="display:flex;gap:16px;max-width:720px">';
        echo '<div class="card"><h2>Active students</h2><p style="font-size:32px">' . esc_html((string)$students) . '</p></div>';
        echo '<div class="card"><h2>Completed lessons</h2><p style="font-size:32px">' . esc_html((string)$completed) . '</p></div>';
        echo '</div>';
        echo '<h2>Recent audit events</h2><table class="widefat striped"><thead><tr><th>Time (UTC)</th><th>Level</th><th>Event</th><th>User</th><th>Context</th></tr></thead><tbody>';
        foreach ($events as $event) {
            echo '<tr><td>' . esc_html($event['created_at']) . '</td><td>' . esc_html($event['level']) . '</td><td>' . esc_html($event['event']) . '</td><td>' . esc_html((string)$event['user_id']) . '</td><td><code>' . esc_html((string)$event['context']) . '</code></td></tr>';
        }
        if (!$events) {
            echo '<tr><td colspan="5">No events yet.</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
