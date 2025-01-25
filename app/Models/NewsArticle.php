<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $fillable = [
        'title',
        'original_content',
        'summary',
        'source_name',
        'source_url',
        'category',
        'published_at',
        'relevance_score',
        'approval_status',
        'linkedin_post',
        'generated_image_url'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'relevance_score' => 'integer',
    ];
} 