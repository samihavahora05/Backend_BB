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
        'user_id',
        'college_name',
        'name',
        'university_affiliation',
        'contact_email',
        'contact_phone',
        'logo',
        'website',
        'email',
        'phone',
        'contact_person',
        'designation',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'accreditation',
        'total_students',
        'placement_officer',
        'verification_status',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function getIsVerifiedAttribute(): bool
    {
        return (bool) ($this->attributes['is_verified'] ?? ($this->attributes['verification_status'] === 'verified'));
    }

    public function students(): HasMany
    {
        return $this->hasMany(CollegeStudent::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(CollegePlacement::class);
    }
}
