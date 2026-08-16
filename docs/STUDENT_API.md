# Student Management API

Reference for frontend integration with the SIGD student CRUD API.

## Base URL

```
{API_BASE_URL}/api/v1
```

Ask backend for the actual host per environment (local/staging/production).

## Overview

Students belong to exactly one academy and are managed through that academy. Behavior key points:

- **Every endpoint requires `Authorization: Bearer {token}`** (Laravel Sanctum). Unauthenticated calls return `401`.
- **Academy-owned**: a user can only manage students of academies where `user_id` equals their own id.
  - `POST` sets `academy_id` automatically from the URL — you never send it. A student cannot exist without an academy.
  - `GET/POST /academies/{academyId}/students` returns `404 "Academy not found."` when the academy does not exist **or** belongs to another user.
  - `GET/PUT/DELETE /academies/{academyId}/students/{studentId}` returns `404 "Student not found."` when the student does not exist in that academy (including students owned by a different academy of the same user).
- Deleting uses a **soft delete** — the row is hidden but not physically removed.

## Response envelope

Same shape as the academy API:

```json
{
  "message": "Human-readable summary.",
  "data": { "...": "..." },
  "status": 200,
  "errors": []
}
```

- `errors` is a flat array of strings. On validation failure (`422`) it's `$validator->errors()->all()`.

### Common statuses

| Status | Meaning |
|---|---|
| 401 | Missing/invalid/expired bearer token |
| 404 | Academy/student not found or not owned |
| 422 | Validation failed |
| 500 | Unexpected server error |

---

## Endpoints

### `GET /academies/{academyId}/students` 🔒

List the students of the authenticated user's academy (newest first).

**Success — `200`** — `data` is an array:

```json
{
  "message": "Students retrieved successfully.",
  "data": [
    {
      "id": 1,
      "academy_id": 1,
      "name": "Jane Doe",
      "gender": "Female",
      "birth_date": "2012-08-20",
      "height": "1.55",
      "weight": "48.00",
      "created_at": "2026-08-04T12:00:00.000000Z",
      "updated_at": "2026-08-04T12:00:00.000000Z"
    }
  ],
  "status": 200,
  "errors": []
}
```

Empty result returns an empty array (`"data": []`).

**Errors**: `401` unauthenticated, `404` academy not found / owned by another user.

---

### `POST /academies/{academyId}/students` 🔒

Create a student in the authenticated user's academy.

**Body**

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 100 |
| `gender` | string | required, `Male` or `Female` |
| `birth_date` | date | required, `Y-m-d` |
| `height` | number | optional, min 0 |
| `weight` | number | optional, min 0 |

**Success — `201`** — same object shape as `GET /academies/{academyId}/students`:

```json
{
  "message": "Student created successfully.",
  "data": {
    "id": 2,
    "academy_id": 1,
    "name": "John Doe",
    "gender": "Male",
    "birth_date": "2010-05-10",
    "height": "1.65",
    "weight": "55.50",
    "created_at": "2026-08-04T12:00:00.000000Z",
    "updated_at": "2026-08-04T12:00:00.000000Z"
  },
  "status": 201,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy not found / owned by another user, `422` validation.

---

### `GET /academies/{academyId}/students/{studentId}` 🔒

Show a single student of the authenticated user's academy.

**Success — `200`** — `data` is one student object (same shape as above).

**Errors**: `401` unauthenticated, `404` academy or student not found / not owned.

---

### `PUT /academies/{academyId}/students/{studentId}` 🔒

Update a student of the authenticated user's academy. Fields are **optional** (`sometimes` rule) — send only the fields you want to change.

**Body** (all optional, but validated if present)

| Field | Type | Rules |
|---|---|---|
| `name` | string | max 100 |
| `gender` | string | `Male` or `Female` |
| `birth_date` | date | `Y-m-d` |
| `height` | number | min 0 |
| `weight` | number | min 0 |

**Success — `200`** — returns the fully updated student object.

```json
{
  "message": "Student updated successfully.",
  "data": {
    "id": 2,
    "academy_id": 1,
    "name": "Johnathan Doe",
    "gender": "Male",
    "birth_date": "2010-05-10",
    "height": "1.70",
    "weight": "60.00",
    "created_at": "2026-08-04T12:00:00.000000Z",
    "updated_at": "2026-08-04T12:00:00.000000Z"
  },
  "status": 200,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy or student not found / not owned, `422` validation.

---

### `DELETE /academies/{academyId}/students/{studentId}` 🔒

Soft-delete a student of the authenticated user's academy.

**Success — `204`**

```json
{
  "message": "Student deleted successfully.",
  "data": null,
  "status": 204,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy or student not found / not owned.

---

## Quick reference

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/academies/{academyId}/students` | 🔒 | list own academy's students, newest first |
| POST | `/academies/{academyId}/students` | 🔒 | create student in own academy |
| GET | `/academies/{academyId}/students/{studentId}` | 🔒 | view own student |
| PUT | `/academies/{academyId}/students/{studentId}` | 🔒 | partial update own student |
| DELETE | `/academies/{academyId}/students/{studentId}` | 🔒 | soft delete own student |

🔒 = requires `Authorization: Bearer {token}`. Students are always scoped to the authenticated user's own academies; foreign academy or student ids are reported as `404`.
