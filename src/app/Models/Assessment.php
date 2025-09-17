<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable=['project_id','type','status','dataset_version_id','inputs','score','summary'];
    protected $casts=['inputs'=>'array','score'=>'array','summary'=>'array'];
    public function project(){ return $this->belongsTo(Project::class); }
    public function datasetVersion(){ return $this->belongsTo(DatasetVersion::class); }
    public function systemResults(){ return $this->hasMany(AssessmentSystemResult::class); }
}
