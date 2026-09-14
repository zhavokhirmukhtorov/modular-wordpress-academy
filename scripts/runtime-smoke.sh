#!/usr/bin/env bash
set -euo pipefail

cleanup() {
  docker compose down -v --remove-orphans >/dev/null 2>&1 || true
}
trap cleanup EXIT

cleanup

echo '[INFO] Starting MariaDB and WordPress containers'
docker compose up -d db wordpress

ready=0
for _ in $(seq 1 60); do
  if curl -fsS http://127.0.0.1:8080/wp-admin/install.php >/dev/null 2>&1; then
    ready=1
    break
  fi
  sleep 2
done

if [ "$ready" -ne 1 ]; then
  echo '[FAIL] WordPress did not become reachable in time.' >&2
  docker compose logs wordpress db >&2 || true
  exit 1
fi

wpcli() {
  docker compose run --rm -T cli wp "$@"
}

echo '[INFO] Installing WordPress'
wpcli core install \
  --url='http://localhost:8080' \
  --title='Modular Academy CI' \
  --admin_user='admin' \
  --admin_password='admin-ci-only' \
  --admin_email='admin@example.test' \
  --skip-email

echo '[INFO] Installing WooCommerce and activating Academy plugins'
wpcli plugin install woocommerce --activate
wpcli plugin activate academy-core
wpcli plugin activate academy-courses
wpcli plugin activate academy-progress

student_id="$(wpcli user create student-ci student-ci@example.test --role=academy_student --porcelain)"
course_id="$(wpcli post create --post_type=academy_course --post_status=publish --post_title='CI Course' --porcelain)"
lesson_id="$(wpcli post create --post_type=academy_lesson --post_status=publish --post_title='CI Lesson' --porcelain)"

wpcli post meta set "$lesson_id" _academy_course_id "$course_id" >/dev/null
wpcli post meta set "$lesson_id" _academy_lesson_order 1 >/dev/null

product_id="$(wpcli eval '$product = new WC_Product_Simple(); $product->set_name("CI Course Product"); $product->set_status("publish"); $product->set_regular_price("10"); $product->save(); echo $product->get_id();')"
wpcli post meta set "$product_id" _academy_course_id "$course_id" >/dev/null

order_id="$(wpcli eval "\$order = wc_create_order(['customer_id' => ${student_id}]); \$order->add_product(wc_get_product(${product_id}), 1); \$order->calculate_totals(); \$order->save(); echo \$order->get_id();")"
wpcli eval "\$order = wc_get_order(${order_id}); if (!\$order) { fwrite(STDERR, 'Order missing' . PHP_EOL); exit(1); } \$order->update_status('completed');"

echo '[INFO] Verifying completed WooCommerce order grants course access'
wpcli eval "if (!academy_user_has_course_access(${student_id}, ${course_id})) { fwrite(STDERR, 'Enrollment check failed' . PHP_EOL); exit(1); } echo 'Enrollment OK' . PHP_EOL;"

echo '[INFO] Verifying protected REST access and progress write'
wpcli eval "
wp_set_current_user(${student_id});
do_action('rest_api_init');

\$access_request = new WP_REST_Request('GET', '/academy/v1/access/${student_id}');
\$access_response = rest_do_request(\$access_request);
if (is_wp_error(\$access_response) || \$access_response->get_status() !== 200) {
    fwrite(STDERR, 'Access REST check failed' . PHP_EOL);
    exit(1);
}
\$access_data = \$access_response->get_data();
if (empty(\$access_data['items'])) {
    fwrite(STDERR, 'Access REST returned no enrollment' . PHP_EOL);
    exit(1);
}

\$progress_request = new WP_REST_Request('POST', '/academy/v1/progress/complete');
\$progress_request->set_param('course_id', ${course_id});
\$progress_request->set_param('lesson_id', ${lesson_id});
\$progress_response = rest_do_request(\$progress_request);
if (is_wp_error(\$progress_response) || \$progress_response->get_status() !== 200) {
    fwrite(STDERR, 'Progress REST write failed' . PHP_EOL);
    exit(1);
}

\$progress_get_request = new WP_REST_Request('GET', '/academy/v1/progress/${course_id}');
\$progress_get_response = rest_do_request(\$progress_get_request);
if (is_wp_error(\$progress_get_response) || \$progress_get_response->get_status() !== 200) {
    fwrite(STDERR, 'Progress REST read failed' . PHP_EOL);
    exit(1);
}
\$progress_data = \$progress_get_response->get_data();
if (empty(\$progress_data['items']) || (int) \$progress_data['items'][0]['lesson_id'] !== ${lesson_id}) {
    fwrite(STDERR, 'Progress row not found' . PHP_EOL);
    exit(1);
}

global \$wpdb;
\$audit_table = \$wpdb->prefix . 'academy_audit_log';
\$audit_count = (int) \$wpdb->get_var('SELECT COUNT(*) FROM ' . \$audit_table);
if (\$audit_count < 2) {
    fwrite(STDERR, 'Audit log check failed' . PHP_EOL);
    exit(1);
}

echo 'REST, progress, and audit checks OK' . PHP_EOL;
"

echo '[OK] Runtime smoke test passed.'
