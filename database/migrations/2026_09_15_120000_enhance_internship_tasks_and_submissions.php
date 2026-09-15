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
        // 1. Enhance internship_tasks table
        if (Schema::hasTable('internship_tasks')) {
            Schema::table('internship_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('internship_tasks', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('internship_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('internship_tasks', 'assigned_to')) {
                    $table->foreignId('assigned_to')->nullable()->after('company_id')->constrained('users')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('internship_tasks', 'assigned_by')) {
                    $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('internship_tasks', 'instructions')) {
                    $table->text('instructions')->nullable()->after('description');
                }
                if (!Schema::hasColumn('internship_tasks', 'expected_deliverable')) {
                    $table->text('expected_deliverable')->nullable()->after('instructions');
                }
                if (!Schema::hasColumn('internship_tasks', 'priority')) {
                    $table->string('priority', 20)->default('medium')->after('type'); // low, medium, high, urgent
                }
                if (!Schema::hasColumn('internship_tasks', 'start_date')) {
                    $table->dateTime('start_date')->nullable()->after('priority');
                }
                if (!Schema::hasColumn('internship_tasks', 'due_date')) {
                    $table->dateTime('due_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('internship_tasks', 'status')) {
                    $table->string('status', 30)->default('assigned')->after('max_marks'); // assigned, in_progress, submitted, under_review, completed, changes_required, overdue
                }
                if (!Schema::hasColumn('internship_tasks', 'marks')) {
                    $table->decimal('marks', 5, 2)->nullable()->after('status');
                }
                if (!Schema::hasColumn('internship_tasks', 'feedback')) {
                    $table->text('feedback')->nullable()->after('marks');
                }
                if (!Schema::hasColumn('internship_tasks', 'completed_at')) {
                    $table->dateTime('completed_at')->nullable()->after('feedback');
                }
            });
        }

        // 2. Enhance internship_submissions table
        if (Schema::hasTable('internship_submissions')) {
            try {
                Schema::table('internship_submissions', function (Blueprint $table) {
                    $table->dropUnique(['task_id', 'user_id']);
                });
            } catch (\Throwable $e) {
                // Ignore if unique index was already dropped
            }

            Schema::table('internship_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('internship_submissions', 'version')) {
                    $table->integer('version')->default(1)->after('user_id');
                }
                if (!Schema::hasColumn('internship_submissions', 'submission_comment')) {
                    $table->text('submission_comment')->nullable()->after('submission_text');
                }
                if (!Schema::hasColumn('internship_submissions', 'proof_files')) {
                    $table->json('proof_files')->nullable()->after('file_paths');
                }
                if (!Schema::hasColumn('internship_submissions', 'reviewer_id')) {
                    $table->foreignId('reviewer_id')->nullable()->after('feedback')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('internship_submissions', 'reviewed_at')) {
                    $table->dateTime('reviewed_at')->nullable()->after('reviewer_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('internship_tasks')) {
            Schema::table('internship_tasks', function (Blueprint $table) {
                $columns = ['company_id', 'assigned_to', 'assigned_by', 'instructions', 'expected_deliverable', 'priority', 'start_date', 'due_date', 'status', 'marks', 'feedback', 'completed_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('internship_tasks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('internship_submissions')) {
            Schema::table('internship_submissions', function (Blueprint $table) {
                $columns = ['version', 'submission_comment', 'proof_files', 'reviewer_id', 'reviewed_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('internship_submissions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
