<?php

defined('ABSPATH') || exit;

class Academy_Progress_REST {
    public static function register_routes(): void {
        register_rest_route('academy/v1', '/progress/complete', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'complete'],
            'permission_callback' => fn() => is_user_logged_in(),
            'args' => [
                'course_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                'lesson_id' => ['required' => true, 'sanitize_callback' => 'absint'],
            ],
        ]);

        register_rest_route('academy/v1', '/progress/(?P<course_id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'get'],
            'permission_callback' => fn() => is_user_logged_in(),
            'args' => ['course_id' => ['validate_callback' => fn($v) => is_numeric($v) && (int)$v > 0]],
        ]);
    }

    public static function complete(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $course_id = absint($request['course_id']);
        $lesson_id = absint($request['lesson_id']);

        if (!function_exists('academy_user_has_course_access') || !academy_user_has_course_access($user_id, $course_id)) {
            return new WP_Error('academy_no_access', 'You do not have access to this course.', ['status' => 403]);
        }
        if (get_post_type($lesson_id) !== 'academy_lesson' || get_post_status($lesson_id) !== 'publish') {
            return new WP_Error('academy_bad_lesson', 'Invalid or unpublished lesson.', ['status' => 400]);
        }
        if ((int) get_post_meta($lesson_id, '_academy_course_id', true) !== $course_id) {
            return new WP_Error('academy_bad_lesson', 'Lesson does not belong to the selected course.', ['status' => 400]);
        }
        if (!Academy_Progress_DB::complete($user_id, $course_id, $lesson_id)) {
            return new WP_Error('academy_db_error', 'Unable to save progress.', ['status' => 500]);
        }
        return rest_ensure_response(['ok' => true, 'lesson_id' => $lesson_id]);
    }

    public static function get(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;
        $user_id = get_current_user_id();
        $course_id = (int) $request['course_id'];
        if (!function_exists('academy_user_has_course_access') || !academy_user_has_course_access($user_id, $course_id)) {
            return new WP_Error('academy_no_access', 'You do not have access to this course.', ['status' => 403]);
        }
        $table = $wpdb->prefix . 'academy_progress';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT lesson_id, status, completed_at, updated_at FROM {$table} WHERE user_id = %d AND course_id = %d ORDER BY updated_at DESC",
            $user_id, $course_id
        ), ARRAY_A);
        return rest_ensure_response(['items' => $rows]);
    }
}
