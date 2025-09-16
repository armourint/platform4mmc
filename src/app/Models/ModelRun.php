<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ModelRun extends Model {
    protected $fillable=['name','source_file','meta'];
    protected $casts=['meta'=>'array'];
    public function observations(): HasMany { return $this->hasMany(ModelObservation::class); }
}
