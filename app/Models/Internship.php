<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Internship extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'skills_required' => 'array',
        'attachments' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'application_deadline' => 'date',
        'featured' => 'boolean',
    ];

    protected $appends = ['company_name', 'company_logo'];

    public function getCompanyNameAttribute()
    {
        if (!empty($this->attributes['company_name'])) {
            return $this->attributes['company_name'];
        }
        if (!empty($this->attributes['company']) && is_string($this->attributes['company'])) {
            return $this->attributes['company'];
        }
        $companyRelation = $this->relationLoaded('company') ? $this->getRelation('company') : null;
        if ($companyRelation instanceof Model) {
            if ($companyRelation->relationLoaded('companyProfile') && $companyRelation->companyProfile) {
                return $companyRelation->companyProfile->company_name 
                    ?? $companyRelation->name 
                    ?: 'Blueboxx Partner';
            }
            return $companyRelation->name 
                ?: 'Blueboxx Partner';
        }
        return 'Blueboxx Partner';
    }

    public function getCompanyLogoAttribute()
    {
        if (!empty($this->attributes['company_logo'])) {
            return $this->attributes['company_logo'];
        }
        $companyRelation = $this->relationLoaded('company') ? $this->getRelation('company') : null;
        if ($companyRelation instanceof Model && $companyRelation->relationLoaded('companyProfile')) {
            return $companyRelation->companyProfile?->logo ?? null;
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
        return $this->hasMany(InternshipApplication::class);
    }

    public function tasks()
    {
        return $this->hasMany(InternshipTask::class);
    }

    public function evaluations()
    {
        return $this->hasMany(InternshipEvaluation::class);
    }

    public function attendance()
    {
        return $this->hasMany(InternshipAttendance::class);
    }

    public function documents()
    {
        return $this->hasMany(InternshipDocument::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(InternshipActivityLog::class);
    }
}
