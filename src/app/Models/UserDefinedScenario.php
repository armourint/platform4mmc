<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class UserDefinedScenario extends Model {
    protected $fillable=['user_id','name','params','notes'];
    protected $casts=['params'=>'array'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
