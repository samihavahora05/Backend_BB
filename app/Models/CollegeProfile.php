<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'university_affiliation',
        'contact_email',
        'contact_phone',
        'address',
        'logo',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(CollegeStudent::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(CollegePlacement::class);
    }
}
