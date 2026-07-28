<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop existing to recreate with Enterprise Schema
        Schema::dropIfExists('expert_course_assignments');
        Schema::dropIfExists('expert_activity_logs');
        Schema::dropIfExists('expert_reviews');
        Schema::dropIfExists('expert_certificates');
        Schema::dropIfExists('expert_languages');
        Schema::dropIfExists('expert_documents');
        Schema::dropIfExists('expert_skills');
        Schema::dropIfExists('expert_profiles');

        // 1. Enterprise Expert Profiles
        Schema::create('expert_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Basic Info
            $table->string('designation')->nullable(); // e.g. "Senior Software Engineer @ Google"
            $table->string('company')->nullable();
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->string('highest_qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->string('profile_photo')->nullable();
            
            // Financials & Availability
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->boolean('is_available')->default(true);
            
            // Social & Links
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('website')->nullable();
            
            // Metrics (Dashboard Aggregated)
            $table->integer('profile_completion_percentage')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_courses_sold')->default(0);
            $table->integer('total_students')->default(0);
            $table->integer('total_certificates_issued')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0); // Course Completion Rate by students
            $table->decimal('student_satisfaction', 5, 2)->default(0);
            
            // Workflow Status
            $table->boolean('is_verified')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Expert Skills
        Schema::create('expert_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('skill_name');
            $table->string('proficiency')->default('Intermediate'); // Beginner, Intermediate, Expert
            $table->timestamps();
        });

        // 3. Expert Documents (Resume, ID Proof)
        Schema::create('expert_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type'); // resume, id_proof, portfolio
            $table->string('file_path');
            $table->timestamps();
        });

        // 4. Expert Languages
        Schema::create('expert_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('language');
            $table->string('proficiency')->default('Fluent');
            $table->timestamps();
        });

        // 5. Expert Certificates
        Schema::create('expert_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('issuer');
            $table->date('issue_date')->nullable();
            $table->string('certificate_url')->nullable();
            $table->timestamps();
        });

        // 6. Expert Reviews (From Students)
        Schema::create('expert_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->integer('rating')->default(5);
            $table->text('review_text')->nullable();
            $table->timestamps();
        });

        // 7. Expert Activity Logs (Audit Trail for Admin actions)
        Schema::create('expert_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // "Approved", "Suspended", "Password Reset"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Expert Course Assignments
        Schema::create('expert_course_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('role')->default('Primary Instructor'); // Primary, Co-Instructor
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('expert_course_assignments');
        Schema::dropIfExists('expert_activity_logs');
        Schema::dropIfExists('expert_reviews');
        Schema::dropIfExists('expert_certificates');
        Schema::dropIfExists('expert_languages');
        Schema::dropIfExists('expert_documents');
        Schema::dropIfExists('expert_skills');
        Schema::dropIfExists('expert_profiles');
        Schema::enableForeignKeyConstraints();
    }
};
