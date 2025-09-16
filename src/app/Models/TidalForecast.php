<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TidalForecast extends Model {
    protected $fillable=['at','height_m','location'];
    protected $casts=['at'=>'datetime'];
}
