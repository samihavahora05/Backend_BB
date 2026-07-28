<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\GlobalSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            "Course Information",
            "Internship Inquiry",
            "Job Opportunities",
            "Mentorship",
            "Career Guidance",
            "Book Consultation",
            "Corporate Training",
            "Partnership / Collaboration",
            "Campus Hiring"
        ];

        GlobalSetting::updateOrCreate(
            ['key' => 'crm_lead_categories'],
            [
                'group' => 'general',
                'value' => json_encode($categories)
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        GlobalSetting::where('key', 'crm_lead_categories')->delete();
    }
};
