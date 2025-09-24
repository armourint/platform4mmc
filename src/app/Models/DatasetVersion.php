<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DatasetVersion extends Model
{
    protected $fillable = [
        'module',
        'version_label',
        'status',        // draft | published | archived
        'is_current',    // bool
        'published_at',  // timestamp nullable
        'notes',
    ];

    // alias
    public function getLabelAttribute(): ?string { return $this->version_label; }
    public function setLabelAttribute($v): void { $this->attributes['version_label'] = $v; }

    /** Promote this dataset to current for its module (atomic). */
    public static function makeCurrent(int $id): void
    {
        DB::transaction(function() use ($id) {
            $dv = static::lockForUpdate()->findOrFail($id);

            // Demote others in the same module
            static::where('module', $dv->module)->update(['is_current' => false]);

            // Promote selected
            $dv->update([
                'status'       => 'published',
                'is_current'   => true,
                'published_at' => now(),
            ]);
        });
    }

    /** Current dataset id for a module with sane fallback ordering. */
    public static function currentId(string $module): ?int
    {
        return static::where('module', $module)->where('is_current', true)->value('id')
            ?? static::where('module', $module)->where('status','published')
                   ->orderByDesc('published_at') // may be NULL
                   ->orderByDesc('id')           // tie-breaker when published_at is NULL
                   ->value('id');
    }
}
