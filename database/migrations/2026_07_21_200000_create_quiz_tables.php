<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quiz Questions (MCQ)
        if (!Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
                $table->text('question');
                $table->enum('type', ['single', 'multiple', 'true_false'])->default('single');
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // Quiz Answers (Options)
        if (!Schema::hasTable('quiz_answers')) {
            Schema::create('quiz_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
                $table->text('answer_text');
                $table->boolean('is_correct')->default(false);
                $table->timestamps();
            });
        }

        // Quiz Attempts (Student submissions)
        if (!Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->integer('score')->default(0);
                $table->boolean('passed')->default(false);
                $table->timestamps();

                $table->index(['quiz_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_questions');
    }
};
