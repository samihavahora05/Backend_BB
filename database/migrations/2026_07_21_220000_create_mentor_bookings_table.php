<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mentor_bookings')) {
            Schema::create('mentor_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('mentor_sessions')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('expert_id')->constrained('expert_profiles')->cascadeOnDelete();
                $table->date('booking_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('order_id')->nullable()->comment('Razorpay Order ID');
                $table->enum('status', ['Pending', 'Confirmed', 'Completed', 'Cancelled'])->default('Pending');
                $table->text('meeting_link')->nullable();
                $table->text('student_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_bookings');
    }
};
