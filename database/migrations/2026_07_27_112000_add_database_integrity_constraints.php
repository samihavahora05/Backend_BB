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
            try {
                Schema::table('job_applications', function (Blueprint $table) {
                    $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
                });
            } catch (\Exception $e) {}
        }

        // Internship Applications -> Internships
        if (Schema::hasTable('internship_applications')) {
            try {
                Schema::table('internship_applications', function (Blueprint $table) {
                    $table->foreign('internship_id')->references('id')->on('internships')->onDelete('cascade');
                });
            } catch (\Exception $e) {}
        }

        // 2. Unique Constraints for Duplicate Prevention
        
        // Course Enrollments (user_id + course_id)
        if (Schema::hasTable('course_enrollments')) {
            try {
                Schema::table('course_enrollments', function (Blueprint $table) {
                    $table->unique(['user_id', 'course_id']);
                });
            } catch (\Exception $e) {}
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
