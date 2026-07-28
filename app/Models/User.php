<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     * Strict assignment protection.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'status',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Security: Never leak tokens or passwords to the frontend API.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'name',
    ];

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function ($user) {
            // Polymorphic relation cascades
            $user->notifications()->delete();
        });
    }

    // ------------------------------------------------------------------------
    // RELATIONSHIPS - PROFILES
    // ------------------------------------------------------------------------
    public function studentProfile() { return $this->hasOne(StudentProfile::class); }
    public function expertProfile() { return $this->hasOne(ExpertProfile::class); }
    public function companyProfile() { return $this->hasOne(CompanyProfile::class); }
    public function collegeProfile() { return $this->hasOne(CollegeProfile::class); }
    public function internProfile() { return $this->hasOne(InternProfile::class); }
    public function jobSeekerProfile() { return $this->hasOne(JobSeekerProfile::class); }
    
    // ------------------------------------------------------------------------
    // RELATIONSHIPS - APP DATA
    // ------------------------------------------------------------------------
    public function deviceTokens() { return $this->hasMany(DeviceToken::class); }
    public function courseEnrollments() { return $this->hasMany(CourseEnrollment::class); }
    public function mentorBookings() { return $this->hasMany(MentorBooking::class); }
    
    // ------------------------------------------------------------------------
    // RELATIONSHIPS - ENTERPRISE STUDENT
    // ------------------------------------------------------------------------
    public function education() { return $this->hasMany(StudentEducation::class); }
    public function skills() { return $this->hasMany(StudentSkill::class); }
    public function documents() { return $this->hasMany(StudentDocument::class); }
    public function socialLinks() { return $this->hasMany(StudentSocialLink::class); }
    public function preferences() { return $this->hasOne(StudentPreference::class); }
    public function activityLogs() { return $this->hasMany(StudentActivityLog::class); }
    public function studentNotifications() { return $this->hasMany(StudentNotification::class); }
    
    // Internship integrations
    public function internshipApplications() { return $this->hasMany(InternshipApplication::class); }
    public function internshipSubmissions() { return $this->hasMany(InternshipSubmission::class); }
    public function internshipEvaluations() { return $this->hasMany(InternshipEvaluation::class); }
    public function internshipAttendance() { return $this->hasMany(InternshipAttendance::class); }
    
    // Orders & Payments
    public function orders() { return $this->hasMany(Order::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    
    // ------------------------------------------------------------------------
    // RELATIONSHIPS - ENTERPRISE ATS JOBS
    // ------------------------------------------------------------------------
    public function postedJobs() { return $this->hasMany(Job::class, 'company_id'); }
    
    // College Placements
    public function placementDrives() { return $this->hasMany(Job::class, 'college_id'); }
    public function collegeInternships() { return $this->hasMany(Internship::class, 'college_id'); }
    public function collegeContests() { return $this->hasMany(Contest::class, 'college_id'); }
    public function partnerCompanies() { return $this->belongsToMany(User::class, 'college_company_partnerships', 'college_id', 'company_id')->withPivot('status')->withTimestamps(); }
    
    public function jobApplications() { return $this->hasMany(JobApplication::class); }
    public function jobInterviewsConducted() { return $this->hasMany(JobInterview::class, 'interviewer_id'); }
    public function jobShortlists() { return $this->hasMany(JobShortlist::class); }
    public function jobBookmarks() { return $this->hasMany(JobBookmark::class); }
    public function jobViews() { return $this->hasMany(JobView::class); }
    
    // ------------------------------------------------------------------------
    // RELATIONSHIPS - ENTERPRISE INSTRUCTORS (EXPERTS)
    // ------------------------------------------------------------------------
    public function expertSkills() { return $this->hasMany(ExpertSkill::class); }
    public function expertDocuments() { return $this->hasMany(ExpertDocument::class); }
    public function expertLanguages() { return $this->hasMany(ExpertLanguage::class); }
    public function expertCertificates() { return $this->hasMany(ExpertCertificate::class); }
    public function expertReviewsReceived() { return $this->hasMany(ExpertReview::class, 'expert_id'); }
    public function expertReviewsGiven() { return $this->hasMany(ExpertReview::class, 'student_id'); }
    public function expertActivityLogs() { return $this->hasMany(ExpertActivityLog::class, 'expert_id'); }
    public function expertCourseAssignments() { return $this->hasMany(ExpertCourseAssignment::class, 'expert_id'); }
    
    // Polymorphic Relationship for Media Uploads
    public function media() { return $this->morphMany(Media::class, 'model'); }
    
    // ------------------------------------------------------------------------
    // RELATIONSHIPS - DELETE REQUESTS
    // ------------------------------------------------------------------------
    public function deleteRequests() { return $this->hasMany(DeleteRequest::class); }
}
