<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Rule.php
class Rule extends Model
{
    protected $casts = ['conditions_json' => 'array'];
    protected $fillable = [
        'dataset_version_id','module','system_id','system_code',
        'rule_type','conditions_json','reason','priority'
    ];
    public function system(){ return $this->belongsTo(System::class); }
    public function datasetVersion(){ return $this->belongsTo(DatasetVersion::class); }
}
