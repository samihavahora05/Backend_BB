<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ExpertProfile::sessions() (hasMany MentorSession) and
 * PublicExpertController::show() query mentor_sessions using
 * expert_profile_id / is_active, and MentorBooking stores a title/price
 * snapshot from the session. None of these columns were ever created by a
 * migration (the original mentor_sessions table only has the legacy
 * student_id/expert_id/scheduled_at booking columns), which caused a
 * "no such column" 500 whenever an expert profile was loaded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('mentor_sessions', 'expert_profile_id')) {
                $table->foreignId('expert_profile_id')->nullable()->after('id')
                    ->constrained('expert_profiles')->nullOnDelete();
            }
            if (!Schema::hasColumn('mentor_sessions', 'title')) {
                $table->string('title')->nullable()->after('expert_profile_id');
            }
            if (!Schema::hasColumn('mentor_sessions', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('duration_minutes');
            }
            if (!Schema::hasColumn('mentor_sessions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        // Legacy rows (direct bookings, no template) shouldn't disappear
        // from any `where('is_active', true)` filters.
        if (Schema::hasColumn('mentor_sessions', 'is_active')) {
            \Illuminate\Support\Facades\DB::table('mentor_sessions')
                ->whereNull('is_active')
                ->update(['is_active' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('mentor_sessions', 'expert_profile_id')) {
                $table->dropConstrainedForeignId('expert_profile_id');
            }
            if (Schema::hasColumn('mentor_sessions', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('mentor_sessions', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('mentor_sessions', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
