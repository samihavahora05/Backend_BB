<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Companies Module Extras
        Schema::create('company_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->boolean('is_headquarters')->default(false);
            $table->timestamps();
        });

        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_name');
            $table->string('document_url');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('company_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Company User
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade'); // Recruiter/Member User
            $table->string('role')->default('recruiter');
            $table->timestamps();
        });

        Schema::create('company_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('platform'); // linkedin, twitter, website etc.
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('company_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('image_url');
            $table->timestamps();
        });

        // 2. Colleges Module Extras
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // College User
            $table->string('name');
            $table->string('head_of_department')->nullable();
            $table->timestamps();
        });

        Schema::create('placement_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // College User
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('placement_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // College User
            $table->string('student_name');
            $table->string('company_name');
            $table->decimal('package_lpa', 8, 2);
            $table->integer('placement_year');
            $table->timestamps();
        });

        Schema::create('recruiter_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // College User
            $table->string('company_name');
            $table->dateTime('visit_date');
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->timestamps();
        });

        // 3. Experts Module Extras
        Schema::create('expert_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('day_of_week'); // Monday, Tuesday etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::create('expert_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('expert_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('issuing_organization');
            $table->string('credential_url')->nullable();
            $table->timestamps();
        });

        Schema::create('expert_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('platform');
            $table->string('url');
            $table->timestamps();
        });

        // 4. Resume Builder
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->default('My Resume');
            $table->text('summary')->nullable();
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->string('template_name')->default('classic');
            $table->timestamps();
        });

        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->integer('version_number')->default(1);
            $table->json('data_json');
            $table->timestamps();
        });

        // 5. Contact & CRM
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('unread'); // unread, read, archived
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->default('website'); // referral, contact_form, banner etc.
            $table->string('status')->default('new'); // new, contacted, warm, closed_won, closed_lost
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->text('note_text');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->text('note_text')->nullable();
            $table->string('status')->default('pending'); // pending, completed, missed
            $table->timestamps();
        });

        // 6. Contests & Scholarships
        Schema::create('contest_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('contests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_url')->nullable();
            $table->text('submission_text')->nullable();
            $table->integer('score')->nullable();
            $table->timestamps();
        });

        Schema::create('contest_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('contests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('rank');
            $table->string('prize_title')->nullable();
            $table->timestamps();
        });

        Schema::create('scholarship_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->onDelete('cascade');
            $table->string('doc_name');
            $table->string('file_url');
            $table->timestamps();
        });

        Schema::create('scholarship_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->onDelete('cascade');
            $table->string('status');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // 7. CMS Homepage Extensions
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('title');
            $table->json('content_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_url')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_banners');
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('scholarship_status_history');
        Schema::dropIfExists('scholarship_documents');
        Schema::dropIfExists('contest_winners');
        Schema::dropIfExists('contest_submissions');
        Schema::dropIfExists('followups');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('resume_versions');
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('expert_social_links');
        Schema::dropIfExists('expert_certifications');
        Schema::dropIfExists('expert_specializations');
        Schema::dropIfExists('expert_availability');
        Schema::dropIfExists('recruiter_visits');
        Schema::dropIfExists('placement_records');
        Schema::dropIfExists('placement_officers');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('company_gallery');
        Schema::dropIfExists('company_social_links');
        Schema::dropIfExists('company_members');
        Schema::dropIfExists('company_documents');
        Schema::dropIfExists('company_locations');
    }
};
