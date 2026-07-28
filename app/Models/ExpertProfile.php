<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExpertProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'hourly_rate',
        'is_verified',
        'designation',
        'company',
        'experience_years',
        'highest_qualification',
        'specialization',
        'profile_photo',
        'approval_status',
        'is_available',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'website',
        'profile_completion_percentage',
        'average_rating',
        'total_reviews',
        'total_courses_sold',
        'total_students',
        'total_certificates_issued',
        'total_revenue',
        'completion_rate',
        'student_satisfaction',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(ExpertAvailability::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MentorSession::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'expert_courses');
    }
}
