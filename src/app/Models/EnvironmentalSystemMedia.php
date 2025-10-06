<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalSystemMedia extends Model
{
    protected $fillable = [
        'environmental_system_id',
        'type',
        'path',
        'alt',
        'sort',
    ];
}
