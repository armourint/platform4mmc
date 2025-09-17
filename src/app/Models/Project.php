<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable=['owner_id','name','client','location','meta'];
    protected $casts=['meta'=>'array'];
    public function owner(){ return $this->belongsTo(User::class,'owner_id'); }
    public function assessments(){ return $this->hasMany(Assessment::class); }
}
