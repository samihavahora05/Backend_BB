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
        Schema::table('role_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('role_requests', 'current_role')) {
                $table->string('current_role', 50)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('role_requests', 'requested_role')) {
                $table->string('requested_role', 50)->nullable()->after('current_role');
            }
            if (!Schema::hasColumn('role_requests', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('role_requests', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('role_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reviewed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_requests', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'current_role',
                'requested_role',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason'
            ]);
        });
    }
};