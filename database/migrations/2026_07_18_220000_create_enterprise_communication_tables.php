<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('message_recipients');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_threads');

        Schema::enableForeignKeyConstraints();

        // 1. Message Threads
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('private'); // private, announcement, broadcast, chat
            $table->string('status')->default('active'); // active, archived, closed
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->longText('body');
            $table->boolean('is_system_message')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Message Recipients
        Schema::create('message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_important')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['recipient_id', 'message_id']);
        });

        // 4. Message Attachments
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable(); // mime type
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->timestamps();
        });

        // 5. Broadcasts
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->json('target_roles')->nullable(); // e.g. ["student", "instructor"]
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 6. Communication Logs
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // email, push, broadcast, bulk_message
            $table->unsignedInteger('recipient_count')->default(0);
            $table->string('status')->default('success'); // success, failed, partial
            $table->text('details')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('message_recipients');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_threads');

        Schema::enableForeignKeyConstraints();
    }
};
