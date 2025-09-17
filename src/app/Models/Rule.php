<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $casts=['conditions_json'=>'array'];
    protected $fillable=['dataset_version_id','module','system_id','rule_type','conditions_json','reason','priority'];
    public function system(){ return $this->belongsTo(System::class); }
    public function datasetVersion(){ return $this->belongsTo(DatasetVersion::class); }
}
