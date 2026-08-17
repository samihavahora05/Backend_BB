<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipApplication extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'custom_answers' => 'array',
        'custom_fields'  => 'array',
        'applied_at'      => 'datetime',
    ];

    protected $appends = [
        'applicant_name',
        'applicant_email',
        'applicant_phone'
    ];

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getApplicantNameAttribute()
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        if ($this->user) {
            return trim("{$this->user->first_name} {$this->user->last_name}");
        }
        return 'Applicant #' . $this->id;
    }

    public function getApplicantEmailAttribute()
    {
        return $this->attributes['email'] ?? $this->user?->email ?? 'N/A';
    }

    public function getApplicantPhoneAttribute()
    {
        return $this->attributes['phone'] ?? $this->user?->phone ?? 'N/A';
    }
}
