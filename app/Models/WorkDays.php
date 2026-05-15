<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkDays extends Model
{
    protected $table = 'work_days';

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
