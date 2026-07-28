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
        Schema::table('email_logs', function (Blueprint $table) {
            // Drop legacy columns from Schema A
            $table->dropForeign(['user_id']); // Drops foreign key constraint if it exists
            $table->dropColumn('user_id');
            $table->dropColumn('recipient_email');
            $table->dropColumn('body_preview');
            $table->dropColumn('sent_at');

            // Add standard columns for Schema B (EmailLog model)
            $table->string('recipient')->after('id');
            $table->string('mailable_class')->after('subject');
            $table->string('status')->default('pending')->after('mailable_class'); // pending, sent, failed
            $table->text('error_message')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn('recipient');
            $table->dropColumn('mailable_class');
            $table->dropColumn('status');
            $table->dropColumn('error_message');

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email');
            $table->text('body_preview')->nullable();
            $table->dateTime('sent_at');
        });
    }
};
