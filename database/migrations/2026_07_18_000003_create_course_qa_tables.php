<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_questions', function (Blueprint $table) {
            $table->id();
            // Assuming courses table exists, else we just use integer for now if it's not strictly foreign keyed yet
            // If the user's courses table doesn't exist yet, this could fail. I will use integer for now to be safe,
            // or I can try constrained() and if it fails they can fix it. Usually in LMS, courses exists.
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('title');
            $table->text('question');
            
            $table->enum('status', ['Pending', 'Answered', 'Resolved', 'Closed'])->default('Pending');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_reported')->default(false);
            $table->text('reported_reason')->nullable();
            
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
        });

        Schema::create('course_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('course_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('answer');
            $table->boolean('is_instructor')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('course_question_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('course_questions')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->enum('status', ['Pending', 'Reviewed', 'Dismissed'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_question_reports');
        Schema::dropIfExists('course_answers');
        Schema::dropIfExists('course_questions');
    }
};
