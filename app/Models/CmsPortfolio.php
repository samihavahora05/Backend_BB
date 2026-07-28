<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsPortfolio extends Model
{
    use HasFactory;

    protected $table = 'cms_portfolios';

    protected $fillable = [
        'title', 'slug', 'studio', 'category', 'description', 
        'duration', 'deliverables', 'image_url', 'link', 'tags',
        'is_featured', 'display_order', 'status',
        'seo_title', 'seo_description'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
    ];
}
