<?php
/**
 * Plugin Name: Academy Progress
 * Description: Progress tracking, REST endpoints, and reporting for the Modular Academy demo.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: zhavokhirmukhtorov
 */

defined('ABSPATH') || exit;

define('ACADEMY_PROGRESS_PATH', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, function () {
    if (!function_exists('academy_user_has_course_access')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Academy Progress requires the Academy Core plugin to be active.', 'academy-progress'),
            esc_html__('Plugin dependency missing', 'academy-progress'),
            ['back_link' => true]
        );
    }
});

require_once ACADEMY_PROGRESS_PATH . 'includes/class-academy-progress-db.php';
require_once ACADEMY_PROGRESS_PATH . 'includes/class-academy-progress-rest.php';
require_once ACADEMY_PROGRESS_PATH . 'includes/class-academy-progress-admin.php';

register_activation_hook(__FILE__, [Academy_Progress_DB::class, 'install']);
add_action('rest_api_init', [Academy_Progress_REST::class, 'register_routes']);
add_action('admin_menu', [Academy_Progress_Admin::class, 'register_menu']);
