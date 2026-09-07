<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internship_applications')) {
            Schema::table('internship_applications', function (Blueprint $table) {
                if (!Schema::hasColumn('internship_applications', 'terms_accepted')) {
                    $table->boolean('terms_accepted')->default(false)->after('custom_answers');
                }
                if (!Schema::hasColumn('internship_applications', 'terms_accepted_at')) {
                    $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
                }
                if (!Schema::hasColumn('internship_applications', 'terms_version')) {
                    $table->string('terms_version', 50)->nullable()->after('terms_accepted_at');
                }
                if (!Schema::hasColumn('internship_applications', 'signature_path')) {
                    $table->string('signature_path')->nullable()->after('terms_version');
                }
                if (!Schema::hasColumn('internship_applications', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable()->after('signature_path');
                }
                if (!Schema::hasColumn('internship_applications', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('signed_at');
                }
                if (!Schema::hasColumn('internship_applications', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                if (!Schema::hasColumn('internship_applications', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('reviewed_at');
                }
                if (!Schema::hasColumn('internship_applications', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn('internship_applications', 'appointment_letter_path')) {
                    $table->string('appointment_letter_path')->nullable()->after('approved_at');
                }
                if (!Schema::hasColumn('internship_applications', 'appointment_letter_generated_at')) {
                    $table->timestamp('appointment_letter_generated_at')->nullable()->after('appointment_letter_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internship_applications')) {
            Schema::table('internship_applications', function (Blueprint $table) {
                $columns = [
                    'terms_accepted', 'terms_accepted_at', 'terms_version',
                    'signature_path', 'signed_at', 'reviewed_by', 'reviewed_at',
                    'rejection_reason', 'approved_at', 'appointment_letter_path',
                    'appointment_letter_generated_at'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('internship_applications', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};