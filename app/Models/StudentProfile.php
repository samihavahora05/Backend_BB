<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Prevent mass-assignment vulnerabilities.
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'profile_photo',
        'education_level',
        'college_name',
        'university',
        'course',
        'specialization',
        'graduation_year',
        'skills',
        'certifications',
        'projects',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'resume_path',
        'bio',
        'city',
        'state',
        'country',
        'pincode',
        'profile_completion',
        'is_verified',
        'status',
        'student_type',
        'job_title',
        'company_name',
        'identification_number',
        'pin',
        'address_line_1',
        'address_line_2',
        'emergency_contact_name',
        'emergency_contact_phone'
    ];

    /**
     * Cast fields for automatic data conversion.
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'skills' => 'json',
            'certifications' => 'json',
            'projects' => 'json',
            'is_verified' => 'boolean',
            'profile_completion' => 'integer',
        ];
    }

    // Core Link
    public function user() { return $this->belongsTo(User::class); }
}
