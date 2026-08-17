<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Root-cause fix for:
 *   SQLSTATE[HY000]: General error: 1 table contests has no column named category_id
 *
 * The Contest model's $fillable and EventsAndActivitiesSeeder both assume
 * `category_id` and `college_id` columns exist on `contests`, but the original
 * create_contests_table migration never added them. This adds the columns the
 * application code already expects instead of stripping them from the seeder.
 *
 * category_id links a contest to a course_categories row (the only "category"
 * table in the app) so contests can be grouped/filtered the same way courses are.
 * college_id links a contest to a college-owner user (Contest::college() already
 * belongsTo(User::class, 'college_id')); null means the contest is open to all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            if (!Schema::hasColumn('contests', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('course_categories')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('contests', 'college_id')) {
                $table->foreignId('college_id')
                    ->nullable()
                    ->after('end_date')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            if (Schema::hasColumn('contests', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('contests', 'college_id')) {
                $table->dropForeign(['college_id']);
                $table->dropColumn('college_id');
            }
        });
    }
};
