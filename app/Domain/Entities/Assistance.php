<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assistance extends Model
{
    protected $table = 'assistance';

    protected $fillable = [
        'student_id',
        'date'
    ];

    protected function casts() {
        return [
            'date' => 'date',
        ];
    }
}
