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
        if (Schema::hasTable('expert_reviews')) {
            Schema::table('expert_reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('expert_reviews', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('expert_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('expert_reviews', 'student_id')) {
                    $table->foreignId('student_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('expert_reviews', 'session_title')) {
                    $table->string('session_title')->nullable()->after('review_text');
                }
                if (!Schema::hasColumn('expert_reviews', 'is_approved')) {
                    $table->boolean('is_approved')->default(true)->after('session_title');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expert_reviews')) {
            Schema::table('expert_reviews', function (Blueprint $table) {
                if (Schema::hasColumn('expert_reviews', 'session_title')) {
                    $table->dropColumn('session_title');
                }
                if (Schema::hasColumn('expert_reviews', 'is_approved')) {
                    $table->dropColumn('is_approved');
                }
            });
        }
    }
};
