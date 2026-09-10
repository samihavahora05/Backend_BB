<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Upgrade users table with approval and account status fields
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status')->default('active')->after('status');
            }
            if (!Schema::hasColumn('users', 'admin_approved')) {
                $table->boolean('admin_approved')->default(false)->after('account_status');
            }
            if (!Schema::hasColumn('users', 'admin_approved_at')) {
                $table->timestamp('admin_approved_at')->nullable()->after('admin_approved');
            }
            if (!Schema::hasColumn('users', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('admin_approved_at');
            }
            if (!Schema::hasColumn('users', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('users', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('users', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });

        // 2. Create dedicated email_verification_otps table
        if (!Schema::hasTable('email_verification_otps')) {
            Schema::create('email_verification_otps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email')->index();
                $table->string('otp_hash');
                $table->timestamp('expires_at');
                $table->integer('attempts')->default(0);
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 3. Ensure existing active users remain verified, approved, and active
        // This guarantees NO existing production users are locked out!
        DB::table('users')
            ->where('status', 'active')
            ->orWhereNull('status')
            ->update([
                'account_status' => 'active',
                'admin_approved' => true,
                'admin_approved_at' => now(),
                'email_verified_at' => DB::raw('COALESCE(email_verified_at, CURRENT_TIMESTAMP)')
            ]);

        // Sync any users with status = pending_approval
        DB::table('users')
            ->where('status', 'pending_approval')
            ->update([
                'account_status' => 'pending_admin_approval',
                'admin_approved' => false
            ]);

        // Sync any users with status = suspended
        DB::table('users')
            ->where('status', 'suspended')
            ->update([
                'account_status' => 'suspended'
            ]);

        // Sync any users with status = rejected
        DB::table('users')
            ->where('status', 'rejected')
            ->update([
                'account_status' => 'rejected'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verification_otps');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_status',
                'admin_approved',
                'admin_approved_at',
                'approved_by',
                'rejection_reason',
                'rejected_at',
                'rejected_by',
            ]);
        });
    }
};