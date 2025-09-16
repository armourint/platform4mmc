<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Location extends Model {
    protected $fillable = ['city_id','code','i','j','lat','lng'];
    public function city(){ return $this->belongsTo(City::class); }
    public function predictions(){ return $this->hasMany(Prediction::class); }
}
