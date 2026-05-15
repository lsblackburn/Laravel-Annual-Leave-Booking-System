<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonWorkDay extends Model
{
    
    protected $fillable = [
        'name',
        'date',
    ];

}
