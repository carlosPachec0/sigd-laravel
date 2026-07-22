---
name: migration-creator
description: Creates database migrations following the SIGD Laravel architecture patterns. Use when asked to create a new migration, add a database table, or modify the schema.
allowed-tools: Read Bash(php:*)
---

# Migration Creator Skill

Creates database migrations for the SIGD Laravel application.

## Quick Start

Generate a migration:

```bash
bash .commandcode/skills/migration-creator/scripts/generate-migration.sh create_table_name_table
```

## Key Rules

1. **Location**: All migrations go in `database/migrations/`
2. **Anonymous class**: Use `return new class extends Migration`
3. **Reversible**: `down()` must reverse `up()`
4. **Return types**: Always declare `void`

## Example

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

## References

- [Generating Migrations](references/generating-migrations.md)
- [Migration Structure](references/migration-structure.md)