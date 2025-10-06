<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalSystem extends Model
{
    protected $fillable = [
        'dataset_version_id',
        'system_code',
        'assembly_id',
        'system_name',
        'system_category',
        'mmc_method',
        'is_active',
        'slug',
    ];

    public function properties()
    {
        return $this->hasOne(EnvironmentalProperty::class);
    }

    public function media()
    {
        return $this->hasMany(EnvironmentalSystemMedia::class);
    }
}
