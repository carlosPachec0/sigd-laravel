<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'student_id',
        'amount'
    ];

    protected function casts() {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ];
    }
}
