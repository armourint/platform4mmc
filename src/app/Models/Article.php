<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory;
    // use SoftDeletes; // enable if soft deletes are used

    protected $fillable = ['title','slug','body','status','published_at'];

    // Cast published_at to DateTime for convenience
    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Relationships
    public function categories() {
        return $this->belongsToMany(Category::class, 'article_category');
    }

    // Scope for only published articles
    public function scopePublished($query) {
        return $query->where('status', 'published');
    }

    // Optional: generate slug automatically on creating/updating
    protected static function booted() {
        static::saving(function($article) {
            if (empty($article->slug) && !empty($article->title)) {
                $baseSlug = \Str::slug($article->title);
                // Ensure unique slug
                $slug = $baseSlug;
                $counter = 2;
                while (Article::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $article->slug = $slug;
            }
            // Set published_at if publishing
            if ($article->status === 'published' && is_null($article->published_at)) {
                $article->published_at = now();
            }
            // If reverted to draft, you may optionally nullify published_at:
            // if ($article->status === 'draft') $article->published_at = null;
        });
    }

    // Route model binding by slug
    public function getRouteKeyName(): string {
        return 'slug';
    }
}
