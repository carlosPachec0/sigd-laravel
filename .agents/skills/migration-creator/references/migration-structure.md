# Migration Structure

## Basic Structure

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            // columns...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

## Key Points

- **Anonymous class**: Uses `return new class extends Migration`
- **Two methods**: `up()` to create, `down()` to reverse
- **Return type**: Always `void`
- **Reversible**: `down()` must undo everything `up()` does

## Connection Property

For non-default database:

```php
protected $connection = 'pgsql';
```

## Common Columns

```php
$table->id();                              // BigIncrements primary key
$table->string('name');                    // VARCHAR(255)
$table->string('name', 100);              // VARCHAR(100)
$table->text('body');                      // TEXT
$table->integer('votes');                  // INT
$table->boolean('active');                 // BOOLEAN
$table->decimal('price', 8, 2);           // DECIMAL
$table->datetime('published_at');         // DATETIME
$table->json('metadata');                 // JSON
$table->foreignId('user_id');             // UNSIGNED BIGINT
$table->timestamps();                     // created_at, updated_at
$table->softDeletes();                    // deleted_at
```

## Foreign Keys

```php
$table->foreignId('user_id')->constrained();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('user_id')->nullable()->constrained();
```

## Indexes

```php
$table->unique('email');
$table->index('status');
$table->index(['account_id', 'created_at']);
```