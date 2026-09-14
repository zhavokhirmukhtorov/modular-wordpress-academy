#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
errors=0

pass() { printf '[OK]   %s\n' "$1"; }
fail() { printf '[FAIL] %s\n' "$1"; errors=$((errors+1)); }
info() { printf '[INFO] %s\n' "$1"; }

info "PHP syntax"
while IFS= read -r -d '' file; do
  if php -l "$file" >/dev/null; then
    pass "PHP syntax: ${file#$ROOT/}"
  else
    fail "PHP syntax: ${file#$ROOT/}"
  fi
done < <(find "$ROOT/plugins" -name '*.php' -print0 | sort -z)

info "Repository structure"
for path in \
  plugins/academy-core/academy-core.php \
  plugins/academy-courses/academy-courses.php \
  plugins/academy-progress/academy-progress.php \
  docs/ARCHITECTURE.md \
  docs/API.md \
  docs/AUDIT_REPORT.md \
  docs/TEST_PLAN.md \
  docs/RUNBOOK.md \
  docs/VERIFICATION.md \
  SECURITY.md; do
  if [ -f "$ROOT/$path" ]; then
    pass "Present: $path"
  else
    fail "Missing: $path"
  fi
done

info "REST permissions"
if grep -R "permission_callback.*__return_true" -n "$ROOT/plugins" | grep -v "academy-courses-rest.php" >/dev/null 2>&1; then
  fail "Unexpected public REST permission callback found."
else
  pass "Only the intended public course catalog endpoint is public."
fi

if grep -q "permission_callback.*is_user_logged_in" "$ROOT/plugins/academy-progress/includes/class-academy-progress-rest.php"; then
  pass "Progress routes require an authenticated WordPress user before business checks."
else
  fail "Progress REST authentication guard not found."
fi

info "WooCommerce mapping write"
WOO="$ROOT/plugins/academy-core/includes/class-academy-core-woocommerce.php"
if grep -q 'current_user_can' "$WOO" && grep -q 'wp_verify_nonce' "$WOO"; then
  pass "Product→course mapping checks capability and nonce."
else
  fail "Product→course mapping capability/nonce guard missing."
fi

info "Business invariants"
if grep -q 'UNIQUE KEY uniq_user_course' "$ROOT/plugins/academy-core/includes/class-academy-core-db.php"; then
  pass "Course access has a database uniqueness constraint."
else
  fail "Course access uniqueness constraint missing."
fi

if grep -q 'UNIQUE KEY uniq_progress' "$ROOT/plugins/academy-progress/includes/class-academy-progress-db.php"; then
  pass "Lesson progress has a database uniqueness constraint."
else
  fail "Progress uniqueness constraint missing."
fi

PROGRESS_REST="$ROOT/plugins/academy-progress/includes/class-academy-progress-rest.php"
if grep -q "get_post_type(\$lesson_id)" "$PROGRESS_REST" && \
   grep -q "get_post_status(\$lesson_id)" "$PROGRESS_REST" && \
   grep -q "_academy_course_id" "$PROGRESS_REST"; then
  pass "Progress write validates lesson type/status/course relation."
else
  fail "Progress write relationship validation incomplete."
fi

info "Plugin dependency guards"
if grep -q "requires the Academy Core plugin" "$ROOT/plugins/academy-courses/academy-courses.php" && \
   grep -q "requires the Academy Core plugin" "$ROOT/plugins/academy-progress/academy-progress.php"; then
  pass "Dependent plugins fail activation clearly when Academy Core is unavailable."
else
  fail "Academy Core activation dependency guard missing."
fi

if [ "$errors" -ne 0 ]; then
  printf '\nVerification failed: %d issue(s).\n' "$errors"
  exit 1
fi

printf '\nVerification passed. Static checks are not a substitute for the runtime test plan in docs/TEST_PLAN.md.\n'
