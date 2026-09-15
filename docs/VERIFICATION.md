# Verification Scope

This document separates checks that are reproducible in the repository from areas that still need broader production validation.

## Reproducibly verified on the current `main` branch

### Static verification

GitHub Actions runs `scripts/verify.sh` on PHP **8.0, 8.1, 8.2, 8.3 and 8.4**.

The workflow currently verifies:

- `php -l` passes for every PHP source file;
- no unexpected public REST permission callback is present;
- the WooCommerce product-to-course write path contains both capability and nonce verification;
- request-derived SQL in the implemented access/progress flows is prepared;
- access and lesson completion use unique database constraints for idempotency;
- lesson completion validates post type, published state, and lesson-to-course relation;
- Academy Courses and Academy Progress have explicit Academy Core dependency guards.

### Runtime smoke test

A separate GitHub Actions workflow starts a fresh Docker environment with MariaDB, WordPress and WP-CLI, then performs the critical application flow.

The current smoke test successfully verifies:

1. WordPress installs against a fresh database.
2. WooCommerce installs and activates.
3. Academy Core, Academy Courses and Academy Progress activate successfully.
4. A test student, course, lesson and WooCommerce product can be created.
5. Completing a WooCommerce order grants the student access to the mapped course.
6. The protected access REST endpoint returns the enrollment for the authenticated student.
7. The protected progress endpoint accepts a valid lesson completion.
8. The progress read endpoint returns the saved lesson.
9. The operational audit log contains the expected business events.

The successful runtime job ends with:

```text
Enrollment OK
REST, progress, and audit checks OK
[OK] Runtime smoke test passed.
```

## What this evidence does not prove

The repository is **not** presented as production-verified. The following remain outside the current automated evidence:

- realistic load/performance behaviour;
- browser/admin UX coverage across WordPress and WooCommerce versions;
- backup restore testing and disaster recovery;
- production monitoring/alerting;
- a comprehensive WordPress/WooCommerce integration and regression suite;
- deactivation/uninstall behaviour and long-running operational effects;
- external delivery infrastructure such as SMTP.

These are intentionally kept visible rather than implied by a green CI badge.
