<?php

defined('ABSPATH') || exit;

class Academy_Core_Access {
    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function grant(int $user_id, int $course_id, string $source = 'manual', ?string $source_ref = null): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'academy_access';
        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (user_id, course_id, source, source_ref, granted_at, revoked_at)
             VALUES (%d, %d, %s, %s, %s, NULL)
             ON DUPLICATE KEY UPDATE source = VALUES(source), source_ref = VALUES(source_ref), granted_at = VALUES(granted_at), revoked_at = NULL",
            $user_id,
            $course_id,
            sanitize_key($source),
            $source_ref !== null ? sanitize_text_field($source_ref) : null,
            current_time('mysql', true)
        );
        $ok = $wpdb->query($sql) !== false;
        if ($ok) {
            do_action('academy_course_access_granted', $user_id, $course_id, $source, $source_ref);
            academy_log_event('course_access_granted', compact('user_id','course_id','source','source_ref'));
        }
        return $ok;
    }

    public static function has_access(int $user_id, int $course_id): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'academy_access';
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND course_id = %d AND revoked_at IS NULL LIMIT 1",
            $user_id,
            $course_id
        ));
        return !empty($value);
    }

    public static function register_routes(): void {
        register_rest_route('academy/v1', '/access/(?P<user_id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'get_access'],
            'permission_callback' => function (WP_REST_Request $request): bool {
                $requested = (int) $request['user_id'];
                return get_current_user_id() === $requested || current_user_can('manage_academy');
            },
            'args' => [
                'user_id' => ['validate_callback' => fn($v) => is_numeric($v) && (int)$v > 0],
            ],
        ]);
    }

    public static function get_access(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'academy_access';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT course_id, source, granted_at FROM {$table} WHERE user_id = %d AND revoked_at IS NULL ORDER BY granted_at DESC",
            (int) $request['user_id']
        ), ARRAY_A);
        return rest_ensure_response(['items' => $rows]);
    }
}
