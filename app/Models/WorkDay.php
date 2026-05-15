<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkDay extends Model
{
    protected $fillable = [
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
