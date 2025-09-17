<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetVersion extends Model
{
    protected $fillable=['module','version_label','status','effective_from','notes'];
    public function rules(){ return $this->hasMany(Rule::class); }
}
