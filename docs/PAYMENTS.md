# Payment Management API

Reference for frontend integration with the SIGD payment CRUD API.

## Base URL

```
{API_BASE_URL}/api/v1
```

Ask backend for the actual host per environment (local/staging/production).

## Overview

Payments record a student's manually-registered payment (e.g. a monthly fee or registration fee paid in person/by transfer). There is **no automatic/gateway integration** — every payment is created explicitly by the academy owner through this API. Behavior key points:

- **Every endpoint requires `Authorization: Bearer {token}`** (Laravel Sanctum). Unauthenticated calls return `401`.
- **Student-owned, nested two levels deep**: a payment always belongs to exactly one student, which belongs to exactly one academy owned by the authenticated user.
  - `POST` sets `student_id` automatically from the URL — you never send it. A payment cannot exist without a student.
  - `GET/POST /academies/{academyId}/students/{studentId}/payments` returns `404 "Academy not found."` when the academy does not exist **or** belongs to another user, and `404 "Student not found."` when the student does not exist in that academy.
  - `GET/PUT/DELETE /academies/{academyId}/students/{studentId}/payments/{paymentId}` returns `404 "Payment not found."` when the payment does not exist for that student (including payments of a different student, even one owned by the same academy/user).
- `id` is a **UUID** (unlike academy/student ids, which are integers).
- Deleting is a **hard delete** — unlike academy/student, there is no soft-delete for payments; the row is permanently removed.

## Response envelope

Same shape as the academy/student API:

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
| 404 | Academy/student/payment not found or not owned |
| 422 | Validation failed |
| 500 | Unexpected server error |

---

## Endpoints

### `GET /academies/{academyId}/students/{studentId}/payments` 🔒

List the payments of the given student (newest first). The student must belong to an academy owned by the authenticated user.

**Success — `200`** — `data` is an array:

```json
{
  "message": "Payments retrieved successfully.",
  "data": [
    {
      "id": "0199abc1-2d3e-7f4a-9b1c-1234567890ab",
      "student_id": "1",
      "subject": "Monthly fee",
      "amount": "30.00",
      "created_at": "2026-08-22T12:00:00.000000Z",
      "updated_at": "2026-08-22T12:00:00.000000Z"
    }
  ],
  "status": 200,
  "errors": []
}
```

Empty result returns an empty array (`"data": []`).

**Errors**: `401` unauthenticated, `404` academy not found / not owned, `404` student not found / not owned.

---

### `POST /academies/{academyId}/students/{studentId}/payments` 🔒

Register a payment for the given student. This is a **manual** registration — there is no payment gateway behind it, the amount is entered by the academy owner.

**Body**

| Field | Type | Rules |
|---|---|---|
| `subject` | string | required, max 100 |
| `amount` | number | required, min 0 |

**Success — `201`** — same object shape as `GET .../payments`:

```json
{
  "message": "Payment created successfully.",
  "data": {
    "id": "0199abc1-2d3e-7f4a-9b1c-1234567890ab",
    "student_id": "1",
    "subject": "Monthly fee",
    "amount": "30.00",
    "created_at": "2026-08-22T12:00:00.000000Z",
    "updated_at": "2026-08-22T12:00:00.000000Z"
  },
  "status": 201,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy or student not found / not owned, `422` validation.

---

### `GET /academies/{academyId}/students/{studentId}/payments/{paymentId}` 🔒

Show a single payment of the given student.

**Success — `200`** — `data` is one payment object (same shape as above).

**Errors**: `401` unauthenticated, `404` academy, student, or payment not found / not owned.

---

### `PUT /academies/{academyId}/students/{studentId}/payments/{paymentId}` 🔒

Update a payment of the given student. Fields are **optional** (`sometimes` rule) — send only the fields you want to change.

**Body** (all optional, but validated if present)

| Field | Type | Rules |
|---|---|---|
| `subject` | string | max 100 |
| `amount` | number | min 0 |

**Success — `200`** — returns the fully updated payment object.

```json
{
  "message": "Payment updated successfully.",
  "data": {
    "id": "0199abc1-2d3e-7f4a-9b1c-1234567890ab",
    "student_id": "1",
    "subject": "Monthly fee (August)",
    "amount": "35.00",
    "created_at": "2026-08-22T12:00:00.000000Z",
    "updated_at": "2026-08-22T12:05:00.000000Z"
  },
  "status": 200,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy, student, or payment not found / not owned, `422` validation.

---

### `DELETE /academies/{academyId}/students/{studentId}/payments/{paymentId}` 🔒

Permanently delete a payment of the given student (hard delete — not recoverable).

**Success — `204`**

```json
{
  "message": "Payment deleted successfully.",
  "data": null,
  "status": 204,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` academy, student, or payment not found / not owned.

---

## Quick reference

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/academies/{academyId}/students/{studentId}/payments` | 🔒 | list a student's payments, newest first |
| POST | `/academies/{academyId}/students/{studentId}/payments` | 🔒 | manually register a payment for a student |
| GET | `/academies/{academyId}/students/{studentId}/payments/{paymentId}` | 🔒 | view one payment |
| PUT | `/academies/{academyId}/students/{studentId}/payments/{paymentId}` | 🔒 | partial update one payment |
| DELETE | `/academies/{academyId}/students/{studentId}/payments/{paymentId}` | 🔒 | permanently delete one payment |

🔒 = requires `Authorization: Bearer {token}`. Payments are always scoped to a student of the authenticated user's own academies; foreign academy, student, or payment ids are reported as `404`.
