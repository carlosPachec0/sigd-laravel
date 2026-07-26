---
name: entity-creator
description: Creates Domain entities following the SIGD Laravel architecture patterns. Use when asked to create a new entity, add a model to the Domain layer, or create a database representation class.
---

# Entity Creator Skill

This skill creates Domain entities following the SIGD Laravel architecture patterns.

## When to Use

Use this skill when asked to:
- Create a new entity
- Add a model to the Domain layer
- Create a database representation class

## Entity Pattern

Entities in this project follow these rules:

### Location
All entities go in: `app/Domain/Entities/`

### Structure
```php
<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityName extends Model
{
    use HasFactory;

    protected $table = 'table_name';

    protected $fillable = [
        'field1',
        'field2',
    ];

    protected function casts(): array
    {
        return [
            'field1' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

### Key Rules

1. **Dependencies**: Entities ONLY depend on Eloquent (Model, HasFactory)
2. **No application logic**: Entities are pure data representations
3. **Namespace**: Always `App\Domain\Entities`
4. **Strict typing**: Always include `declare(strict_types=1)`
5. **Table name**: Explicitly define `$table` property
6. **Fillable**: Define all mass-assignable fields
7. **Casts**: Define type casts for non-string fields

### Special Cases

**Authenticatable entities** (like User):
- Extend `Authenticatable` instead of `Model`
- Add `Notifiable` trait
- Hide sensitive fields (`$hidden`)

## Example: Creating a Post Entity

Given request: "Create a Post entity with title, body, and user_id"

Output:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'title',
        'body',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

## Related Tasks

After creating an entity, you may also need:
- Migration in `database/migrations/`
- Factory in `database/factories/Domain/Entities/`
- Contract (interface) in `app/Domain/Contracts/Repositories/`