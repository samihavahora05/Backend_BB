<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs "password-reuse validation" (a previously unimplemented feature, not
 * a regression): changePassword() in both AuthController and
 * JobseekerDashboardController, plus AuthController::resetPassword(), only
 * ever checked the new password against the *current* one. There was no
 * record of prior password hashes anywhere, so reuse of an old password
 * could never actually be detected. This table stores the last few hashes
 * per user so PasswordHistoryService can check against them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('password_hash');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
