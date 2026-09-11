<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Assessments master table
        if (!Schema::hasTable('assessments')) {
            Schema::create('assessments', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('total_questions')->default(100);
                $table->integer('total_marks')->default(100);
                $table->integer('passing_percentage')->default(50);
                $table->integer('duration_minutes')->nullable()->default(120);
                $table->enum('status', ['active', 'draft', 'archived'])->default('active');
                $table->json('category_breakdown')->nullable();
                $table->timestamps();
            });
        }

        // 2. Assessment Questions
        if (!Schema::hasTable('assessment_questions')) {
            Schema::create('assessment_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
                $table->integer('order')->default(1);
                $table->string('category'); // 'Web Development', 'Digital Marketing', 'Graphic Designing'
                $table->text('question');
                $table->text('option_a');
                $table->text('option_b');
                $table->text('option_c');
                $table->text('option_d');
                $table->enum('correct_answer', ['A', 'B', 'C', 'D']);
                $table->text('explanation')->nullable();
                $table->integer('marks')->default(1);
                $table->timestamps();

                $table->index(['assessment_id', 'order']);
                $table->index(['assessment_id', 'category']);
            });
        }

        // 3. Assessment Attempts (Intern specific)
        if (!Schema::hasTable('assessment_attempts')) {
            Schema::create('assessment_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('submitted_at')->nullable();
                $table->integer('total_questions')->default(100);
                $table->integer('correct_answers')->default(0);
                $table->integer('wrong_answers')->default(0);
                $table->integer('unanswered')->default(100);
                $table->integer('score')->default(0);
                $table->decimal('percentage', 5, 2)->default(0.00);
                $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
                $table->json('category_scores')->nullable(); // Breakdown by subject
                $table->timestamps();

                $table->index(['assessment_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // 4. Assessment Answers (Individual responses)
        if (!Schema::hasTable('assessment_answers')) {
            Schema::create('assessment_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('assessment_attempts')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('assessment_questions')->cascadeOnDelete();
                $table->enum('selected_answer', ['A', 'B', 'C', 'D'])->nullable();
                $table->enum('correct_answer', ['A', 'B', 'C', 'D'])->nullable();
                $table->boolean('is_correct')->nullable();
                $table->integer('marks_obtained')->default(0);
                $table->timestamps();

                $table->unique(['attempt_id', 'question_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
    }
};
