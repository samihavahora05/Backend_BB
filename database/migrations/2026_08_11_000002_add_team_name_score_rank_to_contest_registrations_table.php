<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PublicContestController::register() saves a `team_name`, and
 * StudentContestController::index() reads `score`/`rank`, but the original
 * contest_registrations migration never created these columns. Registering
 * for a contest would fail with an "Unknown column" SQLSTATE error identical
 * in nature to the contests.category_id issue. Adding the columns the
 * application already relies on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contest_registrations', function (Blueprint $table) {
            $table->string('team_name')->nullable()->after('contest_id');
            $table->unsignedInteger('score')->nullable()->after('status');
            $table->unsignedInteger('rank')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('contest_registrations', function (Blueprint $table) {
            $table->dropColumn(['team_name', 'score', 'rank']);
        });
    }
};
