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
        'seo_title', 'seo_description',
        'banner_image', 'short_description', 'full_description', 'website_url',
        'is_ugc_approved', 'naac_grade', 'nirf_ranking', 'is_wes_approved',
        'degree_types', 'popular_courses', 'duration', 'eligibility',
        'admission_process', 'placement_support', 'career_services', 'accreditation',
        'meta_keywords'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_ugc_approved' => 'boolean',
        'is_wes_approved' => 'boolean',
        'degree_types' => 'array',
        'popular_courses' => 'array',
    ];
}
