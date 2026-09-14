<?php
/**
 * Plugin Name: Academy Core
 * Description: Core roles, course access, WooCommerce enrollment, and audit logging for the Modular Academy demo.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: zhavokhirmukhtorov
 */

defined('ABSPATH') || exit;

define('ACADEMY_CORE_VERSION', '1.0.0');
define('ACADEMY_CORE_PATH', plugin_dir_path(__FILE__));

require_once ACADEMY_CORE_PATH . 'includes/class-academy-core-db.php';
require_once ACADEMY_CORE_PATH . 'includes/class-academy-core-roles.php';
require_once ACADEMY_CORE_PATH . 'includes/class-academy-core-access.php';
require_once ACADEMY_CORE_PATH . 'includes/class-academy-core-woocommerce.php';

register_activation_hook(__FILE__, function () {
    Academy_Core_DB::install();
    Academy_Core_Roles::install();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    Academy_Core_Access::init();
    Academy_Core_WooCommerce::init();
});

if (!function_exists('academy_log_event')) {
    function academy_log_event(string $event, array $context = [], string $level = 'info'): void {
        Academy_Core_DB::log($event, $context, $level);
    }
}

if (!function_exists('academy_user_has_course_access')) {
    function academy_user_has_course_access(int $user_id, int $course_id): bool {
        return Academy_Core_Access::has_access($user_id, $course_id);
    }
}
