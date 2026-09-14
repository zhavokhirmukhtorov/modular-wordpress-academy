<?php

defined('ABSPATH') || exit;

class Academy_Courses_Content {
    public static function register(): void {
        register_post_type('academy_course', [
            'labels' => ['name' => 'Courses', 'singular_name' => 'Course'],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => ['title','editor','excerpt','thumbnail','author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'courses'],
            'capability_type' => ['academy_course', 'academy_courses'],
            'map_meta_cap' => true,
        ]);

        register_post_type('academy_lesson', [
            'labels' => ['name' => 'Lessons', 'singular_name' => 'Lesson'],
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'supports' => ['title','editor','excerpt','author'],
            'capability_type' => ['academy_lesson', 'academy_lessons'],
            'map_meta_cap' => true,
        ]);

        register_post_meta('academy_lesson', '_academy_course_id', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => fn() => current_user_can('edit_academy_lessons'),
        ]);

        register_post_meta('academy_lesson', '_academy_lesson_order', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => fn() => current_user_can('edit_academy_lessons'),
        ]);
    }
}
