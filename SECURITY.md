# Security notes

This repository is a compact portfolio engineering project, not a production LMS. Security controls are implemented where they are meaningful for the demonstrated flows, and unverified production claims are intentionally avoided.

## Controls implemented

- REST write/read routes use authentication and/or capability checks.
- WooCommerce product-to-course mapping verifies both capability and nonce.
- Request-derived SQL values are parameterized with `$wpdb->prepare()`.
- Course access and lesson completion use unique database constraints for idempotency.
- Lesson completion validates post type, publication state, course relation, and course access.
- Admin reporting requires the `manage_academy` capability and escapes rendered output.
- Business-significant events are written to a dedicated audit log.

## Production controls still required

Before adapting this design to a real platform, I would add and verify:

1. WordPress/WooCommerce integration tests.
2. Centralized error monitoring and alerting.
3. Rate limiting for write endpoints.
4. Backup restore tests and a documented rollback procedure.
5. Staging-based release verification.
6. Load tests with realistic data volumes.
7. Audit-log retention/archival policy.

The detailed findings and priority order are documented in [`docs/AUDIT_REPORT.md`](docs/AUDIT_REPORT.md).
