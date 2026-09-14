# Architecture

## Design goal

Keep business responsibilities separated so one plugin can evolve without reaching into private internals of another plugin.

```text
WooCommerce order completed
        |
        v
+-------------------+
|   Academy Core    |
| access + logging  |
+---------+---------+
          |
          | academy_user_has_course_access()
          v
+-------------------+       REST        +-------------------+
| Academy Courses   | <---------------- | frontend / client |
| courses + lessons |                   +-------------------+
+---------+---------+
          |
          | lesson relationship validation
          v
+-------------------+
| Academy Progress  |
| completion/report |
+---------+---------+
          |
          | academy_lesson_completed action
          v
+-------------------+
|   Audit Log DB    |
+-------------------+
```

## Data model

| Store | Purpose | Important constraint/index |
|---|---|---|
| `wp_posts` | courses and lessons | native WordPress content model |
| `wp_postmeta` | lesson→course/order and product→course mapping | adequate for compact sample; profile before scaling |
| `wp_academy_access` | granted/revoked course access | unique `(user_id, course_id)` |
| `wp_academy_progress` | per-user lesson completion | unique `(user_id, lesson_id)` |
| `wp_academy_audit_log` | operational/business events | indexes on event, level, created_at |

## Module contracts

### Core → other modules

`academy_user_has_course_access(int $user_id, int $course_id): bool`

The courses/progress modules use this stable public helper rather than querying the access table directly.

### Progress → observers

`academy_lesson_completed` action is emitted after a successful completion write. Other modules can react without changing the progress component.

### WooCommerce → Core

The WooCommerce integration is optional. Hooks are registered safely; if WooCommerce is not active, the mapping UI/order integration simply does not execute.

## Trust boundaries

- public catalog endpoint: published course metadata only;
- protected lesson endpoint: requires enrollment or `manage_academy`;
- progress writes: require login, active access, published lesson, and correct lesson/course relation;
- admin report: requires `manage_academy`;
- WooCommerce mapping write: requires post edit capability and nonce.

## Why custom tables are used

Access and progress are transactional/business state. Unique keys provide a database-level idempotency guarantee that is clearer and more efficient than relying only on post meta for these writes.

## Known scaling boundary

Lesson→course relation currently uses post meta. That is an intentional simplicity choice for the sample. A real high-volume installation should be profiled first; if the query becomes a measured bottleneck, move the relation/order to a dedicated indexed table rather than optimizing speculatively.
