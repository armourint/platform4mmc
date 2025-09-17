<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable=['type','title','excerpt','body','meta'];
    protected $casts=['meta'=>'array'];
}
