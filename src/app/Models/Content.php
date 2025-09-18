<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'url', 'type', 'source', 'credibility', 'published_at', 'status', 'meta'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'meta' => 'array',
    ];
}
