<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. System & Security Core
        if (!Schema::hasTable('global_settings')) {
            Schema::create('global_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string'); // string, json, boolean, file
                $table->string('group')->default('general'); // general, api, email, theme
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->string('service_name')->unique();
                $table->text('encrypted_key');
                $table->boolean('is_enabled')->default(true);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address')->index();
                $table->enum('type', ['blocked', 'failed_login', 'otp_failed', 'suspicious_login']);
                $table->integer('attempts')->default(1);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admin_sessions')) {
            Schema::create('admin_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('login_at');
                $table->timestamp('logout_at')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('device')->nullable();
                $table->string('browser')->nullable();
                $table->string('location')->nullable();
                $table->enum('status', ['active', 'expired', 'logged_out'])->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('backup_histories')) {
            Schema::create('backup_histories', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->bigInteger('size_bytes');
                $table->string('download_url')->nullable();
                $table->enum('status', ['pending', 'completed', 'failed', 'restored'])->default('completed');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 2. Task & Submission Systems
        if (!Schema::hasTable('internship_tasks')) {
            Schema::create('internship_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
                $table->string('title');
                $table->text('description');
                $table->date('due_date');
                $table->integer('max_marks')->default(100);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('internship_submissions')) {
            Schema::create('internship_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('internship_task_id')->constrained('internship_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('content')->nullable();
                $table->json('attachments')->nullable();
                $table->integer('marks_obtained')->nullable();
                $table->text('feedback')->nullable();
                $table->enum('status', ['submitted', 'graded', 'late'])->default('submitted');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('contest_tasks')) {
            Schema::create('contest_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contest_id')->constrained('contests')->cascadeOnDelete();
                $table->string('title');
                $table->text('description');
                $table->json('rules')->nullable();
                $table->integer('max_score')->default(100);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('contest_submissions')) {
            Schema::create('contest_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contest_task_id')->constrained('contest_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('repository_url')->nullable();
                $table->json('attachments')->nullable();
                $table->integer('score')->nullable();
                $table->text('feedback')->nullable();
                $table->boolean('is_winner')->default(false);
                $table->timestamps();
            });
        }

        // 3. Approval & Verification Auditing
        if (!Schema::hasTable('course_approvals')) {
            Schema::create('course_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->enum('action', ['approved', 'rejected', 'revision_requested']);
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('internship_approvals')) {
            Schema::create('internship_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->enum('action', ['approved', 'rejected', 'revision_requested']);
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('company_verifications')) {
            Schema::create('company_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();
                $table->json('documents'); // GST, PAN, etc.
                $table->enum('kyc_status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('data_audit_logs')) {
            Schema::create('data_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('table_name')->index();
                $table->string('column_name')->nullable();
                $table->bigInteger('record_id')->index();
                $table->enum('action', ['created', 'updated', 'deleted']);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address')->nullable();
                $table->string('device')->nullable();
                $table->timestamps();
            });
        }

        // 4. Performance & Analytics
        if (!Schema::hasTable('student_progress')) {
            Schema::create('student_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->decimal('completion_percent', 5, 2)->default(0);
                $table->integer('learning_hours')->default(0);
                $table->integer('average_quiz_score')->default(0);
                $table->integer('average_assignment_score')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'course_id']);
            });
        }

        if (!Schema::hasTable('college_performances')) {
            Schema::create('college_performances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('college_id')->constrained('users')->cascadeOnDelete();
                $table->integer('total_students')->default(0);
                $table->decimal('placement_percent', 5, 2)->default(0);
                $table->integer('total_internships')->default(0);
                $table->integer('total_certificates')->default(0);
                $table->decimal('performance_score', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_cache')) {
            Schema::create('analytics_cache', function (Blueprint $table) {
                $table->string('metric_key')->primary();
                $table->json('data');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('generated_reports')) {
            Schema::create('generated_reports', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // placement, revenue, student_performance
                $table->string('file_url');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 5. Communication Queues
        if (!Schema::hasTable('email_queues')) {
            Schema::create('email_queues', function (Blueprint $table) {
                $table->id();
                $table->string('recipient');
                $table->string('subject');
                $table->string('template');
                $table->json('payload')->nullable();
                $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
                $table->integer('attempts')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notification_queues')) {
            Schema::create('notification_queues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('type', ['push', 'sms', 'in_app']);
                $table->string('title');
                $table->text('message');
                $table->enum('delivery_status', ['pending', 'delivered', 'failed'])->default('pending');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // 6. CMS & Media Manager
        if (!Schema::hasTable('cms_pages')) {
            Schema::create('cms_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->boolean('is_published')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('cms_sections')) {
            Schema::create('cms_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cms_page_id')->constrained('cms_pages')->cascadeOnDelete();
                $table->string('section_key'); // hero, about, footer
                $table->json('content'); // flexible content block
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // Media tables moved to dedicated 020000_create_media_manager_tables.php migration;
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('cms_sections');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('notification_queues');
        Schema::dropIfExists('email_queues');
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('analytics_cache');
        Schema::dropIfExists('college_performances');
        Schema::dropIfExists('student_progress');
        Schema::dropIfExists('data_audit_logs');
        Schema::dropIfExists('company_verifications');
        Schema::dropIfExists('internship_approvals');
        Schema::dropIfExists('course_approvals');
        Schema::dropIfExists('contest_submissions');
        Schema::dropIfExists('contest_tasks');
        Schema::dropIfExists('internship_submissions');
        Schema::dropIfExists('internship_tasks');
        Schema::dropIfExists('backup_histories');
        Schema::dropIfExists('admin_sessions');
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('global_settings');
    }
};
