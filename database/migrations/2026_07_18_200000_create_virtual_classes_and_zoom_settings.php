<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zoom Settings (single-row configuration per platform)
        Schema::create('zoom_settings', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->enum('auto_recording', ['none', 'local', 'cloud'])->default('none');
            $table->enum('audio_options', ['both', 'telephony', 'voip'])->default('both');
            $table->enum('host_video', ['enable', 'disable'])->default('disable');
            $table->enum('participant_video', ['enable', 'disable'])->default('disable');
            $table->enum('join_before_host', ['enable', 'disable'])->default('disable');
            $table->enum('waiting_room', ['enable', 'disable'])->default('enable');
            $table->enum('mute_upon_entry', ['enable', 'disable'])->default('enable');
            $table->enum('class_join_approval', ['automatically', 'manually', 'no-registration'])->default('automatically');
            $table->timestamps();
        });

        // Virtual Classes
        Schema::create('virtual_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('course_categories')->nullOnDelete();
            $table->string('language')->default('English');
            $table->integer('duration_minutes')->default(60);
            $table->integer('max_students')->default(100);
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('platform', ['zoom', 'google_meet', 'microsoft_teams', 'custom'])->default('zoom');
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('join_url')->nullable();
            $table->string('start_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->boolean('is_recorded')->default(false);
            $table->integer('enrolled_count')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_free')->default(true);
            $table->string('thumbnail')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Virtual Class Enrollments
        Schema::create('virtual_class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_class_id')->constrained('virtual_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['enrolled', 'attended', 'absent', 'cancelled'])->default('enrolled');
            $table->timestamp('joined_at')->nullable();
            $table->integer('duration_attended_minutes')->default(0);
            $table->timestamps();
            $table->unique(['virtual_class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_class_enrollments');
        Schema::dropIfExists('virtual_classes');
        Schema::dropIfExists('zoom_settings');
    }
};
