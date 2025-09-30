<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $table = 'rules';

    // Your actual DB columns
    protected $fillable = [
        'dataset_version_id',
        'module',          // e.g., 'viability'
        'system_id',       // nullable; use if you have a systems table
        'system_code',     // e.g., 'BLOCK', 'LGS', ...
        'rule_type',       // 'include' | 'exclude'
        'conditions_json', // JSON column in your schema
        'reason',
        'priority',
    ];

    protected $casts = [
        'conditions_json' => 'array',
        'priority'        => 'integer',
    ];

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function datasetVersion()
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    /**
     * Virtual "conditions" attribute (aliases conditions_json) so
     * legacy code or the evaluator reading $rule->conditions still works.
     */
    public function getConditionsAttribute()
    {
        return $this->conditions_json;
    }

    public function setConditionsAttribute($value): void
    {
        $this->conditions_json = $value;
    }
}
