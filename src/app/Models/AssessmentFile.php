<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentFile extends Model
{
    protected $fillable=['assessment_id','kind','path','mime','size_bytes','checksum'];
    public function assessment(){ return $this->belongsTo(Assessment::class); }
}
