<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::creating(function ($job) {
            if (empty($job->job_id_prefix)) {
                $job->job_id_prefix = 'JOB-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
            }
        });
    }

    protected $casts = [
        'responsibilities' => 'array',
        'requirements' => 'array',
        'benefits' => 'array',
        'required_skills' => 'array',
        'application_deadline' => 'datetime',
        'hide_salary' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['company_name', 'company_logo'];

    public function getCompanyNameAttribute()
    {
        if ($this->relationLoaded('company') && $this->company) {
            if ($this->company->relationLoaded('companyProfile')) {
                return $this->company->companyProfile?->company_name 
                    ?? $this->company->name 
                    ?? trim("{$this->company->first_name} {$this->company->last_name}") 
                    ?: 'Unknown Company';
            }
            return $this->company->name 
                ?? trim("{$this->company->first_name} {$this->company->last_name}") 
                ?: 'Unknown Company';
        }
        return 'Unknown Company';
    }

    public function getCompanyLogoAttribute()
    {
        if ($this->relationLoaded('company') && $this->company && $this->company->relationLoaded('companyProfile')) {
            return $this->company->companyProfile?->logo ?? null;
        }
        return null;
    }

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function college()
    {
        return $this->belongsTo(User::class, 'college_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function shortlists()
    {
        return $this->hasMany(JobShortlist::class);
    }

    public function documents()
    {
        return $this->hasMany(JobDocument::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(JobActivityLog::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(JobBookmark::class);
    }

    public function views()
    {
        return $this->hasMany(JobView::class);
    }
}
