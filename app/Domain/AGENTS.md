# Domain Layer AGENTS.md

This file defines the Domain layer for the SIGD Laravel application.

## Layer Purpose
The Domain layer contains the core business logic, entities, contracts, and domain exceptions. It is independent of infrastructure concerns and defines the business rules.

## Structure

### Constants
- `UserRoles.php`: Defines user role constants (ADMIN, STANDARD) and provides a method to list all roles.

### Contracts
- `Repositories/UserRepositoryInterface.php`: Interface defining user repository operations:
  - `findByEmail(string $email): ?User`
  - `create(array $data): User`

### Entities
- `User.php`: Eloquent model representing a user entity with:
  - Attributes: email, password, first_name, last_name, role
  - Uses authentication traits (HasFactory, Notifiable)
  - Password hashing via cast

### Exceptions
- `InvalidCredentialsException.php`: Thrown when authentication credentials are invalid
- `UserAlreadyExistsException.php`: Thrown when attempting to create a user with an existing email

## Skills

- [Entity Creator](../../.commandcode/skills/entity-creator/SKILL.md) - Creates Domain entities following SIGD Laravel patterns

## Conventions
- All classes use strict typing (`declare(strict_types=1)`)
- Exceptions extend RuntimeException
- Constants are defined as final classes with public const
- Interfaces are placed in Contracts directory
- Entities are Eloquent models in Entities directory

## Dependencies
- Domain layer depends only on Laravel's Eloquent and Authentication components
- No dependencies on Application, Infrastructure, or Presentation layers