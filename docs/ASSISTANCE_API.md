# Assistance (Attendance) API

Reference for frontend integration with the SIGD assistance/attendance CRUD API.

## Base URL

```
{API_BASE_URL}/api/v1
```

Ask backend for the actual host per environment (local/staging/production).

## Overview

An assistance record marks that a student attended on a given date — creating the record **is** the attendance mark; there is no `status` field for absences. Behavior key points:

- **Every endpoint requires `Authorization: Bearer {token}`** (Laravel Sanctum). Unauthenticated calls return `401`.
- **Student-owned, nested two levels deep**: an assistance record always belongs to exactly one student, which belongs to exactly one academy owned by the authenticated user.
  - `POST` sets `student_id` automatically from the URL — you never send it.
  - `GET/POST /academies/{academyId}/students/{studentId}/assistance` returns `404 "Academy not found."` when the academy does not exist **or** belongs to another user, and `404 "Student not found."` when the student does not exist in that academy.
  - `GET/PUT/DELETE /academies/{academyId}/students/{studentId}/assistance/{assistanceId}` returns `404 "Assistance record not found."` when the record does not exist for that student (including records of a different student, even one owned by the same academy/user).
- **Unique per student per date**: a student can only have one assistance record for a given `date`. Creating a duplicate, or updating a record's `date` to one already used by the same student, returns `409 "An assistance record already exists for this student on this date."`.
- `id` is an **integer** (like academy/student ids).
- Deleting is a **hard delete** — the row is permanently removed, there is no soft-delete for assistance.

## Response envelope

Same shape as the rest of the API:

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
| 404 | Academy/student/assistance record not found or not owned |
| 409 | An assistance record already exists for this student on this date |
| 422 | Validation failed |
| 500 | Unexpected server error |

---

## Endpoints

### `GET /academies/{academyId}/students/{studentId}/assistance` 🔒

List the assistance records of the given student (most recent date first). The student must belong to an academy owned by the authenticated user.

**Success — `200`** — `data` is an array:

```json
{
  "message": "Assistance records retrieved successfully.",
  "data": [
    {
      "id": "1",
      "student_id": "1",
      "date": "2026-08-20",
      "created_at": "2026-08-20T12:00:00.000000Z",
      "updated_at": "2026-08-20T12:00:00.000000Z"
    }
  ],
  "status": 200,
  "errors": []
}
```

Empty result returns an empty array (`"data": []`).

**Errors**: `401` unauthenticated, `404` academy not found / not owned, `404` student not found / not owned.

---

### `POST /academies/{academyId}/students/{studentId}/assistance` 🔒

Mark attendance for the given student on a given date.

**Body**

| Field | Type | Rules |
|---|---|---|
| `date` | date (`YYYY-MM-DD`) | required |

**Success — `201`** — same object shape as `GET .../assistance`:

```json
{
  "message": "Assistance record created successfully.",
  "data": {
    "id": "1",
    "student_id": "1",
    "date": "2026-08-20",
    "created_at": "2026-08-20T12:00:00.000000Z",
    "updated_at": "2026-08-20T12:00:00.000000Z"
  },
  "status": 201,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy or student not found / not owned, `409` a record already exists for this student on this date, `422` validation.

---

### `GET /academies/{academyId}/students/{studentId}/assistance/{assistanceId}` 🔒

Show a single assistance record of the given student.

**Success — `200`** — `data` is one assistance object (same shape as above).

**Errors**: `401` unauthenticated, `404` academy, student, or assistance record not found / not owned.

---

### `PUT /academies/{academyId}/students/{studentId}/assistance/{assistanceId}` 🔒

Update the date of an assistance record. Fields are **optional** (`sometimes` rule) — send only the fields you want to change.

**Body** (all optional, but validated if present)

| Field | Type | Rules |
|---|---|---|
| `date` | date (`YYYY-MM-DD`) | date |

**Success — `200`** — returns the fully updated assistance object.

```json
{
  "message": "Assistance record updated successfully.",
  "data": {
    "id": "1",
    "student_id": "1",
    "date": "2026-08-21",
    "created_at": "2026-08-20T12:00:00.000000Z",
    "updated_at": "2026-08-21T09:00:00.000000Z"
  },
  "status": 200,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy, student, or assistance record not found / not owned, `409` the new date is already used by another record of this student, `422` validation.

---

### `DELETE /academies/{academyId}/students/{studentId}/assistance/{assistanceId}` 🔒

Permanently delete an assistance record of the given student (hard delete — not recoverable).

**Success — `204`**

```json
{
  "message": "Assistance record deleted successfully.",
  "data": null,
  "status": 204,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy, student, or assistance record not found / not owned.

---

## Quick reference

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/academies/{academyId}/students/{studentId}/assistance` | 🔒 | list a student's assistance records, most recent date first |
| POST | `/academies/{academyId}/students/{studentId}/assistance` | 🔒 | mark attendance for a student on a date |
| GET | `/academies/{academyId}/students/{studentId}/assistance/{assistanceId}` | 🔒 | view one assistance record |
| PUT | `/academies/{academyId}/students/{studentId}/assistance/{assistanceId}` | 🔒 | change the date of one assistance record |
| DELETE | `/academies/{academyId}/students/{studentId}/assistance/{assistanceId}` | 🔒 | permanently delete one assistance record |

🔒 = requires `Authorization: Bearer {token}`. Assistance records are always scoped to a student of the authenticated user's own academies; foreign academy, student, or assistance ids are reported as `404`. A duplicate `student_id` + `date` is reported as `409`.
