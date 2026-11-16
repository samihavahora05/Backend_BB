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
        if (Schema::hasTable('internship_applications')) {
            Schema::table('internship_applications', function (Blueprint $table) {
                if (!Schema::hasColumn('internship_applications', 'candidate_signature_base64')) {
                    $table->longText('candidate_signature_base64')->nullable()->after('signature_path');
                }
                if (!Schema::hasColumn('internship_applications', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable()->after('candidate_signature_base64');
                }
                if (!Schema::hasColumn('internship_applications', 'offer_letter_sent_at')) {
                    $table->timestamp('offer_letter_sent_at')->nullable()->after('signed_at');
                }
                if (!Schema::hasColumn('internship_applications', 'offer_letter_path')) {
                    $table->string('offer_letter_path')->nullable()->after('offer_letter_sent_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('internship_applications')) {
            Schema::table('internship_applications', function (Blueprint $table) {
                $cols = ['candidate_signature_base64', 'signed_at', 'offer_letter_sent_at', 'offer_letter_path'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('internship_applications', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
