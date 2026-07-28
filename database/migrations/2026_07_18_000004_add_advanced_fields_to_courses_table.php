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
        Schema::table('courses', function (Blueprint $table) {
            $table->text('short_description')->nullable()->after('title');
            $table->enum('course_type', ['Free', 'Paid'])->default('Paid')->after('price');
            
            // Override the old is_published with a more robust status Enum
            $table->enum('status', ['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])->default('Draft')->after('is_published');
            
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('is_archived')->default(false)->after('is_featured');
            
            $table->string('preview_video_url')->nullable()->after('thumbnail');
            $table->string('demo_pdf_url')->nullable()->after('preview_video_url');
            $table->string('landing_page_url')->nullable()->after('demo_pdf_url');
            $table->string('duration')->nullable()->after('language'); // String like "8 Weeks"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'short_description',
                'course_type',
                'status',
                'is_featured',
                'is_archived',
                'preview_video_url',
                'demo_pdf_url',
                'landing_page_url',
                'duration'
            ]);
        });
    }
};
