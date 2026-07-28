<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Foreign Key Cascades

        // Job Applications -> Jobs
        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                // Drop existing FK if any
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_applications' AND REFERENCED_TABLE_NAME IS NOT NULL");
                foreach ($foreignKeys as $fk) {
                    if (str_contains($fk->CONSTRAINT_NAME, 'job_id')) {
                        $table->dropForeign([ 'job_id' ]);
                    }
                }
            });
            Schema::table('job_applications', function (Blueprint $table) {
                $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            });
        }

        // Internship Applications -> Internships
        if (Schema::hasTable('internship_applications')) {
            Schema::table('internship_applications', function (Blueprint $table) {
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'internship_applications' AND REFERENCED_TABLE_NAME IS NOT NULL");
                foreach ($foreignKeys as $fk) {
                    if (str_contains($fk->CONSTRAINT_NAME, 'internship_id')) {
                        $table->dropForeign([ 'internship_id' ]);
                    }
                }
            });
            Schema::table('internship_applications', function (Blueprint $table) {
                $table->foreign('internship_id')->references('id')->on('internships')->onDelete('cascade');
            });
        }



        // 2. Unique Constraints for Duplicate Prevention
        
        // Course Enrollments (user_id + course_id)
        if (Schema::hasTable('course_enrollments')) {
            // Check if unique index exists to avoid duplicates error
            $indexes = DB::select("SHOW INDEXES FROM course_enrollments");
            $hasUnique = false;
            foreach ($indexes as $index) {
                if ($index->Key_name === 'course_enrollments_user_id_course_id_unique') {
                    $hasUnique = true;
                    break;
                }
            }
            if (!$hasUnique) {
                // Clear any existing duplicates before adding unique constraint
                DB::statement("DELETE t1 FROM course_enrollments t1 INNER JOIN course_enrollments t2 WHERE t1.id < t2.id AND t1.user_id = t2.user_id AND t1.course_id = t2.course_id");
                
                Schema::table('course_enrollments', function (Blueprint $table) {
                    $table->unique(['user_id', 'course_id']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would involve dropping unique indices and recreating FKs without cascade
        // Usually not strictly required for this structural hotfix, but good practice.
    }
};
