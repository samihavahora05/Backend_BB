<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Alter existing student_profiles table
        Schema::table('student_profiles', function (Blueprint $table) {
            // Drop redundant JSON columns if they exist
            if (Schema::hasColumn('student_profiles', 'skills')) {
                $table->dropColumn('skills');
            }
            if (Schema::hasColumn('student_profiles', 'certifications')) {
                $table->dropColumn('certifications');
            }
            if (Schema::hasColumn('student_profiles', 'projects')) {
                $table->dropColumn('projects');
            }
            if (Schema::hasColumn('student_profiles', 'linkedin_url')) {
                $table->dropColumn('linkedin_url');
            }
            if (Schema::hasColumn('student_profiles', 'github_url')) {
                $table->dropColumn('github_url');
            }
            if (Schema::hasColumn('student_profiles', 'portfolio_url')) {
                $table->dropColumn('portfolio_url');
            }
            if (Schema::hasColumn('student_profiles', 'resume_path')) {
                $table->dropColumn('resume_path');
            }
            
            // Add new fields
            if (!Schema::hasColumn('student_profiles', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('address_line_1')->nullable();
                $table->string('address_line_2')->nullable();
            }
        });

        // 2. Create Student Education
        Schema::create('student_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('university')->nullable();
            $table->string('college_name')->nullable();
            $table->string('course')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('semester')->nullable();
            $table->year('start_year')->nullable();
            $table->year('end_year')->nullable();
            $table->decimal('cgpa', 4, 2)->nullable();
            $table->timestamps();
        });

        // 3. Create Student Skills
        Schema::create('student_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('skill_name');
            $table->enum('proficiency', ['Beginner', 'Intermediate', 'Advanced', 'Expert'])->default('Intermediate');
            $table->timestamps();
        });

        // 4. Create Student Documents
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['resume', 'id_proof', 'other'])->default('other');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // 5. Create Student Social Links
        Schema::create('student_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('platform'); // github, linkedin, portfolio, twitter
            $table->string('url');
            $table->timestamps();
        });

        // 6. Create Student Preferences
        Schema::create('student_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('notification_preferences')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->timestamps();
        });

        // 7. Create Student Activity Logs
        Schema::create('student_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->text('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // 8. Create Student Notifications
        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('student_notifications');
        Schema::dropIfExists('student_activity_logs');
        Schema::dropIfExists('student_preferences');
        Schema::dropIfExists('student_social_links');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('student_skills');
        Schema::dropIfExists('student_education');
        Schema::enableForeignKeyConstraints();
    }
};
