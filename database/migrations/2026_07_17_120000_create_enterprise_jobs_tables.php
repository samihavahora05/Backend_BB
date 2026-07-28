<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop existing ATS tables if they exist
        Schema::dropIfExists('job_views');
        Schema::dropIfExists('job_bookmarks');
        Schema::dropIfExists('job_activity_logs');
        Schema::dropIfExists('job_documents');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('job_shortlists');
        Schema::dropIfExists('job_interviews');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('jobs');

        // 1. Jobs Table
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id_prefix')->unique(); // e.g. JOB-2026-001
            $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('industry')->nullable();
            
            $table->string('employment_type')->default('Full-Time'); // Full-Time, Part-Time, Contract, Internship
            $table->string('experience_level')->default('Entry-Level'); // Entry, Mid, Senior, Executive
            $table->string('remote_type')->default('Onsite'); // Remote, Hybrid, Onsite
            
            $table->string('location')->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->boolean('hide_salary')->default(false);
            
            $table->text('description')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('requirements')->nullable();
            $table->json('benefits')->nullable();
            $table->json('required_skills')->nullable();
            
            $table->integer('vacancies')->default(1);
            $table->timestamp('application_deadline')->nullable();
            
            $table->string('thumbnail')->nullable();
            $table->string('preview_video')->nullable();
            
            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_keywords')->nullable();
            
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'pending_approval', 'active', 'expired', 'closed', 'rejected'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Job Applications Table
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Student
            
            $table->string('resume_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            
            $table->json('custom_answers')->nullable(); // Array of Q&A
            
            $table->enum('status', [
                'applied', 'under_review', 'shortlisted', 
                'interview_scheduled', 'rejected', 'offer_sent', 
                'accepted', 'joined', 'completed'
            ])->default('applied');
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Job Interviews Table
        Schema::create('job_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete(); // HR/Admin
            
            $table->integer('round_number')->default(1);
            $table->string('mode')->default('google_meet'); // zoom, google_meet, offline
            $table->string('meeting_link')->nullable();
            $table->text('location')->nullable(); // For offline
            $table->timestamp('scheduled_at')->nullable();
            
            $table->integer('marks_obtained')->nullable();
            $table->integer('max_marks')->default(100);
            $table->text('feedback')->nullable();
            
            $table->enum('recommendation', ['hire', 'hold', 'reject', 'pending'])->default('pending');
            $table->timestamps();
        });

        // 4. Job Shortlists Table
        Schema::create('job_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Student
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Job Offers Table
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
            
            $table->decimal('salary_offered', 12, 2)->nullable();
            $table->string('offer_letter_path')->nullable();
            $table->timestamp('valid_until')->nullable();
            
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->text('candidate_notes')->nullable(); // Reason for declining etc.
            $table->timestamps();
        });

        // 6. Job Documents Table (Attachments for the Job Posting itself)
        Schema::create('job_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->timestamps();
        });

        // 7. Job Activity Logs (Audit Trail)
        Schema::create('job_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Who performed the action
            $table->string('action'); // e.g. "Changed status to active", "Shortlisted John Doe"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Job Bookmarks
        Schema::create('job_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Student
            $table->timestamps();
        });

        // 9. Job Views
        Schema::create('job_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('job_views');
        Schema::dropIfExists('job_bookmarks');
        Schema::dropIfExists('job_activity_logs');
        Schema::dropIfExists('job_documents');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('job_shortlists');
        Schema::dropIfExists('job_interviews');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('jobs');
        Schema::enableForeignKeyConstraints();
    }
};
