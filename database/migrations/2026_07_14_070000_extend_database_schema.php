<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Missing columns in existing tables
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('price');
            }
            if (!Schema::hasColumn('courses', 'skills')) {
                $table->json('skills')->nullable()->after('thumbnail_url');
            }
        });

        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'video_url')) {
                $table->string('video_url')->nullable()->after('content');
            }
            if (!Schema::hasColumn('lessons', 'pdf_url')) {
                $table->string('pdf_url')->nullable()->after('video_url');
            }
            if (!Schema::hasColumn('lessons', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(0)->after('pdf_url');
            }
        });

        Schema::table('mentor_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('mentor_sessions', 'topic')) {
                $table->string('topic')->nullable()->after('meeting_url');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'invoice_url')) {
                $table->string('invoice_url')->nullable()->after('amount');
            }
        });

        // 2. New tables
        Schema::create('intern_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('week_number');
            $table->text('learnings');
            $table->text('challenges')->nullable();
            $table->timestamps();
        });

        Schema::create('intern_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->string('project_name');
            $table->enum('status', ['In Progress', 'Review', 'Completed'])->default('In Progress');
            $table->integer('progress_percent')->default(0);
            $table->string('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'job_id']);
        });

        Schema::create('saved_internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'internship_id']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->string('meeting_url')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->string('title');
            $table->json('questions');
            $table->integer('max_points')->default(100);
            $table->timestamps();
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blog_tag_pivot', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('blog_tags')->onDelete('cascade');
            $table->primary(['blog_id', 'tag_id']);
        });

        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_path')->unique();
            $table->string('title');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('blog_tag_pivot');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('saved_internships');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('intern_projects');
        Schema::dropIfExists('intern_journals');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('invoice_url');
        });
        Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'pdf_url', 'duration_minutes']);
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_url', 'skills']);
        });
    }
};
