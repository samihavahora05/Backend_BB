<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsPlacementPartner extends Model
{
    use HasFactory;

    protected $table = 'cms_placement_partners';

    protected $fillable = [
        'name', 'slug', 'logo_url', 'industry_id', 'website_url', 
        'is_featured', 'show_on_homepage', 'display_order', 'status',
        'seo_title', 'seo_description'
    ];

    public function industry()
    {
        return $this->belongsTo(CmsIndustry::class, 'industry_id');
    }
}
