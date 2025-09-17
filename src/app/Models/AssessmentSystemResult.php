<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSystemResult extends Model
{
    protected $fillable=['assessment_id','system_id','is_viable','reason'];
    public function assessment(){ return $this->belongsTo(Assessment::class); }
    public function system(){ return $this->belongsTo(System::class); }
}
