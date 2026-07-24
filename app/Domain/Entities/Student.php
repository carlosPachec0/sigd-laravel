<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Student extends Model
{
    use SoftDeletes;

    protected $table = 'students';

    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'height',
        'weight'
    ];

    public function assistance() {
        return $this->hasMany(Assistance::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
