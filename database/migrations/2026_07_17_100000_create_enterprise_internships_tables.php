<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Drop existing internship tables safely if they exist to avoid conflicts
        Schema::dropIfExists('internship_evaluations');
        Schema::dropIfExists('internship_attendance');
        Schema::dropIfExists('internship_feedback');
        Schema::dropIfExists('internship_activity_logs');
        Schema::dropIfExists('internship_documents');
        Schema::dropIfExists('internship_submissions');
        Schema::dropIfExists('internship_tasks');
        Schema::dropIfExists('internship_applications');
        Schema::dropIfExists('saved_internships');
        Schema::dropIfExists('internships');
        
        Schema::enableForeignKeyConstraints();

        // 2. Re-create internships table
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->enum('mode', ['Remote', 'Hybrid', 'Onsite'])->default('Remote');
            $table->integer('duration_months')->nullable();
            $table->string('duration')->nullable(); // e.g. "6 Months", "10 Weeks"
            $table->decimal('stipend', 10, 2)->nullable();
            
            $table->json('skills_required')->nullable();
            $table->text('eligibility')->nullable();
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('learning_outcomes')->nullable();
            
            $table->integer('openings')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('application_deadline')->nullable();
            
            $table->enum('status', ['open', 'closed', 'draft', 'archived'])->default('draft');
            $table->boolean('featured')->default(false);
            
            $table->string('thumbnail')->nullable();
            $table->string('preview_image')->nullable();
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Re-create applications table
        Schema::create('internship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->enum('status', [
                'applied', 'under_review', 'shortlisted', 'interview', 'rejected', 'selected', 'offer_sent', 'joined', 'completed'
            ])->default('applied');
            
            $table->string('resume_url')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('custom_answers')->nullable();
            
            $table->text('internal_notes')->nullable();
            
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['internship_id', 'user_id']); // One application per user per internship
        });

        // 4. Tasks Table
        Schema::create('internship_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete(); // Admin/Mentor who created it
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['daily', 'weekly', 'project'])->default('weekly');
            $table->dateTime('deadline')->nullable();
            $table->json('attachments')->nullable();
            $table->decimal('max_marks', 5, 2)->nullable();
            
            $table->timestamps();
        });

        // 5. Task Submissions
        Schema::create('internship_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('internship_tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->text('submission_text')->nullable();
            $table->json('file_paths')->nullable();
            $table->string('github_link')->nullable();
            $table->string('video_link')->nullable();
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'resubmit'])->default('pending');
            $table->decimal('marks_obtained', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            
            $table->timestamps();
            
            $table->unique(['task_id', 'user_id']);
        });

        // 6. Evaluations
        Schema::create('internship_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->decimal('attendance_score', 5, 2)->nullable();
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            
            $table->boolean('is_eligible_for_certificate')->default(false);
            $table->text('mentor_feedback')->nullable();
            $table->text('admin_feedback')->nullable();
            
            $table->timestamps();
        });

        // 7. Attendance
        Schema::create('internship_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'leave', 'holiday'])->default('present');
            $table->timestamps();
            
            $table->unique(['internship_id', 'user_id', 'date']);
        });

        // 8. Documents (Offers, Certificates)
        Schema::create('internship_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->enum('document_type', ['offer_letter', 'certificate', 'contract', 'other'])->default('other');
            $table->string('file_path');
            
            $table->timestamps();
        });

        // 9. Activity Logs
        Schema::create('internship_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // The student or admin involved
            $table->string('action'); // e.g. "Application Submitted", "Task Assigned"
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::dropIfExists('internship_activity_logs');
        Schema::dropIfExists('internship_documents');
        Schema::dropIfExists('internship_attendance');
        Schema::dropIfExists('internship_evaluations');
        Schema::dropIfExists('internship_submissions');
        Schema::dropIfExists('internship_tasks');
        Schema::dropIfExists('internship_applications');
        Schema::dropIfExists('internships');
        
        Schema::enableForeignKeyConstraints();
    }
};
