<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveSetting extends Model
{
    protected $fillable = [
        'base_allowance',
        'increase_after_years',
        'increase_by_days',
        'maximum_allowance',
        'leave_refresh_day',
        'leave_refresh_month',
    ];
}
