# Verification Scope

## Verified in the current package

- PHP 8.4 syntax check (`php -l`) passes for every PHP source file.
- Repository verification script passes after the final dependency/security documentation changes.
- No unexpected public REST permission callback was found by the repository guard script.
- WooCommerce product-to-course write path includes capability and nonce verification.
- Request-derived SQL values use `$wpdb->prepare()` in the implemented REST/access flows.
- Course access and lesson completion are protected by unique database constraints.
- Lesson completion validates the post type, published state, and lesson-to-course relationship.
- Academy Courses and Academy Progress include explicit activation guards for the Academy Core dependency.

## Not yet claimed as verified

- Real WordPress activation/deactivation lifecycle.
- WooCommerce checkout/order behavior in a live runtime.
- Browser/admin UX compatibility across WordPress versions.
- Load/performance figures under realistic traffic.
- Backup/restore and production rollback.
- Automated WordPress integration test suite.

These are intentionally listed as remaining validation steps rather than presented as completed work.
