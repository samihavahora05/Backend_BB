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
        // 1. Google OAuth tokens storage for organizer accounts (info.blueboxx@gmail.com)
        if (!Schema::hasTable('google_oauth_tokens')) {
            Schema::create('google_oauth_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->text('access_token');
                $table->text('refresh_token')->nullable();
                $table->integer('expires_in')->nullable();
                $table->string('token_type')->default('Bearer');
                $table->string('scope')->nullable();
                $table->timestamp('created_at_timestamp')->nullable();
                $table->timestamps();
            });
        }

        // 2. Google Meet Spaces & Interview Meetings table
        if (!Schema::hasTable('google_meet_interviews')) {
            Schema::create('google_meet_interviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attempt_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('google_space_name')->nullable(); // e.g. spaces/XXXXXXXX
                $table->string('meeting_code')->nullable();      // e.g. xxx-yyyy-zzz
                $table->string('meeting_uri', 500);              // e.g. https://meet.google.com/xxx-yyyy-zzz
                $table->string('organizer_email')->default('info.blueboxx@gmail.com');
                $table->string('meeting_status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
                $table->dateTime('scheduled_at')->nullable();
                $table->json('space_details')->nullable();
                $table->timestamps();
            });
        }

        // 3. Ensure assessment_attempts table has corresponding fields
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_attempts', 'meeting_code')) {
                    $table->string('meeting_code')->nullable()->after('meet_link');
                }
                if (!Schema::hasColumn('assessment_attempts', 'google_space_name')) {
                    $table->string('google_space_name')->nullable()->after('meeting_code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_meet_interviews');
        Schema::dropIfExists('google_oauth_tokens');

        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $table) {
                $cols = ['meeting_code', 'google_space_name'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('assessment_attempts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
