<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('media_folders')) {
            Schema::create('media_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
                $table->string('name');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_files')) {
            Schema::create('media_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('folder_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
                $table->string('name');
                $table->string('original_name');
                $table->string('path'); // Path on disk
                $table->string('disk')->default('public'); // local, s3, gcs
                $table->string('mime_type');
                $table->string('extension');
                $table->unsignedBigInteger('size'); // bytes
                $table->json('metadata')->nullable(); // dimensions, duration, etc.
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_tags')) {
            Schema::create('media_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_file_tag')) {
            Schema::create('media_file_tag', function (Blueprint $table) {
                $table->foreignId('file_id')->constrained('media_files')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('media_tags')->cascadeOnDelete();
                $table->primary(['file_id', 'tag_id']);
            });
        }

        if (!Schema::hasTable('media_categories')) {
            Schema::create('media_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_versions')) {
            Schema::create('media_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->constrained('media_files')->cascadeOnDelete();
                $table->string('path');
                $table->unsignedBigInteger('size');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_share_links')) {
            Schema::create('media_share_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->nullable()->constrained('media_files')->cascadeOnDelete();
                $table->foreignId('folder_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
                $table->string('token')->unique();
                $table->string('password')->nullable();
                $table->integer('download_limit')->nullable();
                $table->integer('downloads')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_logs')) {
            Schema::create('media_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->nullable()->constrained('media_files')->cascadeOnDelete();
                $table->foreignId('folder_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // uploaded, downloaded, moved, deleted
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_favorites')) {
            Schema::create('media_favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->nullable()->constrained('media_files')->cascadeOnDelete();
                $table->foreignId('folder_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['file_id', 'user_id']);
                $table->unique(['folder_id', 'user_id']);
            });
        }

        // Permissions for specific files/folders
        if (!Schema::hasTable('media_permissions')) {
            Schema::create('media_permissions', function (Blueprint $table) {
                $table->id();
                $table->morphs('media'); // file or folder
                $table->morphs('entity'); // user or role
                $table->string('permission_level'); // view, edit, full
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_permissions');
        Schema::dropIfExists('media_favorites');
        Schema::dropIfExists('media_logs');
        Schema::dropIfExists('media_share_links');
        Schema::dropIfExists('media_versions');
        Schema::dropIfExists('media_file_tag');
        Schema::dropIfExists('media_tags');
        Schema::dropIfExists('media_categories');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('media_folders');
    }
};
