<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name','slug','type'];

    // Relationships
    public function articles() {
        return $this->belongsToMany(Article::class, 'article_category');
    }

    // (Optional) If you want to enforce allowed types, you could add:
    // public static array $types = ['phase','topic','mmc_type'];
    // and use it in validation when creating/updating categories.
    
    // Route key (if needed for category pages in future)
    // public function getRouteKeyName(): string {
    //     return 'slug';
    // }
}
