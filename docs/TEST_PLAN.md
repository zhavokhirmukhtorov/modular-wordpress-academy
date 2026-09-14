# Runtime Test Plan

This repository includes static verification, but the following runtime checks should be completed in a real WordPress + WooCommerce environment before any production use.

## Activation

1. Activate `Academy Core`.
2. Activate `Academy Courses`.
3. Activate `Academy Progress`.
4. Confirm the three custom tables exist:
   - `wp_academy_access`
   - `wp_academy_audit_log`
   - `wp_academy_progress`
5. Confirm administrator has `manage_academy` and `view_academy_reports`.

## Content and permissions

1. Create one course and two lessons.
2. Associate both lessons with the course.
3. As an anonymous user, verify `/academy/v1/courses` is readable.
4. As a user without enrollment, verify the lessons endpoint returns 403.
5. As an administrator, verify the lessons endpoint is readable.

## WooCommerce enrollment

1. Install/activate WooCommerce.
2. Create a product and map it to the course.
3. Complete an order as a registered test customer.
4. Verify one active access row is created.
5. Re-run the completed-order hook and verify access is not duplicated.
6. Verify `course_access_granted` appears in the audit log.
7. Complete a guest order and verify it is skipped and logged explicitly.

## Progress

1. As the enrolled student, mark lesson 1 complete.
2. Repeat the same request and verify there is still one progress row.
3. Attempt to mark a lesson from another course complete and verify HTTP 400.
4. Remove/revoke access in a test fixture and verify progress endpoints return HTTP 403.

## Administration

1. Open **Academy Reports** as administrator.
2. Verify aggregate counts match fixture data.
3. Verify recent audit entries render escaped context.
4. Verify a non-privileged user cannot access the report page.

## Operational checks before production

- backup and restore test;
- staging deployment and rollback rehearsal;
- rate-limit test for write endpoints;
- realistic data-volume/load test;
- centralized PHP/HTTP error monitoring;
- retention policy for audit logs.
