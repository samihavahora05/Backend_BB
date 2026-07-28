<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Internship;
use App\Models\Job;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\PlacementPartner;
use App\Models\SuccessStory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a Seed Company User
        $companyUser = User::create([
            'name' => 'Acme Corp Inc',
            'email' => 'hr@acme.com',
            'phone' => '9876543210',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        $companyUser->assignRole('company');
        $companyUser->companyProfile()->create([
            'company_name' => 'Acme Corp Inc',
            'website' => 'https://acmecorp.com',
        ]);

        // 2. Create a Seed Expert User
        $expertUser = User::create([
            'name' => 'Priya Desai',
            'email' => 'priya@expert.com',
            'phone' => '9876543211',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        $expertUser->assignRole('expert');
        $expertUser->expertProfile()->create([
            'first_name' => 'Priya',
            'last_name' => 'Desai',
            'designation' => 'Senior Frontend Developer',
            'company' => 'Microsoft',
            'is_verified' => true,
        ]);

        // 3. Seed Course Categories & Courses
        $cat = CourseCategory::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'Courses related to fullstack and frontend engineering.',
        ]);

        $course1 = Course::create([
            'category_id' => $cat->id,
            'expert_id' => $expertUser->id,
            'title' => 'Advanced React Patterns',
            'slug' => 'advanced-react-patterns',
            'description' => 'Master advanced React design patterns, state machines, and compound components.',
            'price' => 2499.00,
            'level_id' => null,
            'is_published' => true,
        ]);

        $module = Module::create([
            'course_id' => $course1->id,
            'title' => 'Module 4: Context API & State Machines',
            'order' => 1,
        ]);

        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Introduction to State Machines',
            'order' => 1,
        ]);

        // 4. Seed Internships & Jobs under the Company User
        Internship::create([
            'company_id' => $companyUser->id,
            'title' => 'Frontend Developer Intern',
            'description' => 'Looking for a React developer intern to build beautiful user interfaces.',
            'duration_months' => 3,
            'stipend' => 25000.00,
            'status' => 'open',
        ]);

        Internship::create([
            'company_id' => $companyUser->id,
            'title' => 'UI/UX Design Intern',
            'description' => 'Figma UI design intern to support web redesign projects.',
            'duration_months' => 6,
            'stipend' => 20000.00,
            'status' => 'open',
        ]);

        Job::create([
            'company_id' => $companyUser->id,
            'title' => 'Senior Frontend Developer',
            'description' => 'Senior engineer with 5+ years of React experience.',
            'salary_range' => '₹15,00,000 - ₹20,00,000',
            'location' => 'Bangalore',
            'status' => 'open',
        ]);

        // 5. Seed FAQs & Testimonials
        Faq::create([
            'question' => 'How does the placement assurance program work?',
            'answer' => 'We train you on industry-ready projects, verify your skills, and refer you to partner companies.',
            'is_active' => true,
        ]);

        Testimonial::create([
            'name' => 'Rahul Sharma',
            'role' => 'Software Engineer',
            'company' => 'Google',
            'content' => 'The program completely transformed my career path. I got hired in 3 months!',
            'rating' => 5,
            'is_featured' => true,
        ]);

        // 6. Seed Placement Partners & Success Stories
        PlacementPartner::create([
            'name' => 'Microsoft',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/96/Microsoft_logo_%282012%29.svg',
            'website_url' => 'https://microsoft.com',
        ]);

        SuccessStory::create([
            'student_name' => 'Aakash Patel',
            'course_name' => 'Advanced React Patterns',
            'company_name' => 'Razorpay',
            'package' => '14 LPA',
            'story' => 'I successfully landed a job at Razorpay immediately after completing my final project.',
            'is_featured' => true,
        ]);
    }
}
