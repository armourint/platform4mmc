<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImport extends Model
{
    protected $fillable = [
        'module', 'dataset_version_id', 'user_id',
        'original_name', 'disk', 'path', 'status',
        'rows_processed', 'error', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
