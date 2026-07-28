<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Prevent mass-assignment vulnerabilities.
     */
    protected $fillable = [
        'category_id',
        'expert_id', // Add expert_id
        'title',
        'slug',
        'short_description', // New
        'description',
        'thumbnail',
        'preview_video_url', // New
        'demo_pdf_url', // New
        'landing_page_url', // New
        'price',
        'discount_price', // New
        'course_type', // New
        'level_id', // Replaced 'level'
        'language', // New
        'duration', // New
        'duration_hours', // Original
        'status', // New
        'is_featured', // New
        'is_archived', // New
        'is_published', // Original
    ];

    /**
     * Cast fields for automatic data conversion.
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_archived' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------------
    // RELATIONSHIPS
    // ------------------------------------------------------------------------
    
    // Core Link
    public function category() { return $this->belongsTo(CourseCategory::class); }
    public function level() { return $this->belongsTo(CourseLevel::class); }
    public function expert() { return $this->belongsTo(User::class, 'expert_id'); }
    public function instructors() { return $this->belongsToMany(ExpertProfile::class, 'expert_courses'); }

    // Course Content (LMS)
    public function modules() { return $this->hasMany(Module::class); }
    public function assignments() { return $this->hasMany(Assignment::class); }
    
    // Users & E-Commerce
    public function enrollments() { return $this->hasMany(CourseEnrollment::class); }
    public function reviews() { return $this->hasMany(CourseReview::class); }
    public function wishlists() { return $this->hasMany(WishlistCourse::class); }
    public function certificates() { return $this->hasMany(CourseCertificate::class); }
    public function studentProgress() { return $this->hasMany(StudentProgress::class); }
}
