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
        'bio',
        'date_of_birth',
        'gender',
        'profile_photo',
        'skills',
        'status',
        'student_type',
        'job_title',
        'company_name',
        'identification_number',
        'pin',
        'address_line_1',
        'address_line_2',
        'emergency_contact_name',
        'emergency_contact_phone',
        'city',
        'state',
        'country',
        'resume_path'
    ];

    /**
     * Cast fields for automatic data conversion.
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'skills' => 'json',
        ];
    }

    // Core Link
    public function user() { return $this->belongsTo(User::class); }
}
