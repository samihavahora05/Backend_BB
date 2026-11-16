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
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_attempts', 'is_qualified')) {
                    $table->boolean('is_qualified')->default(false)->after('score');
                }
                if (!Schema::hasColumn('assessment_attempts', 'interview_scheduled_at')) {
                    $table->dateTime('interview_scheduled_at')->nullable()->after('is_qualified');
                }
                if (!Schema::hasColumn('assessment_attempts', 'meet_link')) {
                    $table->string('meet_link')->nullable()->after('interview_scheduled_at');
                }
                if (!Schema::hasColumn('assessment_attempts', 'interview_status')) {
                    $table->enum('interview_status', ['pending', 'scheduled', 'completed', 'cancelled'])->default('pending')->after('meet_link');
                }
                if (!Schema::hasColumn('assessment_attempts', 'whatsapp_sent')) {
                    $table->boolean('whatsapp_sent')->default(false)->after('interview_status');
                }
                if (!Schema::hasColumn('assessment_attempts', 'email_sent')) {
                    $table->boolean('email_sent')->default(false)->after('whatsapp_sent');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $table) {
                $columns = ['is_qualified', 'interview_scheduled_at', 'meet_link', 'interview_status', 'whatsapp_sent', 'email_sent'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('assessment_attempts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
