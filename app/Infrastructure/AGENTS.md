# Infrastructure Layer AGENTS.md

This file defines the Infrastructure layer for the SIGD Laravel application.

## Layer Purpose
The Infrastructure layer implements the technical details: HTTP handling, repository implementations, dependency injection, and cross-cutting concerns. It depends on Domain and Application layers.

## Structure

### Http/Controllers
- `AuthController.php`: Handles authentication endpoints (signup, login)
  - Uses AuthService from Application layer
  - Returns standardized JSON responses via ApiResponse trait

### Http/Middleware
- `ForceJsonResponse.php`: Sets Accept header to application/json for all requests

### Http/Requests
- `LoginRequest.php`: Validates login input (email, password)
- `SignupRequest.php`: Validates signup input (email, password, first_name, last_name, role)
  - Both throw HttpResponseException on validation failure with structured error response

### Http/Resources
- Empty directory (available for API resource classes)

### Providers
- `RepositoryServiceProvider.php`: Binds domain interfaces to infrastructure implementations
  - UserRepositoryInterface → UserRepository
  - AuthService → AuthService with UserRepository dependency

### Repositories
- `UserRepository.php`: Implements UserRepositoryInterface
  - findByEmail(): Queries User model by email
  - create(): Creates new User record

### Traits
- `ApiResponse.php`: Provides successResponse() and errorResponse() methods for consistent JSON formatting

## Conventions
- All classes use strict typing (`declare(strict_types=1)`)
- Controllers are final classes
- Requests extend FormRequest with custom failedValidation()
- Repositories implement domain contracts
- Service providers handle dependency injection

## Dependencies
- Depends on Domain layer (entities, contracts, constants)
- Depends on Application layer (services, DTOs)
- Uses Laravel's Eloquent, FormRequest, and ServiceProvider