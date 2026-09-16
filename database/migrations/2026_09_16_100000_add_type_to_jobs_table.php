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
        if (!Schema::hasColumn('jobs', 'type')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->string('type')->default('job')->after('company_id')->index();
            });
        }

        // Backfill existing records: Set type to 'internship' if employment_type is 'Internship', otherwise 'job'
        try {
            DB::table('jobs')
                ->whereRaw('LOWER(employment_type) = ?', ['internship'])
                ->update(['type' => 'internship']);

            DB::table('jobs')
                ->whereRaw('LOWER(employment_type) != ? OR employment_type IS NULL', ['internship'])
                ->whereNull('type')
                ->update(['type' => 'job']);
        } catch (\Throwable $e) {
            // Safe fallback if table is empty or error occurs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('jobs', 'type')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
