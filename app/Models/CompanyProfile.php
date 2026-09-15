<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'logo',
        'website',
        'gst_number',
        'industry',
        'company_size',
        'founded_year',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'linkedin',
        'twitter',
        'facebook',
        'hiring_status',
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

    public function user() { return $this->belongsTo(User::class); }
    public function jobs() { return $this->hasMany(Job::class, 'company_profile_id'); }
    public function internships() { return $this->hasMany(Internship::class, 'company_profile_id'); }
}
