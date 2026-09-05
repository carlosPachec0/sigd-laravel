<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;

class Assistance extends Model
{
    protected $table = 'assistance';

    protected $fillable = [
        'student_id',
        'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
