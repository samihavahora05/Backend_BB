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
        if (Schema::hasTable('internship_tasks')) {
            Schema::table('internship_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('internship_tasks', 'duration_minutes')) {
                    $table->integer('duration_minutes')->default(25)->after('due_date');
                }
                if (!Schema::hasColumn('internship_tasks', 'analysis_duration_minutes')) {
                    $table->integer('analysis_duration_minutes')->default(5)->after('duration_minutes');
                }
                if (!Schema::hasColumn('internship_tasks', 'started_at')) {
                    $table->dateTime('started_at')->nullable()->after('analysis_duration_minutes');
                }
                if (!Schema::hasColumn('internship_tasks', 'time_taken_seconds')) {
                    $table->integer('time_taken_seconds')->nullable()->after('started_at');
                }
                if (!Schema::hasColumn('internship_tasks', 'is_delayed')) {
                    $table->boolean('is_delayed')->default(false)->after('time_taken_seconds');
                }
                if (!Schema::hasColumn('internship_tasks', 'self_analysis')) {
                    $table->text('self_analysis')->nullable()->after('is_delayed');
                }
                if (!Schema::hasColumn('internship_tasks', 'self_analysis_submitted_at')) {
                    $table->dateTime('self_analysis_submitted_at')->nullable()->after('self_analysis');
                }
            });
        }

        if (Schema::hasTable('internship_submissions')) {
            Schema::table('internship_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('internship_submissions', 'time_taken_seconds')) {
                    $table->integer('time_taken_seconds')->nullable()->after('submission_comment');
                }
                if (!Schema::hasColumn('internship_submissions', 'is_delayed')) {
                    $table->boolean('is_delayed')->default(false)->after('time_taken_seconds');
                }
                if (!Schema::hasColumn('internship_submissions', 'self_analysis')) {
                    $table->text('self_analysis')->nullable()->after('is_delayed');
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
                $columns = ['duration_minutes', 'analysis_duration_minutes', 'started_at', 'time_taken_seconds', 'is_delayed', 'self_analysis', 'self_analysis_submitted_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('internship_tasks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('internship_submissions')) {
            Schema::table('internship_submissions', function (Blueprint $table) {
                $columns = ['time_taken_seconds', 'is_delayed', 'self_analysis'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('internship_submissions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
