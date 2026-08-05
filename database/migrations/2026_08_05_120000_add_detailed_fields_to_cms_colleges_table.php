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
        Schema::table('cms_colleges', function (Blueprint $table) {
            $table->string('banner_image')->nullable()->after('logo_url');
            $table->text('short_description')->nullable()->after('location');
            $table->longText('full_description')->nullable()->after('short_description');
            $table->string('website_url')->nullable()->after('full_description');
            
            // Approvals & Rankings
            $table->boolean('is_ugc_approved')->default(false)->after('website_url');
            $table->string('naac_grade')->nullable()->after('is_ugc_approved');
            $table->string('nirf_ranking')->nullable()->after('naac_grade');
            $table->boolean('is_wes_approved')->default(false)->after('nirf_ranking');
            
            // Academics
            $table->json('degree_types')->nullable()->after('is_wes_approved');
            $table->json('popular_courses')->nullable()->after('degree_types');
            $table->string('duration')->nullable()->after('popular_courses');
            $table->text('eligibility')->nullable()->after('duration');
            $table->longText('admission_process')->nullable()->after('eligibility');
            $table->longText('placement_support')->nullable()->after('admission_process');
            $table->text('career_services')->nullable()->after('placement_support');
            $table->text('accreditation')->nullable()->after('career_services');
            
            // SEO
            $table->text('meta_keywords')->nullable()->after('seo_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_colleges', function (Blueprint $table) {
            $table->dropColumn([
                'banner_image',
                'short_description',
                'full_description',
                'website_url',
                'is_ugc_approved',
                'naac_grade',
                'nirf_ranking',
                'is_wes_approved',
                'degree_types',
                'popular_courses',
                'duration',
                'eligibility',
                'admission_process',
                'placement_support',
                'career_services',
                'accreditation',
                'meta_keywords'
            ]);
        });
    }
};
