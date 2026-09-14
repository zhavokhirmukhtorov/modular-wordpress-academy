# REST API

Base namespace: `academy/v1`.

The API is intentionally small. Public catalog data is separate from protected enrollment/progress data.

## `GET /courses`

Public list of published courses.

**Response**

```json
{
  "items": [
    {
      "id": 42,
      "title": "Backend Fundamentals",
      "excerpt": "...",
      "url": "https://example.test/courses/backend-fundamentals/"
    }
  ]
}
```

## `GET /courses/{course_id}/lessons`

Returns published lessons only when the current user has access to the course or has the `manage_academy` capability.

**Authentication:** WordPress authenticated session / REST nonce.

## `GET /access/{user_id}`

Returns active course access for the requested user.

Allowed when:

- the current user requests their own access; or
- the current user has `manage_academy`.

## `POST /progress/complete`

Marks one lesson as completed for the current user.

```json
{
  "course_id": 42,
  "lesson_id": 101
}
```

The write is accepted only when:

1. the user is authenticated;
2. the user has active course access;
3. the lesson exists and is published;
4. the lesson belongs to the selected course.

A database unique key on `(user_id, lesson_id)` makes repeated completion calls idempotent.

## `GET /progress/{course_id}`

Returns the current user's recorded lesson progress for a course they can access.

## Error model

WordPress REST errors are returned with stable error codes such as:

- `academy_no_access` — HTTP 403;
- `academy_bad_lesson` — HTTP 400;
- `academy_db_error` — HTTP 500.

## Security note

WordPress REST cookie authentication requires the standard REST nonce for state-changing browser requests. The endpoint also performs server-side access and relationship validation; the nonce is not treated as authorization by itself.
