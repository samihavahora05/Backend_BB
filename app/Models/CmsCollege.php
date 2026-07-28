<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsCollege extends Model
{
    use HasFactory;

    protected $table = 'cms_colleges';

    protected $fillable = [
        'name', 'slug', 'logo_url', 'location', 
        'is_featured', 'display_order', 'status',
        'seo_title', 'seo_description'
    ];
}
