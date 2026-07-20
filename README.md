# SIGD-backend

RESTful API built with Laravel 13 for the SIGD system. This is an API-only backend — no frontend code is included. The frontend (Vue SPA) will consume this API separately.

## Tech Stack

- PHP 8.5
- Laravel 13
- PostgreSQL 18
- Laravel Sanctum (SPA cookie-based authentication)
- PHPUnit

## Architecture

Clean Architecture adapted to Laravel conventions, organized in three layers:

```
app/
├── Domain/                  # Inner layer — entities, contracts, constants, exceptions
│   ├── Constants/
│   ├── Contracts/Repositories/
│   ├── Entities/
│   └── Exceptions/
├── Application/             # Middle layer — services (use cases), DTOs
│   ├── DTOs/
│   ├── Services/
│   └── Validators/
└── Infrastructure/          # Outer layer — HTTP, repositories, providers
    ├── Http/
    │   ├── Controllers/
    │   ├── Middleware/
    │   ├── Requests/
    │   └── Resources/
    ├── Providers/
    ├── Repositories/
    └── Traits/
```

**Dependency rule**: Infrastructure → Application → Domain. Domain has no application-internal dependencies.

**Trade-off**: Entity models extend `Eloquent\Model` inside Domain/Entities. This is a deliberate Laravel-convention choice — Eloquent's Active Record pattern is accepted as a framework-level dependency. Business logic never lives in entities.

## Endpoints

| Method | Endpoint              | Description       | Auth |
|--------|-----------------------|-------------------|------|
| POST   | `/api/v1/auth/signup` | Register new user | No   |
| POST   | `/api/v1/auth/login`  | Authenticate user | No   |

### Signup

```bash
curl -X POST http://localhost:8000/api/v1/auth/signup \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "StrongPass1!",
    "password_confirmation": "StrongPass1!",
    "first_name": "John",
    "last_name": "Doe",
    "role": "Standard"
  }'
```

**Success (201):**
```json
{
    "message": "User created successfully.",
    "data": {
        "id": 1,
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "role": "Standard"
    },
    "status": 201,
    "errors": []
}
```

### Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "StrongPass1!"
  }'
```

**Success (200):**
```json
{
    "message": "Login successful.",
    "data": {
        "id": 1,
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "role": "Standard"
    },
    "status": 200,
    "errors": []
}
```

### Standard Error Response

```json
{
    "message": "Validation failed.",
    "data": null,
    "status": 422,
    "errors": [
        "Email is required."
    ]
}
```

### SPA Authentication Flow

This API uses Sanctum's SPA cookie-based mode. Authentication is session-based — no tokens are returned in the JSON body.

The Vue SPA must call `GET /sanctum/csrf-cookie` before signup or login to obtain the `XSRF-TOKEN` cookie. The HTTP client then sends it back as the `X-XSRF-TOKEN` header on the actual request. Laravel validates the CSRF token and starts a session via the `Set-Cookie` response header.

Subsequent authenticated requests are made with `withCredentials: true` (or `credentials: 'include'`) so the browser sends the session cookie automatically.

## Setup

### Prerequisites

- PHP 8.5+
- Composer 2.x
- PostgreSQL 18

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env` with PostgreSQL credentials:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigd_db
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

Start the dev server:

```bash
php artisan serve
```

### Testing

Tests use an in-memory SQLite database (configured in `phpunit.xml`).

```bash
php artisan test
```

## Database Schema — Users

| Column      | Type                     |
|-------------|--------------------------|
| id          | bigint, auto increment   |
| email       | string, unique           |
| password    | string (hashed)          |
| first_name  | string                   |
| last_name   | string                   |
| role        | string                   |
| created_at  | timestamp                |
| updated_at  | timestamp                |

## User Roles

Stored as constant strings in `App\Domain\Constants\UserRoles`:

- `Admin`
- `Standard`

## Key Design Decisions

- **Repository pattern**: `UserRepositoryInterface` (Domain) with `UserRepository` (Infrastructure). Depends on abstractions, not implementations.
- **Service layer**: `AuthService` orchestrates signup/login. Controllers are thin — no business logic.
- **DTOs**: Strongly typed, readonly DTOs isolate services from HTTP input and Eloquent models. Response DTOs do not include authentication tokens — the session is communicated exclusively via the `Set-Cookie` header.
- **Validation**: Form Requests (`SignupRequest`, `LoginRequest`) handle all validation. Services assume valid data.
- **Exception handling**: Centralized in `bootstrap/app.php`. Domain exceptions map to proper HTTP status codes (`UserAlreadyExistsException` → 409, `InvalidCredentialsException` → 401).
- **Dependency injection**: Constructor injection everywhere. Bindings in `RepositoryServiceProvider`.
- **API versioning**: URI-based (`/api/v1/`). New versions can be added without breaking existing clients.
- **Standard response**: All endpoints return `{message, data, status, errors}` via the `ApiResponse` trait.
- **Strict types**: Every PHP file declares `strict_types=1`.
- **Authentication**: Sanctum SPA cookie-based mode. `Auth::guard('web')->login($user)` starts a session. No bearer tokens. Session cookie is `HttpOnly`, `SameSite=lax`, shared via `SESSION_DOMAIN=localhost`.

## License

MIT
