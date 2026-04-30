<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'is_half_day',
        'manager_comment',
        'additional_info'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
}
