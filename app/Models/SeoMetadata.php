<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'url_path',
        'title',
        'description',
        'keywords',
        'canonical_url',
        'og_image',
        'schema_json',
        'robots',
    ];

    protected $casts = [
        'schema_json' => 'array',
    ];
}
