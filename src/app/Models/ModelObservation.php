<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ModelObservation extends Model {
    protected $fillable=['model_run_id','observed_at','location','inputs','outputs'];
    protected $casts=['inputs'=>'array','outputs'=>'array','observed_at'=>'datetime'];
    public function run(): BelongsTo { return $this->belongsTo(ModelRun::class,'model_run_id'); }
}
