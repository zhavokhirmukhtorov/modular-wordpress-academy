# Modular WordPress Academy

A compact **WordPress/PHP backend engineering sample** built to demonstrate modular plugin architecture, REST permissions, custom MySQL tables, WooCommerce-driven enrollment, audit logging, and production-readiness thinking.

This is deliberately **not a page-builder showcase**. The interesting part is the backend: three custom plugins share business workflows while keeping module boundaries explicit and reviewable.

## Reviewer guide — 3 minutes

If you are reviewing this repository as a technical sample, start here:

1. [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — module/data boundaries.
2. [`plugins/academy-core`](plugins/academy-core) — access, roles, audit log, WooCommerce enrollment.
3. [`plugins/academy-progress/includes/class-academy-progress-rest.php`](plugins/academy-progress/includes/class-academy-progress-rest.php) — protected write flow and business validation.
4. [`docs/AUDIT_REPORT.md`](docs/AUDIT_REPORT.md) — risks intentionally left visible instead of being presented as “production-ready”.
5. [`docs/VERIFICATION.md`](docs/VERIFICATION.md) — what has and has not actually been verified.

## System overview

```text
WooCommerce order completed
          |
          v
+----------------------+       +----------------------+
|     Academy Core     |------>|  academy_access DB   |
| roles/access/audit   |       +----------------------+
+----------+-----------+
           |
           | public access contract
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

## Modules

### Academy Core

- student/instructor roles and custom capabilities;
- `wp_academy_access` table with a unique user/course constraint;
- `wp_academy_audit_log` for business-significant events;
- WooCommerce product → course mapping;
- enrollment after a completed registered-user order;
- protected access REST endpoint;
- stable helper contract used by other plugins.

### Academy Courses

- `academy_course` and `academy_lesson` content models;
- lesson → course relation and ordering;
- public course catalog endpoint;
- protected lesson-list endpoint for enrolled users/admins;
- explicit dependency guard on Academy Core.

### Academy Progress

- `wp_academy_progress` custom table;
- idempotent completion writes via a unique `(user_id, lesson_id)` key;
- protected progress REST endpoints;
- validation that the lesson is published and belongs to the selected course;
- small admin reporting screen with recent audit events;
- explicit dependency guard on Academy Core.

## REST API

```text
GET  /wp-json/academy/v1/courses
GET  /wp-json/academy/v1/courses/{id}/lessons
GET  /wp-json/academy/v1/access/{user_id}
POST /wp-json/academy/v1/progress/complete
GET  /wp-json/academy/v1/progress/{course_id}
```

See [`docs/API.md`](docs/API.md) for permissions, payloads, and error behavior.

## Engineering controls demonstrated

- server-side capability/authentication checks on protected endpoints;
- WooCommerce admin write protected by capability + nonce;
- `$wpdb->prepare()` for request-derived SQL values;
- unique database constraints for idempotent access/progress writes;
- validation of lesson publication state and lesson/course relationship;
- escaped admin output;
- audit trail for enrollment/progress events;
- plugin-to-plugin communication through stable helpers/actions instead of reaching into internals;
- documented release/rollback process rather than direct production editing.

## Verification

Run:

```bash
bash scripts/verify.sh
```

The repository has passed PHP syntax checks and repository guard checks in the current development environment. That **does not mean the project is claimed as production-verified**.

Runtime WordPress/WooCommerce validation remains explicitly listed in [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md) and [`docs/VERIFICATION.md`](docs/VERIFICATION.md).

## Local setup — no paid infrastructure required

### Option A — LocalWP

1. Create a clean local WordPress site.
2. Copy the three directories from `plugins/` into `wp-content/plugins/`.
3. Activate plugins in this order: **Academy Core → Academy Courses → Academy Progress**.
4. Optionally install the free WooCommerce plugin to test enrollment.
5. Follow [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md).

### Option B — Docker

For local development only:

```bash
docker compose up -d
```

WordPress is exposed on `http://localhost:8080`.

The credentials in `docker-compose.yml` are intentionally local-development defaults and must not be reused in production.

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — boundaries and data model.
- [`docs/API.md`](docs/API.md) — REST contract.
- [`docs/AUDIT_REPORT.md`](docs/AUDIT_REPORT.md) — prioritized technical findings.
- [`docs/SAMPLE_AUDIT_RU.md`](docs/SAMPLE_AUDIT_RU.md) — the same audit style in Russian.
- [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md) — runtime acceptance scenarios.
- [`docs/RUNBOOK.md`](docs/RUNBOOK.md) — release and rollback procedure.
- [`docs/VERIFICATION.md`](docs/VERIFICATION.md) — evidence boundary: verified vs. not yet verified.
- [`SECURITY.md`](SECURITY.md) — implemented and remaining security controls.

## Scope statement

This is a **self-directed portfolio engineering project**, not a commercial LMS claim and not a replacement for LearnDash/BuddyBoss. Its purpose is to demonstrate how I approach a WordPress/PHP system with multiple custom modules: understand boundaries, trace business-critical flows, review database/API/security behavior, make incremental changes, and keep production risks explicit.
