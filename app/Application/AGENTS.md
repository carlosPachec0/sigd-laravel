# Application Layer AGENTS.md

This file defines the Application layer for the SIGD Laravel application.

## Layer Purpose
The Application layer orchestrates use cases, defines data transfer objects (DTOs), and contains application services. It depends on Domain layer contracts and entities.

## Structure

### DTOs (Data Transfer Objects)
- `LoginRequestDto.php`: Login input data (email, password)
- `LoginResponseDto.php`: Login output data (id, email, firstName, lastName, role)
- `SignupRequestDto.php`: Signup input data (email, password, firstName, lastName, role)
- `SignupResponseDto.php`: Signup output data (id, email, firstName, lastName, role)

All DTOs are:
- Final readonly classes
- Have `fromArray()` static constructor (request DTOs)
- Have `toArray()` serialization method (response DTOs)

### Services
- `AuthService.php`: Authentication business logic
  - `signup()`: Creates user, throws UserAlreadyExistsException if email exists
  - `login()`: Authenticates user, throws InvalidCredentialsException on failure
  - Uses UserRepositoryInterface for persistence
  - Uses Laravel's Auth facade for session management

### Validators
- Empty directory (available for custom validation rules)

## Conventions
- All classes use strict typing (`declare(strict_types=1)`)
- DTOs are immutable (readonly)
- Services are final classes
- Services depend only on domain contracts (repositories)
- Exceptions from Domain layer are thrown for business rule violations

## Dependencies
- Depends on Domain layer (entities, contracts, exceptions)
- No dependencies on Infrastructure or Presentation layers
- Uses Laravel's Auth and Hash facades for authentication