<?php

defined('ABSPATH') || exit;

class Academy_Core_WooCommerce {
    private const NONCE_ACTION = 'academy_save_course_mapping';
    private const NONCE_NAME = '_academy_course_mapping_nonce';

    public static function init(): void {
        add_action('woocommerce_product_options_general_product_data', [self::class, 'course_field']);
        add_action('woocommerce_process_product_meta', [self::class, 'save_course_field']);
        add_action('woocommerce_order_status_completed', [self::class, 'enroll_from_order']);
    }

    public static function course_field(): void {
        if (!function_exists('woocommerce_wp_text_input')) {
            return;
        }

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        woocommerce_wp_text_input([
            'id' => '_academy_course_id',
            'label' => 'Academy Course ID',
            'description' => 'Grant access to this course when the order is completed.',
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['min' => '1', 'step' => '1'],
        ]);
    }

    public static function save_course_field(int $product_id): void {
        if (!current_user_can('edit_post', $product_id)) {
            return;
        }

        $nonce = isset($_POST[self::NONCE_NAME])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]))
            : '';

        if (!$nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        $course_id = isset($_POST['_academy_course_id'])
            ? absint(wp_unslash($_POST['_academy_course_id']))
            : 0;

        if ($course_id > 0 && get_post_type($course_id) === 'academy_course') {
            update_post_meta($product_id, '_academy_course_id', $course_id);
        } else {
            delete_post_meta($product_id, '_academy_course_id');
        }
    }

    public static function enroll_from_order(int $order_id): void {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        if (!$user_id) {
            academy_log_event('enrollment_skipped_guest_order', ['order_id' => $order_id], 'warning');
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();
            $course_id = (int) get_post_meta($product_id, '_academy_course_id', true);

            if ($course_id > 0 && get_post_type($course_id) === 'academy_course') {
                Academy_Core_Access::grant($user_id, $course_id, 'woocommerce', (string) $order_id);
            }
        }
    }
}
