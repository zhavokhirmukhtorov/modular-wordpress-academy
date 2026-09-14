<?php
/**
 * Plugin Name: Academy Courses
 * Description: Course/lesson content model and read-only REST API for the Modular Academy demo.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: zhavokhirmukhtorov
 */

defined('ABSPATH') || exit;

define('ACADEMY_COURSES_PATH', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, function () {
    if (!function_exists('academy_user_has_course_access')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Academy Courses requires the Academy Core plugin to be active.', 'academy-courses'),
            esc_html__('Plugin dependency missing', 'academy-courses'),
            ['back_link' => true]
        );
    }
});

require_once ACADEMY_COURSES_PATH . 'includes/class-academy-courses-content.php';
require_once ACADEMY_COURSES_PATH . 'includes/class-academy-courses-rest.php';

add_action('init', [Academy_Courses_Content::class, 'register']);
add_action('rest_api_init', [Academy_Courses_REST::class, 'register_routes']);
register_activation_hook(__FILE__, function(){ Academy_Courses_Content::register(); flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
