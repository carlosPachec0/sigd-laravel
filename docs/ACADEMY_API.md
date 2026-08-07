# Academy Management API

Reference for frontend integration with the SIGD academy CRUD API.

## Base URL

```
{API_BASE_URL}/api/v1
```

Ask backend for the actual host per environment (local/staging/production).

## Overview

The academy feature provides full CRUD over the authenticated user's own academies. Behavior key points:

- **Every endpoint requires `Authorization: Bearer {token}`** (Laravel Sanctum). Unauthenticated calls return `401`.
- **Owner-scoped**: a user can only list, view, update, or delete the academies where `user_id` equals their own id.
  - `POST` sets `user_id` automatically from the authenticated user — you never send it.
  - `GET/PUT/DELETE /academies/{id}` returns `404 "Academy not found."` when the academy does not exist **or** belongs to another user (the two cases are indistinguishable to avoid leaking existence).
- Deleting uses a **soft delete** — the row is hidden but not physically removed.

## Response envelope

Same shape as the auth API:

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
| 404 | Academy not found or owned by another user |
| 422 | Validation failed |
| 500 | Unexpected server error |

---

## Endpoints

### `GET /academies` 🔒

List the authenticated user's academies (newest first).

**Success — `200`** — `data` is an array:

```json
{
  "message": "Academies retrieved successfully.",
  "data": [
    {
      "id": 1,
      "user_id": "9f1c2e2a-....",
      "name": "Judo Club",
      "discipline": "Judo",
      "registration_fee": "50.00",
      "monthly_fee": "25.00",
      "class_fee": "10.00",
      "created_at": "2026-08-04T12:00:00.000000Z",
      "updated_at": "2026-08-04T12:00:00.000000Z"
    }
  ],
  "status": 200,
  "errors": []
}
```

Empty result returns an empty array (`"data": []`).

**Errors**: `401` unauthenticated.

---

### `POST /academies` 🔒

Create an academy owned by the authenticated user.

**Body**

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 100 |
| `discipline` | string | required, max 100 |
| `registration_fee` | number | required, min 0 |
| `monthly_fee` | number | required, min 0 |
| `class_fee` | number | required, min 0 |

**Success — `201`** — same object shape as `GET /academies`:

```json
{
  "message": "Academy created successfully.",
  "data": {
    "id": 1,
    "user_id": "9f1c2e2a-....",
    "name": "Karate Dojo",
    "discipline": "Karate",
    "registration_fee": "60.00",
    "monthly_fee": "30.00",
    "class_fee": "15.00",
    "created_at": "2026-08-04T12:00:00.000000Z",
    "updated_at": "2026-08-04T12:00:00.000000Z"
  },
  "status": 201,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `422` validation.

---

### `GET /academies/{id}` 🔒

Show a single academy owned by the authenticated user.

**Success — `200`** — `data` is one academy object (same shape as above).

**Errors**: `401` unauthenticated, `404` not found / owned by another user.

---

### `PUT /academies/{id}` 🔒

Update an academy owned by the authenticated user. Fields are **optional** (`sometimes` rule) — send only the fields you want to change.

**Body** (all optional, but validated if present)

| Field | Type | Rules |
|---|---|---|
| `name` | string | max 100 |
| `discipline` | string | max 100 |
| `registration_fee` | number | min 0 |
| `monthly_fee` | number | min 0 |
| `class_fee` | number | min 0 |

**Success — `200`** — returns the fully updated academy object.

```json
{
  "message": "Academy updated successfully.",
  "data": {
    "id": 1,
    "user_id": "9f1c2e2a-....",
    "name": "Karate Dojo",
    "discipline": "Karate",
    "registration_fee": "70.00",
    "monthly_fee": "35.00",
    "class_fee": "20.00",
    "created_at": "2026-08-04T12:00:00.000000Z",
    "updated_at": "2026-08-04T12:00:00.000000Z"
  },
  "status": 200,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` not found / owned by another user, `422` validation.

---

### `DELETE /academies/{id}` 🔒

Soft-delete an academy owned by the authenticated user.

**Success — `204`**

```json
{
  "message": "Academy deleted successfully.",
  "data": null,
  "status": 204,
  "errors": []
}
```

**Errors**: `401` unauthenticated, `404` not found / owned by another user.

---

## Quick reference

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/academies` | 🔒 | list own, newest first |
| POST | `/academies` | 🔒 | create own |
| GET | `/academies/{id}` | 🔒 | view own |
| PUT | `/academies/{id}` | 🔒 | partial update own |
| DELETE | `/academies/{id}` | 🔒 | soft delete own |

🔒 = requires `Authorization: Bearer {token}`. Owner-scoping means all reads/writes are confined to the authenticated user's own academies; foreign academy ids are reported as `404`.