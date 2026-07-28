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
        Schema::table('jobs', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->constrained('users')->onDelete('cascade')->after('company_id');
            $table->string('drive_type')->default('regular')->after('college_id'); // regular, placement_drive, campus_job
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->constrained('users')->onDelete('cascade')->after('company_id');
            $table->string('drive_type')->default('regular')->after('college_id'); // regular, internship_drive
        });

        Schema::table('contests', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->constrained('users')->onDelete('cascade')->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');
            $table->dropColumn('drive_type');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');
            $table->dropColumn('drive_type');
        });

        Schema::table('contests', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');
        });
    }
};
