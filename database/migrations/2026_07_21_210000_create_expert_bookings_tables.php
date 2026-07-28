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

        // Expert Availability Slots (Recurring Weekly)
        if (!Schema::hasTable('expert_availabilities')) {
            Schema::create('expert_availabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
                $table->integer('day_of_week'); // 0 = Sunday, 6 = Saturday
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_available')->default(true);
                $table->timestamps();
            });
        }

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
