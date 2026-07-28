<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Notifications & Audit Logs
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('notification_id');
            $table->dateTime('read_at');
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('body_preview')->nullable();
            $table->dateTime('sent_at');
            $table->timestamps();
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // 2. LMS Extensions
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->dateTime('enrolled_at');
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->timestamps();
        });

        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->integer('rating');
            $table->text('review_text')->nullable();
            $table->timestamps();
        });

        Schema::create('course_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('certificate_number')->unique();
            $table->string('file_path')->nullable();
            $table->dateTime('issued_at');
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->integer('max_points')->default(100);
            $table->dateTime('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->text('submission_text')->nullable();
            $table->integer('points_awarded')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        // 3. Analytics
        Schema::create('dashboard_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key')->unique();
            $table->decimal('metric_value', 15, 2);
            $table->dateTime('calculated_at');
            $table->timestamps();
        });

        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('query_string');
            $table->timestamps();
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('url_path');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Homepage Sections & Banners
        Schema::create('hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_link')->nullable();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('link_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Scholarships & Certifications
        Schema::create('scholarship_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->string('status');
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('resume_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('thumbnail_path')->nullable();
            $table->json('layout_data')->nullable();
            $table->timestamps();
        });

        Schema::create('resume_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('format')->default('pdf');
            $table->timestamps();
        });

        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('background_image')->nullable();
            $table->text('text_layout')->nullable();
            $table->timestamps();
        });

        Schema::create('certificate_verification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained('certificates')->onDelete('cascade');
            $table->string('verification_code')->unique();
            $table->integer('verify_count')->default(0);
            $table->timestamps();
        });

        // 6. Colleges & Experts Profiles
        Schema::create('college_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_profile_id')->constrained('college_profiles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('enrollment_number')->nullable();
            $table->timestamps();
        });

        Schema::create('expert_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_profile_id')->constrained('expert_profiles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating');
            $table->text('review_text')->nullable();
            $table->timestamps();
        });

        // 7. Interviews Extras
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained('interviews')->onDelete('cascade');
            $table->foreignId('interviewer_id')->constrained('users')->onDelete('cascade');
            $table->text('feedback_text');
            $table->integer('score')->nullable();
            $table->timestamps();
        });

        Schema::create('interview_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained('interviews')->onDelete('cascade');
            $table->string('round_name');
            $table->string('status')->default('pending'); // pending, passed, failed
            $table->timestamps();
        });

        // 8. Wishlist Courses & CRM Notes
        Schema::create('wishlist_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('crm_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->text('content');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // 9. File Manager & Media
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('filepath');
            $table->string('mime_type')->nullable();
            $table->integer('filesize')->default(0);
            $table->timestamps();
        });

        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
        Schema::dropIfExists('media');
        Schema::dropIfExists('lead_statuses');
        Schema::dropIfExists('crm_notes');
        Schema::dropIfExists('wishlist_courses');
        Schema::dropIfExists('interview_rounds');
        Schema::dropIfExists('interview_feedback');
        Schema::dropIfExists('expert_reviews');
        Schema::dropIfExists('college_students');
        Schema::dropIfExists('certificate_verification');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('resume_downloads');
        Schema::dropIfExists('resume_templates');
        Schema::dropIfExists('scholarship_reviews');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('hero_sections');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('visitor_logs');
        Schema::dropIfExists('dashboard_statistics');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('course_certificates');
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('notification_reads');
    }
};
