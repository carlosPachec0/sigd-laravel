# Database Layer AGENTS.md

This file defines the database layer for the SIGD Laravel application.

## Layer Purpose
The database layer contains migrations, factories, and seeders that define the application's data schema and test data generation.

## Structure

### Migrations
- `0001_01_01_000000_create_users_table.php`: Creates users and sessions tables

### Factories
- `Domain/Entities/UserFactory.php`: Generates fake User data
  - Uses Domain layer's UserRoles constant
  - Default role: STANDARD
  - Password: 'password' (hashed)

### Seeders
- `DatabaseSeeder.php`: Creates initial admin user
  - Email: admin@example.com
  - Role: ADMIN

## Schema Design

### Users Table
- id (primary key)
- email (unique)
- password
- first_name
- last_name
- role
- timestamps

### Sessions Table
- id (primary key)
- user_id (foreign key)
- ip_address
- user_agent
- payload
- last_activity

## Conventions
- Migrations follow Laravel's timestamp naming convention
- Factories mirror Domain layer structure
- Seeders use factories for data generation
- Foreign keys use Laravel's foreignId() method

## Dependencies
- Depends on Domain layer (entities, constants)
- Uses Laravel's migration, factory, and seeder systems