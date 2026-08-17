<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expert Profiles
        if (!Schema::hasTable('expert_profiles')) {
            Schema::create('expert_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('designation');
                $table->string('company')->nullable();
                $table->text('bio')->nullable();
                $table->decimal('hourly_rate', 10, 2)->default(0);
                $table->string('timezone')->default('Asia/Kolkata');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // NOTE: expert_availabilities is intentionally NOT created here.
        // It previously was, using an `is_available` column, but the app
        // (ExpertAvailability model + PublicExpertController::show()) uses
        // `is_active`. That mismatch, combined with a second migration also
        // trying to create this table, caused migrations to fail partway
        // and left the table (when it existed) without the `is_active`
        // column, producing the Experts profile 500 error. The table is now
        // owned exclusively by 2026_08_11_000003_create_expert_availabilities_table.php,
        // which creates it with the correct schema and self-heals older
        // databases that already have it in the old shape.

        // Expert Bookings
        if (!Schema::hasTable('expert_bookings')) {
            Schema::create('expert_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->date('booking_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->decimal('amount', 10, 2);
                $table->string('order_id')->nullable(); // Razorpay order ID
                $table->string('payment_id')->nullable(); // Razorpay payment ID
                $table->enum('status', ['Pending', 'Confirmed', 'Completed', 'Cancelled'])->default('Pending');
                $table->text('meeting_link')->nullable(); // Zoom / Meet link
                $table->text('student_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_bookings');
        Schema::dropIfExists('expert_availabilities');
        Schema::dropIfExists('expert_profiles');
    }
};
