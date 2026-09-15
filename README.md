# Modular WordPress Academy

A small backend-focused WordPress project built around three custom plugins: access/enrollment, course content, and progress tracking.

I built it to keep the interesting parts visible: plugin boundaries, custom tables, REST permissions, WooCommerce integration, audit events, and the checks I would want before changing a larger existing system. It is intentionally compact, so the important flows can be reviewed without digging through unrelated UI code.

> This is a self-directed portfolio project, not a commercial LMS or a claim of production deployment experience.

## What is in the repository

### Academy Core

Owns the shared access model and operational logging.

- student/instructor roles and custom capabilities;
- `wp_academy_access` with a unique user/course constraint;
- `wp_academy_audit_log` for business-significant events;
- WooCommerce product-to-course mapping;
- enrollment when a registered user's order is completed;
- protected REST endpoint for course access.

### Academy Courses

Owns the content model.

- `academy_course` and `academy_lesson` post types;
- lesson-to-course relation and lesson order;
- public course catalog endpoint;
- protected lesson endpoint for enrolled users/admins;
- explicit dependency on Academy Core.

### Academy Progress

Owns completion state and a small reporting view.

- `wp_academy_progress` custom table;
- idempotent writes with a unique `(user_id, lesson_id)` key;
- protected progress read/write endpoints;
- validation that a lesson is published and belongs to the requested course;
- audit event on completion;
- explicit dependency on Academy Core.

## Architecture at a glance

```text
WooCommerce completed order
          |
          v
+----------------------+       +----------------------+
|     Academy Core     |------>|  academy_access DB   |
| roles/access/audit   |       +----------------------+
+----------+-----------+
           |
           | shared access contract
           v
+----------------------+       +----------------------+
|   Academy Courses    |------>| WP courses/lessons   |
| content + read API   |       +----------------------+
+----------+-----------+
           |
           | lesson belongs to course
           v
+----------------------+       +----------------------+
|   Academy Progress   |------>| academy_progress DB  |
| write API + reports  |       +----------------------+
+----------+-----------+
           |
           v
+----------------------+
|   academy_audit_log  |
+----------------------+
```

A rendered diagram is available in [`docs/architecture.svg`](docs/architecture.svg).

## REST API

```text
GET  /wp-json/academy/v1/courses
GET  /wp-json/academy/v1/courses/{id}/lessons
GET  /wp-json/academy/v1/access/{user_id}
POST /wp-json/academy/v1/progress/complete
GET  /wp-json/academy/v1/progress/{course_id}
```

See [`docs/API.md`](docs/API.md) for permissions and payload details.

## Controls I wanted to make explicit

- authentication/capability checks on protected endpoints;
- capability + nonce protection on the WooCommerce admin write path;
- prepared SQL for request-derived values;
- unique constraints for idempotent access/progress writes;
- validation of lesson type, publication state, and course relation;
- escaping on admin output;
- audit events for enrollment and lesson completion;
- plugin-to-plugin communication through helpers/actions instead of reaching into private internals.

## Verification

There are two GitHub Actions workflows.

**Static verification** runs the repository checks on PHP **8.0, 8.1, 8.2, 8.3 and 8.4**. It includes PHP syntax checks and a small set of guards around REST permissions, nonce/capability checks, database constraints, validation, and plugin dependencies.

**Runtime smoke test** starts a fresh MariaDB + WordPress environment, installs WooCommerce, activates all three Academy plugins, creates test data, completes a WooCommerce order, and verifies the critical path end to end:

```text
WordPress install
→ WooCommerce activation
→ Academy plugin activation
→ completed order
→ course access granted
→ protected REST access
→ progress write/read
→ audit log entry
```

The current `main` branch passes both workflows. See [`docs/VERIFICATION.md`](docs/VERIFICATION.md) for the exact evidence boundary.

Local static check:

```bash
bash scripts/verify.sh
```

Runtime check (requires Docker):

```bash
bash scripts/runtime-smoke.sh
```

## What I still do not call “production-verified”

A green smoke test is useful evidence, but it is not a substitute for production validation. This project still does not claim:

- load/performance numbers under realistic traffic;
- cross-browser/admin UX coverage across WordPress versions;
- backup/restore evidence;
- production monitoring and alerting;
- a full WordPress/WooCommerce integration test suite;
- deactivation/uninstall and long-running operational behavior.

The prioritized gaps are documented in [`docs/AUDIT_REPORT.md`](docs/AUDIT_REPORT.md).

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) - module/data boundaries
- [`docs/API.md`](docs/API.md) - REST contract
- [`docs/AUDIT_REPORT.md`](docs/AUDIT_REPORT.md) - prioritized findings
- [`docs/SAMPLE_AUDIT_RU.md`](docs/SAMPLE_AUDIT_RU.md) - audit example in Russian
- [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md) - runtime scenarios
- [`docs/RUNBOOK.md`](docs/RUNBOOK.md) - release/rollback notes
- [`docs/VERIFICATION.md`](docs/VERIFICATION.md) - what is verified and what is not
- [`SECURITY.md`](SECURITY.md) - implemented and remaining security controls

## Scope

This repository is deliberately small. The goal is not to imitate a full LMS feature set; it is to show how I structure and verify backend changes in a modular WordPress/PHP system: understand ownership and dependencies, protect write paths, keep data rules explicit, test the critical business flow, and document what remains uncertain.
