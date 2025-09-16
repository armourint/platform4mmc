<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model {
    protected $fillable = [
        'location_id','at','year','target','rf','rbf','dt','ann','rnn','lstm','gru'
    ];
    protected $casts = ['at'=>'datetime'];
    public function location(){ return $this->belongsTo(Location::class); }
}
