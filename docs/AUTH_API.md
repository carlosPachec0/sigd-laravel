# Auth & User Management API

Reference for frontend integration with the SIGD authentication and profile API.

## Base URL

```
{API_BASE_URL}/api/v1
```

Ask backend for the actual host per environment (local/staging/production).

## Authentication model

This API uses **Laravel Sanctum personal access (bearer) tokens** — not cookies/sessions. There is no CSRF dance and no shared-domain requirement.

1. Call `signup` or `login`. The response includes a `token` string.
2. Store that token (e.g. in memory + a secure persistent store — avoid `localStorage` if you can, to reduce XSS exposure).
3. Send it on every subsequent request:

```
Authorization: Bearer {token}
```

4. Tokens currently expire after **30 days** (`SANCTUM_TOKEN_EXPIRATION`, configurable by backend). There is no refresh-token endpoint — when a token expires or is revoked, the user must log in again (`401 Unauthenticated.`).
5. A token is revoked by calling `logout`, by a password reset (revokes **all** tokens), or by a password change (revokes all **other** tokens, keeping the one used for the request).

## Response envelope

Every response — success or error — has this shape:

```json
{
  "message": "Human-readable summary.",
  "data": { "...": "..." },
  "status": 200,
  "errors": []
}
```

- `data` is `null` for endpoints that don't return a resource (logout, forgot-password, reset-password, verify-email, resend-verification, change-password).
- `errors` is a flat array. For validation failures (`422`) it's the list of all field error messages (not keyed by field — see below).
- `status` always matches the HTTP status code.

### Validation error shape (422)

```json
{
  "message": "Validation failed.",
  "data": null,
  "status": 422,
  "errors": [
    "Email is required.",
    "Password must be at least 8 characters."
  ]
}
```

`errors` is `$validator->errors()->all()` — a flat list of messages, not an object keyed by field name. If you need per-field errors in the UI, match on message content or ask backend to add a keyed variant.

### Common error statuses

| Status | Meaning | Example endpoints |
|---|---|---|
| 401 | Missing/invalid/expired bearer token, or bad login credentials | any `auth:sanctum` route, `login` |
| 403 | Invalid/expired/tampered signed link | `verify-email` |
| 409 | Resource already exists | `signup` (duplicate email) |
| 422 | Validation failed, or a business-rule rejection (wrong current password, bad reset token) | most POST/PUT endpoints |
| 429 | Rate limited | `signup`, `login`, `forgot-password`, `reset-password`, `resend-verification`, `verify-email` |
| 500 | Unexpected server error | any |

---

## Endpoints

### `POST /auth/signup`

Create an account. No auth required.

**Body**

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `email` | string | required, valid email |
| `password` | string | required, min 10 chars, mixed case, at least one number, must match `password_confirmation` |
| `password_confirmation` | string | required, must equal `password` |

**Success — `201`**

```json
{
  "message": "User created successfully.",
  "data": {
    "id": "9f1c2e2a-....",
    "email": "jane@example.com",
    "name": "Jane Doe",
    "token": "1|abcdef123456...",
    "email_verified_at": null
  },
  "status": 201,
  "errors": []
}
```

- `token` — use immediately as the bearer token; the user is logged in right after signup.
- `email_verified_at` — `null` until the user clicks the verification link (see below). Nothing is blocked on verification today, but expect that to change.
- A verification email is sent automatically on signup (see **Email verification** below).

**Errors**: `409` duplicate email, `422` validation, `429` rate limited (5/min per IP).

---

### `POST /auth/login`

No auth required.

**Body**

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, valid email |
| `password` | string | required |

**Success — `200`**

```json
{
  "message": "Login successful.",
  "data": {
    "id": "9f1c2e2a-....",
    "email": "jane@example.com",
    "name": "Jane Doe",
    "token": "2|ghijkl789...",
    "email_verified_at": "2026-07-30T12:00:00.000000Z"
  },
  "status": 200,
  "errors": []
}
```

Each login issues a **new** token; old tokens from other sessions/devices remain valid (multi-device supported) unless separately revoked.

**Errors**: `401` — deliberately generic `"Authentication failed."` for both "unknown email" and "wrong password" (no user enumeration via login). `422` validation, `429` rate limited (10/min per IP+email).

---

### `POST /auth/logout` 🔒

Requires `Authorization: Bearer {token}`.

Revokes **only the token used for this request** — other devices/sessions stay logged in.

**Success — `200`**
```json
{ "message": "Logged out successfully.", "data": null, "status": 200, "errors": [] }
```

**Errors**: `401` if the token is missing/invalid/already revoked.

---

### `POST /auth/forgot-password`

No auth required. Triggers a password-reset email.

**Body**

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, valid email |

**Success — `200`** — **always this exact response**, whether or not an account exists for that email (prevents attackers from discovering registered emails):

```json
{
  "message": "If an account with that email exists, a password reset link has been sent.",
  "data": null,
  "status": 200,
  "errors": []
}
```

⚠️ Do not build UI that infers "email not found" from this response — it will never say that. If the email exists, the user receives a message with a link to:

```
{FRONTEND_URL}/reset-password?token={token}&email={email}
```

Build a page at that route which collects a new password and calls `reset-password` (below) with those values.

**Errors**: `422` validation, `429` rate limited (3/min per IP+email).

---

### `POST /auth/reset-password`

No auth required. Called from the frontend's `/reset-password` page after the user follows the emailed link.

**Body**

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, valid email (from the link's query param) |
| `token` | string | required (from the link's query param) |
| `password` | string | required, min 10 chars, mixed case, number, must match confirmation |
| `password_confirmation` | string | required |

**Success — `200`**
```json
{ "message": "Your password has been reset successfully.", "data": null, "status": 200, "errors": [] }
```
All of the user's existing bearer tokens are revoked — every logged-in device is signed out and must log in again with the new password.

**Errors**: `422` — either validation failure, or `"This password reset link is invalid or has expired."` (invalid/used/expired token — same message for both, to avoid leaking which). Reset tokens expire after **60 minutes**. `429` rate limited (shares the 3/min forgot-password limiter).

---

### Email verification

Sent automatically on signup, and can be resent (see below). The email contains a link to:

```
{FRONTEND_URL}/verify-email/{id}/{hash}?expires={expires}&signature={signature}
```

**The frontend must build a page at that route** which, on load, calls the real API endpoint using those exact same path/query values:

#### `GET /auth/email/verify/{id}/{hash}?expires=...&signature=...`

No bearer token required — the link's signature is itself the proof of ownership (this lets verification work straight from an email client with no app session). **Do not modify or drop the `expires`/`signature` query params** — the signature is computed over the exact URL and will fail validation otherwise.

**Success — `200`**
```json
{ "message": "Email verified successfully.", "data": null, "status": 200, "errors": [] }
```
Verifying an already-verified email is a harmless no-op (still `200`), so it's safe if the user double-clicks the link.

**Errors**: `403` — `"This verification link is invalid or has expired."` (expired, tampered, or reused-after-expiry). Links expire after **60 minutes**. `429` rate limited (10/min per IP).

#### `POST /auth/email/verification-notification` 🔒

Resends the verification email to the authenticated user. Requires `Authorization: Bearer {token}`.

**Success — `200`**
```json
{ "message": "Verification email sent.", "data": null, "status": 200, "errors": [] }
```
No-op (still `200`, no new email) if the user is already verified.

**Errors**: `401` unauthenticated, `429` rate limited (3/min per user).

---

### `GET /profile` 🔒

Returns the authenticated user's own profile. Requires `Authorization: Bearer {token}`.

**Success — `200`**
```json
{
  "message": "Profile retrieved successfully.",
  "data": {
    "id": "9f1c2e2a-....",
    "email": "jane@example.com",
    "name": "Jane Doe",
    "email_verified_at": "2026-07-30T12:00:00.000000Z"
  },
  "status": 200,
  "errors": []
}
```

**Errors**: `401` unauthenticated.

---

### `PUT /profile` 🔒

Update the authenticated user's name/email. Requires `Authorization: Bearer {token}`.

**Body**

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `email` | string | required, valid email, must not belong to another user |

**Success — `200`** — same shape as `GET /profile`.

⚠️ **If `email` changes**: `email_verified_at` resets to `null` and a new verification email is sent automatically. Reflect this in the UI (e.g. show a "please re-verify your email" banner after a successful email change).

**Errors**: `401` unauthenticated, `422` validation (including "email already taken").

---

### `PUT /profile/password` 🔒

Change the authenticated user's password. Requires `Authorization: Bearer {token}`.

**Body**

| Field | Type | Rules |
|---|---|---|
| `current_password` | string | required, must match the account's current password |
| `new_password` | string | required, min 10 chars, mixed case, number, must differ from `current_password`, must match confirmation |
| `new_password_confirmation` | string | required |

**Success — `200`**
```json
{ "message": "Password changed successfully.", "data": null, "status": 200, "errors": [] }
```
All of the user's **other** tokens/devices are revoked; the token used for this request stays valid, so the current session is not logged out.

**Errors**: `401` unauthenticated, `422` — validation, or `"The provided current password is incorrect."`.

---

## Quick reference

| Method | Path | Auth | Rate limit |
|---|---|---|---|
| POST | `/auth/signup` | — | 5/min per IP |
| POST | `/auth/login` | — | 10/min per IP+email |
| POST | `/auth/logout` | 🔒 | — |
| POST | `/auth/forgot-password` | — | 3/min per IP+email |
| POST | `/auth/reset-password` | — | 3/min per IP+email |
| GET | `/auth/email/verify/{id}/{hash}` | signed link | 10/min per IP |
| POST | `/auth/email/verification-notification` | 🔒 | 3/min per user |
| GET | `/profile` | 🔒 | — |
| PUT | `/profile` | 🔒 | — |
| PUT | `/profile/password` | 🔒 | — |

🔒 = requires `Authorization: Bearer {token}`

## Frontend pages you need to build

These three routes exist only on the frontend — the backend emails links to them, and they call the API endpoints above:

1. **`/reset-password?token=...&email=...`** — form to collect a new password, calls `POST /auth/reset-password`.
2. **`/verify-email/{id}/{hash}?expires=...&signature=...`** — no user input needed; on load, call `GET /auth/email/verify/{id}/{hash}?expires=...&signature=...` with the exact query string received, then show the result.
3. Anywhere the user is logged in with an unverified email, consider a banner with a "resend verification email" action calling `POST /auth/email/verification-notification`.

Set `FRONTEND_URL` in the backend's environment to your frontend's actual base URL so these links point to the right place.
