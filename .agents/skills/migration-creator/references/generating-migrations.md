# Generating Migrations

## Artisan Command

Use `make:migration` to generate a migration:

```bash
php artisan make:migration create_flights_table
```

Laravel will:
1. Create file in `database/migrations/`
2. Add timestamp to filename for ordering
3. Attempt to guess table name from migration name
4. Pre-fill migration if table name is determined

## Naming Conventions

| Command | Creates |
|---------|---------|
| `create_users_table` | New `users` table |
| `add_votes_to_users_table` | Modify `users` table |
| `create_flights_table` | New `flights` table |

## Options

```bash
# Custom path (relative to base path)
php artisan make:migration create_posts_table --path=database/migrations
```

## Squashing Migrations

Dump current schema to SQL file:

```bash
php artisan schema:dump
php artisan schema:dump --prune  # Dump and remove old migrations
```

Schema files go to `database/schema/` directory.