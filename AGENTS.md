# SIGD Laravel Architecture AGENTS.md

This is the main orchestrator for the SIGD Laravel application architecture. For detailed layer specifications, see the layer-specific AGENTS.md files.

## Architecture Overview

The application follows a **layered architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────┐
│           Infrastructure Layer              │
│  (HTTP, Controllers, Repositories, DI)      │
├─────────────────────────────────────────────┤
│           Application Layer                 │
│  (Services, DTOs, Use Cases)                │
├─────────────────────────────────────────────┤
│              Domain Layer                   │
│  (Entities, Contracts, Exceptions)          │
└─────────────────────────────────────────────┘
```

## Layer Dependencies

- **Domain Layer** → No dependencies (core business logic)
- **Application Layer** → Depends on Domain
- **Infrastructure Layer** → Depends on Domain and Application

## Layer Documentation

- [Domain Layer](app/Domain/AGENTS.md) - Business entities, contracts, exceptions
- [Application Layer](app/Application/AGENTS.md) - Services, DTOs, use case orchestration
- [Infrastructure Layer](app/Infrastructure/AGENTS.md) - HTTP handling, repositories, providers
- [Database Layer](database/AGENTS.md) - Migrations, factories, seeders

## Current Features

- **Authentication**: Signup and login with role-based access (Admin, Standard)
- **API Responses**: Standardized JSON response format
- **Validation**: Request validation with structured error responses

## Key Conventions

- Strict typing (`declare(strict_types=1)`)
- Final classes for services, controllers, DTOs
- Immutable DTOs (readonly)
- Domain contracts (interfaces) in Domain layer
- Implementations in Infrastructure layer

## Adding New Features

1. **Domain**: Add entities, contracts, exceptions
2. **Application**: Add services, DTOs for use cases
3. **Infrastructure**: Add controllers, requests, repositories
4. **Providers**: Register new bindings in service providers

## Testing

- Unit tests for Domain and Application layers
- Feature tests for Infrastructure layer (HTTP endpoints)