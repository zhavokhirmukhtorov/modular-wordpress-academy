<?php

defined('ABSPATH') || exit;

class Academy_Courses_REST {
    public static function register_routes(): void {
        register_rest_route('academy/v1', '/courses', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'courses'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('academy/v1', '/courses/(?P<id>\d+)/lessons', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'lessons'],
            'permission_callback' => function (WP_REST_Request $request): bool {
                $course_id = (int) $request['id'];
                return is_user_logged_in() && function_exists('academy_user_has_course_access')
                    && (academy_user_has_course_access(get_current_user_id(), $course_id) || current_user_can('manage_academy'));
            },
            'args' => ['id' => ['validate_callback' => fn($v) => is_numeric($v) && (int)$v > 0]],
        ]);
    }

    public static function courses(): WP_REST_Response {
        $posts = get_posts([
            'post_type' => 'academy_course',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);
        $items = array_map(fn($p) => [
            'id' => $p->ID,
            'title' => get_the_title($p),
            'excerpt' => get_the_excerpt($p),
            'url' => get_permalink($p),
        ], $posts);
        return rest_ensure_response(['items' => $items]);
    }

    public static function lessons(WP_REST_Request $request): WP_REST_Response {
        $posts = get_posts([
            'post_type' => 'academy_lesson',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'meta_query' => [[
                'key' => '_academy_course_id',
                'value' => (int) $request['id'],
                'compare' => '=',
                'type' => 'NUMERIC',
            ]],
            'meta_key' => '_academy_lesson_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);
        $items = array_map(fn($p) => [
            'id' => $p->ID,
            'title' => get_the_title($p),
            'order' => (int) get_post_meta($p->ID, '_academy_lesson_order', true),
        ], $posts);
        return rest_ensure_response(['items' => $items]);
    }
}
