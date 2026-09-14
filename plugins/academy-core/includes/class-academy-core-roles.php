<?php

defined('ABSPATH') || exit;

class Academy_Core_Roles {
    public static function install(): void {
        add_role('academy_student', 'Academy Student', [
            'read' => true,
        ]);

        $instructor = add_role('academy_instructor', 'Academy Instructor', [
            'read' => true,
            'upload_files' => true,
        ]);
        if (!$instructor) {
            $instructor = get_role('academy_instructor');
        }

        $course_caps = [
            'read_academy_course', 'read_private_academy_courses',
            'edit_academy_course', 'edit_academy_courses', 'edit_others_academy_courses',
            'edit_published_academy_courses', 'publish_academy_courses',
            'delete_academy_course', 'delete_academy_courses', 'delete_others_academy_courses',
            'delete_published_academy_courses',
            'read_academy_lesson', 'read_private_academy_lessons',
            'edit_academy_lesson', 'edit_academy_lessons', 'edit_others_academy_lessons',
            'edit_published_academy_lessons', 'publish_academy_lessons',
            'delete_academy_lesson', 'delete_academy_lessons', 'delete_others_academy_lessons',
            'delete_published_academy_lessons',
        ];

        if ($instructor) {
            foreach ($course_caps as $cap) {
                $instructor->add_cap($cap);
            }
        }

        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_merge($course_caps, ['manage_academy', 'view_academy_reports']) as $cap) {
                $admin->add_cap($cap);
            }
        }
    }
}
