<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * The ExpertAvailability model (app/Models/ExpertAvailability.php) and
 * ExpertProfile::availabilities() have existed and been actively queried
 * (PublicExpertController::show() eager-loads `availabilities` on every
 * profile view), but this table was never created by any migration.
 * That meant every expert profile page load threw an uncaught
 * "SQLSTATE: no such table: expert_availabilities" 500 error.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expert_availabilities')) {
            Schema::create('expert_availabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expert_profile_id')->constrained('expert_profiles')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday ... 6 = Saturday
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            return;
        }

        // Table already exists (created by the older, now-removed migration
        // block that used `is_available` instead of `is_active`). Repair it
        // in place without dropping any existing rows.
        Schema::table('expert_availabilities', function (Blueprint $table) {
            if (!Schema::hasColumn('expert_availabilities', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('end_time');
            }
        });

        if (Schema::hasColumn('expert_availabilities', 'is_available')) {
            // Backfill is_active from the old column so existing rows keep
            // their availability state, then leave the old column in place
            // (dropping columns is unsafe on SQLite without doctrine/dbal
            // and isn't required for correctness).
            DB::table('expert_availabilities')->update([
                'is_active' => DB::raw('is_available'),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally a no-op: this migration may be repairing a table
        // created by an earlier migration, so it does not own dropping it.
    }
};
