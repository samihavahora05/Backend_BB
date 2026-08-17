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
        if (!Schema::hasTable('student_job_offers')) {
            Schema::create('student_job_offers', function (Blueprint $table) {
                $table->id();
                $table->string('student_name');
                $table->string('degree')->nullable();
                $table->string('company_name');
                $table->string('role');
                $table->string('offered_on')->nullable(); // e.g. "10 Mar 2025" or "2025-03-10"
                $table->string('package')->nullable(); // e.g. "12 LPA"
                $table->string('avatar_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed initial records
            DB::table('student_job_offers')->insert([
                [
                    'student_name' => 'Ananya Sharma',
                    'degree'       => 'B.Tech - CSE',
                    'company_name' => 'Infosys',
                    'role'         => 'Software Engineer',
                    'offered_on'   => '10 Mar 2025',
                    'package'      => '8.5 LPA',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'student_name' => 'Rahul Verma',
                    'degree'       => 'MBA - Marketing',
                    'company_name' => 'HDFC Bank',
                    'role'         => 'Business Analyst',
                    'offered_on'   => '25 Feb 2025',
                    'package'      => '10 LPA',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'student_name' => 'Priya Nair',
                    'degree'       => 'B.Sc - Data Science',
                    'company_name' => 'TCS',
                    'role'         => 'Data Analyst',
                    'offered_on'   => '05 Apr 2025',
                    'package'      => '7.2 LPA',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'student_name' => 'Aman Gupta',
                    'degree'       => 'B.Tech - IT',
                    'company_name' => 'Wipro',
                    'role'         => 'Frontend Developer',
                    'offered_on'   => '18 Jan 2025',
                    'package'      => '6.8 LPA',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'student_name' => 'Sneha Patel',
                    'degree'       => 'BCA',
                    'company_name' => 'Accenture',
                    'role'         => 'System Engineer',
                    'offered_on'   => '12 Apr 2025',
                    'package'      => '7.5 LPA',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'student_name' => 'Vikramaditya Rao',
                    'degree'       => 'M.Tech - AI',
                    'company_name' => 'Cognizant',
                    'role'         => 'AI/ML Specialist',
                    'offered_on'   => '02 May 2025',
                    'package'      => '14 LPA',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_job_offers');
    }
};
