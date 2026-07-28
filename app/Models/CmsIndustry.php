<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsIndustry extends Model
{
    use HasFactory;

    protected $table = 'cms_industries';
    
    protected $fillable = [
        'name', 'slug', 'is_active'
    ];

    public function companies()
    {
        return $this->hasMany(CmsCompany::class, 'industry_id');
    }

    public function placementPartners()
    {
        return $this->hasMany(CmsPlacementPartner::class, 'industry_id');
    }
}
