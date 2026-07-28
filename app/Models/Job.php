<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'responsibilities' => 'array',
        'requirements' => 'array',
        'benefits' => 'array',
        'required_skills' => 'array',
        'application_deadline' => 'datetime',
        'hide_salary' => 'boolean',
        'is_featured' => 'boolean',
    ];

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
