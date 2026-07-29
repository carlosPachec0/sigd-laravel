<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Academy extends Model
{
    use SoftDeletes;
    
    protected $table = 'academies';

    protected $fillable = [
        'name',
        'discipline',
        'registration_fee',
        'monthly_fee',
        'class_fee'
    ];

    public function trainer() {
        return $this->belongsTo(Trainer::class);
    }

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function offers() {
        return $this->hasMany(Offer::class);
    }

    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
            'monthly_fee' => 'decimal:2',
            'class_fee' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
