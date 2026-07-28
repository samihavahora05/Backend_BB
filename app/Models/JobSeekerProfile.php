<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobSeekerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'phone',
        'resume_path',
        'experience',
        'expected_salary',
        'preferred_location',
        'preferred_job_type',
        'skills',
        'linkedin',
        'github',
        'portfolio',
        'headline',
        'about_me',
        'profile_completion',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expected_salary' => 'decimal:2',
            'skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }
}
