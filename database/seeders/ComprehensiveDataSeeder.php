<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\ExpertProfile;
use App\Models\CompanyProfile;
use App\Models\CollegeProfile;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Models\VirtualClass;
use App\Models\VirtualClassEnrollment;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobBookmark;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\SavedInternship;
use App\Models\CourseEnrollment;
use App\Models\LessonProgress;
use App\Models\IssuedCertificate;
use App\Models\Contest;
use App\Models\ScholarshipProgram;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\PlacementPartner;
use App\Models\SuccessStory;
use App\Models\Lead;
use Spatie\Permission\Models\Role;

class ComprehensiveDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Roles
        $roles = ['super_admin', 'admin', 'student', 'expert', 'company', 'college', 'intern', 'job-seeker'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // 2. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@blueboxx.in'],
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'password'   => Hash::make('password'),
                'status'     => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // 3. Demo Companies
        $companiesData = [
            [
                'name' => 'TechCorp Global',
                'email' => 'hr@techcorp.com',
                'company_name' => 'TechCorp Global Ltd.',
                'industry' => 'Information Technology',
                'website' => 'https://techcorp.example.com',
                'city' => 'Bangalore',
            ],
            [
                'name' => 'CloudScale Innovations',
                'email' => 'recruitment@cloudscale.io',
                'company_name' => 'CloudScale Innovations',
                'industry' => 'Cloud & AI Solutions',
                'website' => 'https://cloudscale.example.com',
                'city' => 'Hyderabad',
            ],
            [
                'name' => 'CyberGuard Security',
                'email' => 'careers@cyberguard.com',
                'company_name' => 'CyberGuard Solutions',
                'industry' => 'Cyber Security',
                'website' => 'https://cyberguard.example.com',
                'city' => 'Pune',
            ],
            [
                'name' => 'DataMind Systems',
                'email' => 'jobs@datamind.ai',
                'company_name' => 'DataMind AI Labs',
                'industry' => 'Data Analytics & AI',
                'website' => 'https://datamind.example.com',
                'city' => 'Gurgaon',
            ],
        ];

        $companyUsers = [];
        foreach ($companiesData as $c) {
            $user = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'first_name' => $c['name'],
                    'last_name'  => '',
                    'password'   => Hash::make('password'),
                    'status'     => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('company');
            CompanyProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $c['company_name'],
                    'industry'     => $c['industry'],
                    'website'      => $c['website'],
                ]
            );
            $companyUsers[] = $user;
        }

        // 4. Demo Experts / Instructors
        $expertsData = [
            [
                'first_name' => 'Rajesh',
                'last_name'  => 'Sharma',
                'email'      => 'rajesh.sharma@blueboxx.in',
                'title'      => 'Senior System Architect & AI Researcher',
                'designation'=> 'Principal Software Engineer',
                'company'    => 'Google India',
                'specialization' => 'Backend Systems & Machine Learning',
                'hourly_rate'=> 150.00,
                'exp'        => 12,
            ],
            [
                'first_name' => 'Priya',
                'last_name'  => 'Desai',
                'email'      => 'priya.desai@blueboxx.in',
                'title'      => 'Full Stack Development Lead',
                'designation'=> 'Engineering Lead',
                'company'    => 'Microsoft',
                'specialization' => 'React, Next.js & Node.js Architecture',
                'hourly_rate'=> 120.00,
                'exp'        => 9,
            ],
        ];

        $expertUsers = [];
        foreach ($expertsData as $e) {
            $user = User::firstOrCreate(
                ['email' => $e['email']],
                [
                    'first_name' => $e['first_name'],
                    'last_name'  => $e['last_name'],
                    'password'   => Hash::make('password'),
                    'status'     => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('expert');

            $expertProfile = ExpertProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'designation'           => $e['title'] . ' | ' . $e['designation'],
                    'company'               => $e['company'],
                    'specialization'        => $e['specialization'],
                    'hourly_rate'           => $e['hourly_rate'],
                    'experience_years'      => $e['exp'],
                    'bio'                   => "Expert instructor with over {$e['exp']} years of industry experience leading high-impact engineering projects.",
                    'highest_qualification' => 'Master of Science (Computer Science)',
                    'is_verified'           => true,
                    'is_available'          => true,
                    'approval_status'       => 'approved',
                    'average_rating'        => 4.9,
                    'total_reviews'         => 120,
                    'total_students'        => 1500,
                ]
            );
            $expertUsers[] = $user;
        }

        // 5. Demo Student
        $studentUser = User::firstOrCreate(
            ['email' => 'student.intern@blueboxx.com'],
            [
                'first_name' => 'Aarav',
                'last_name'  => 'Patel',
                'phone'      => '9876543210',
                'password'   => Hash::make('password'),
                'status'     => 'active',
                'email_verified_at' => now(),
            ]
        );
        $studentUser->assignRole('student');
        StudentProfile::firstOrCreate(
            ['user_id' => $studentUser->id],
            [
                'bio'       => 'Final year Computer Science student eager to work on full-stack web development and cloud technologies.',
                'city'      => 'Bangalore',
                'state'     => 'Karnataka',
                'country'   => 'India',
                'student_type' => 'College Student',
            ]
        );

        // Additional Students
        for ($i = 1; $i <= 5; $i++) {
            $stUser = User::firstOrCreate(
                ['email' => "student{$i}@blueboxx.com"],
                [
                    'first_name' => "Student{$i}",
                    'last_name'  => "Learner",
                    'password'   => Hash::make('password'),
                    'status'     => 'active',
                ]
            );
            $stUser->assignRole('student');
            StudentProfile::firstOrCreate(['user_id' => $stUser->id], ['city' => 'Mumbai', 'student_type' => 'Student']);
        }

        // 6. Course Categories & Levels
        $catWeb = CourseCategory::firstOrCreate(['slug' => 'web-development'], ['name' => 'Web Development', 'description' => 'Fullstack & frontend courses']);
        $catData = CourseCategory::firstOrCreate(['slug' => 'data-science'], ['name' => 'Data Science & AI', 'description' => 'Machine learning & analytics']);
        $catCloud = CourseCategory::firstOrCreate(['slug' => 'cloud-devops'], ['name' => 'Cloud & DevOps', 'description' => 'AWS, Docker & Kubernetes']);

        $lvlBeginner = CourseLevel::firstOrCreate(['slug' => 'beginner'], ['title' => 'Beginner']);
        $lvlIntermediate = CourseLevel::firstOrCreate(['slug' => 'intermediate'], ['title' => 'Intermediate']);
        $lvlAdvanced = CourseLevel::firstOrCreate(['slug' => 'advanced'], ['title' => 'Advanced']);

        // 7. Courses with Modules, Lessons & Quizzes
        $coursesData = [
            [
                'title'       => 'Mastering Modern Full-Stack Laravel & Next.js',
                'category_id' => $catWeb->id,
                'level_id'    => $lvlIntermediate->id,
                'expert_id'   => $expertUsers[1]->id, // Priya
                'price'       => 4999.00,
                'discount'    => 2999.00,
                'type'        => 'Paid',
                'duration'    => '40 Hours',
                'short'       => 'Build enterprise full-stack web applications with Laravel REST APIs and Next.js frontend.',
                'desc'        => 'Comprehensive step-by-step masterclass covering API design, authentication, state management, deployment, and best practices.',
            ],
            [
                'title'       => 'Applied Python for Artificial Intelligence & Machine Learning',
                'category_id' => $catData->id,
                'level_id'    => $lvlBeginner->id,
                'expert_id'   => $expertUsers[0]->id, // Rajesh
                'price'       => 5999.00,
                'discount'    => 3499.00,
                'type'        => 'Paid',
                'duration'    => '50 Hours',
                'short'       => 'Learn Python, NumPy, Pandas, Scikit-Learn, and Neural Networks from scratch.',
                'desc'        => 'Hands-on practical training on real-world datasets, computer vision models, and natural language processing pipelines.',
            ],
            [
                'title'       => 'Docker, Kubernetes & AWS DevOps Bootcamp',
                'category_id' => $catCloud->id,
                'level_id'    => $lvlAdvanced->id,
                'expert_id'   => $expertUsers[0]->id, // Assigned to Rajesh
                'price'       => 6999.00,
                'discount'    => 3999.00,
                'type'        => 'Paid',
                'duration'    => '35 Hours',
                'short'       => 'Deploy, scale, and manage containerized cloud applications on AWS EKS and EC2.',
                'desc'        => 'Production DevOps guide covering CI/CD pipelines with GitHub Actions, Infrastructure as Code with Terraform, and Kubernetes cluster management.',
            ],
            [
                'title'       => 'Frontend Fundamentals: HTML5, CSS3 & JavaScript Essentials',
                'category_id' => $catWeb->id,
                'level_id'    => $lvlBeginner->id,
                'expert_id'   => $expertUsers[1]->id,
                'price'       => 0.00,
                'discount'    => 0.00,
                'type'        => 'Free',
                'duration'    => '15 Hours',
                'short'       => 'Free introductory course to learn web foundations and DOM manipulation.',
                'desc'        => 'Kickstart your coding journey with interactive lessons and practical exercises building real websites.',
            ],
        ];

        foreach ($coursesData as $cData) {
            $slug = Str::slug($cData['title']);
            $course = Course::withTrashed()->where('slug', $slug)->first();
            $courseAttributes = [
                'slug'              => $slug,
                'category_id'       => $cData['category_id'],
                'level_id'          => $cData['level_id'],
                'expert_id'         => $cData['expert_id'],
                'title'             => $cData['title'],
                'short_description' => $cData['short'],
                'description'       => $cData['desc'],
                'price'             => $cData['price'],
                'discount_price'    => $cData['discount'],
                'course_type'       => $cData['type'],
                'duration'          => $cData['duration'],
                'status'            => 'Published',
                'is_published'      => true,
                'is_featured'       => true,
                'is_archived'       => false,
                'language'          => 'English',
            ];

            if ($course) {
                if ($course->trashed()) {
                    $course->restore();
                }
                $course->update($courseAttributes);
            } else {
                $course = Course::create($courseAttributes);
            }

            // Create Modules & Lessons
            for ($m = 1; $m <= 3; $m++) {
                $module = Module::updateOrCreate(
                    ['course_id' => $course->id, 'order' => $m],
                    ['title' => "Module {$m}: Core Concepts Part {$m}", 'order' => $m]
                );

                for ($l = 1; $l <= 3; $l++) {
                    $lesson = Lesson::updateOrCreate(
                        ['module_id' => $module->id, 'order' => $l],
                        [
                            'title'            => "Lesson {$l}: Building Feature {$l}",
                            'duration_minutes' => 20,
                            'video_url'        => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                            'order'            => $l,
                            'is_free'          => $l === 1,
                        ]
                    );

                    // Attach Quiz to Lesson 1 of Module 1
                    if ($m === 1 && $l === 1 && !$lesson->quiz) {
                        $quiz = Quiz::create([
                            'lesson_id'     => $lesson->id,
                            'title'         => "Module 1 Knowledge Check Quiz",
                            'passing_score' => 70,
                            'questions'     => '[]',
                        ]);

                        $q1 = QuizQuestion::create([
                            'quiz_id'  => $quiz->id,
                            'question' => 'What is the primary function of HTTP GET request?',
                            'type'     => 'single',
                        ]);

                        QuizAnswer::create(['question_id' => $q1->id, 'answer_text' => 'To retrieve data from server', 'is_correct' => true]);
                        QuizAnswer::create(['question_id' => $q1->id, 'answer_text' => 'To submit form data to server', 'is_correct' => false]);
                        QuizAnswer::create(['question_id' => $q1->id, 'answer_text' => 'To delete server file', 'is_correct' => false]);
                    }
                }
            }

            // Enroll Student in Course 1
            if ($cData === $coursesData[0]) {
                CourseEnrollment::firstOrCreate(
                    ['user_id' => $studentUser->id, 'course_id' => $course->id],
                    ['status' => 'active', 'enrolled_at' => now()]
                );
            }
        }

        // 8. Virtual Classes & MCQs
        $vClass = VirtualClass::updateOrCreate(
            ['title' => 'Live Masterclass: Scalable Microservices Architecture'],
            [
                'description'      => 'Interactive live interactive workshop on designing resilient backend microservices.',
                'instructor_id'    => $expertUsers[0]->id,
                'category_id'      => $catWeb->id,
                'language'         => 'English',
                'duration_minutes' => 90,
                'max_students'     => 100,
                'start_datetime'   => now()->addDays(3)->setTime(18, 0),
                'end_datetime'     => now()->addDays(3)->setTime(19, 30),
                'status'           => 'scheduled',
                'platform'         => 'zoom',
                'join_url'         => 'https://zoom.us/j/123456789',
                'is_free'          => true,
                'price'            => 0.00,
                'enrolled_count'   => 15,
                'created_by'       => $superAdmin->id,
            ]
        );

        // Attach MCQ Quiz to Virtual Class
        if (!$vClass->quiz) {
            $vcQuiz = Quiz::create([
                'virtual_class_id' => $vClass->id,
                'title'            => 'Microservices Quiz Assessment',
                'passing_score'    => 70,
                'questions'        => '[]',
            ]);

            $vcQ1 = QuizQuestion::create([
                'quiz_id'  => $vcQuiz->id,
                'question' => 'Which design pattern is commonly used for distributed transaction management in microservices?',
                'type'     => 'single',
            ]);

            QuizAnswer::create(['question_id' => $vcQ1->id, 'answer_text' => 'Saga Pattern', 'is_correct' => true]);
            QuizAnswer::create(['question_id' => $vcQ1->id, 'answer_text' => 'Singleton Pattern', 'is_correct' => false]);
            QuizAnswer::create(['question_id' => $vcQ1->id, 'answer_text' => 'Observer Pattern', 'is_correct' => false]);
        }

        VirtualClassEnrollment::firstOrCreate(
            ['virtual_class_id' => $vClass->id, 'user_id' => $studentUser->id],
            ['status' => 'enrolled']
        );

        // 9. ATS Internships
        $internships = [
            [
                'company_id'       => $companyUsers[0]->id,
                'title'            => 'Full Stack Engineering Intern',
                'department'       => 'Software Engineering',
                'location'         => 'Bangalore, India',
                'mode'             => 'Hybrid',
                'duration_months'  => 6,
                'duration'         => '6 Months',
                'stipend'          => 20000.00,
                'skills_required'  => ['Laravel', 'React', 'MySQL', 'REST API'],
                'eligibility'      => 'Students in final or pre-final year of B.Tech/B.E./MCA',
                'description'      => 'Work on core web applications with our senior engineering team. Gain hands-on exposure to cloud architecture.',
                'responsibilities' => 'Develop new features, write test cases, optimize database queries.',
                'learning_outcomes'=> 'Build production-ready code with automated CI/CD pipelines.',
                'openings'         => 5,
                'status'           => 'open',
                'start_date'       => now()->addDays(15),
                'application_deadline' => now()->addDays(30),
            ],
            [
                'company_id'       => $companyUsers[1]->id,
                'title'            => 'AI & Data Science Intern',
                'department'       => 'Data Analytics',
                'location'         => 'Hyderabad, India',
                'mode'             => 'Remote',
                'duration_months'  => 3,
                'duration'         => '3 Months',
                'stipend'          => 25000.00,
                'skills_required'  => ['Python', 'Pandas', 'TensorFlow', 'SQL'],
                'eligibility'      => 'Pursuing Computer Science, Data Science or related fields',
                'description'      => 'Assist in building predictive machine learning models and data preprocessing pipelines.',
                'responsibilities' => 'Clean dataset, train NLP/CV models, generate analytics reports.',
                'learning_outcomes'=> 'End-to-end ML model deployment experience.',
                'openings'         => 3,
                'status'           => 'open',
                'start_date'       => now()->addDays(10),
                'application_deadline' => now()->addDays(20),
            ],
            [
                'company_id'       => $companyUsers[2]->id,
                'title'            => 'Cyber Security Analyst Intern',
                'department'       => 'Information Security',
                'location'         => 'Pune, India',
                'mode'             => 'Onsite',
                'duration_months'  => 6,
                'duration'         => '6 Months',
                'stipend'          => 18000.00,
                'skills_required'  => ['Ethical Hacking', 'Linux', 'Network Security', 'OWASP'],
                'eligibility'      => 'Interested in Information Security and Vulnerability Assessment',
                'description'      => 'Perform security audits and vulnerability scanning on web applications.',
                'responsibilities' => 'Identify security risks, document penetration test findings.',
                'learning_outcomes'=> 'Practical experience with enterprise security frameworks.',
                'openings'         => 2,
                'status'           => 'open',
                'start_date'       => now()->addDays(20),
                'application_deadline' => now()->addDays(40),
            ],
        ];

        foreach ($internships as $intData) {
            $internship = Internship::updateOrCreate(
                ['title' => $intData['title'], 'company_id' => $intData['company_id']],
                $intData
            );

            // Apply Student to Internships
            if ($intData === $internships[0]) {
                InternshipApplication::firstOrCreate(
                    ['internship_id' => $internship->id, 'user_id' => $studentUser->id],
                    [
                        'status'       => 'applied',
                        'cover_letter' => 'I am excited to apply for this internship opportunity!',
                        'applied_at'   => now(),
                    ]
                );

                SavedInternship::firstOrCreate(
                    ['user_id' => $studentUser->id, 'internship_id' => $internship->id]
                );
            }
        }

        // 10. ATS Jobs
        $jobs = [
            [
                'company_id'       => $companyUsers[0]->id,
                'title'            => 'Senior Full Stack Software Engineer',
                'department'       => 'Engineering',
                'employment_type'  => 'Full-Time',
                'experience_level' => 'Senior',
                'location'         => 'Bangalore, India',
                'salary_min'       => 1200000.00,
                'salary_max'       => 1800000.00,
                'description'      => 'We are looking for a Senior Developer proficient in Laravel and React to build high-performance cloud applications.',
                'responsibilities' => ['Design database schemas', 'Build REST APIs', 'Mentor junior engineers'],
                'requirements'     => ['4+ years Laravel & React experience', 'Solid knowledge of SQL & Redis'],
                'benefits'         => ['Health Insurance', 'Remote Work Options', 'Performance Bonus'],
                'required_skills'  => ['Laravel', 'React', 'TypeScript', 'MySQL'],
                'application_deadline' => now()->addDays(45),
                'status'           => 'active',
                'is_featured'      => true,
            ],
            [
                'company_id'       => $companyUsers[1]->id,
                'title'            => 'DevOps & Cloud Systems Engineer',
                'department'       => 'Infrastructure',
                'employment_type'  => 'Full-Time',
                'experience_level' => 'Mid-Level',
                'location'         => 'Hyderabad, India',
                'salary_min'       => 1000000.00,
                'salary_max'       => 1500000.00,
                'description'      => 'Join our cloud platform team managing Kubernetes clusters and CI/CD pipelines across AWS.',
                'responsibilities' => ['Automate deployments', 'Monitor system metrics', 'Manage cloud resources'],
                'requirements'     => ['2+ years Docker & Kubernetes', 'AWS Certified Associate'],
                'benefits'         => ['Flexible Hours', 'Learning Allowance'],
                'required_skills'  => ['AWS', 'Docker', 'Kubernetes', 'Terraform'],
                'application_deadline' => now()->addDays(35),
                'status'           => 'active',
                'is_featured'      => true,
            ],
        ];

        foreach ($jobs as $idx => $jData) {
            $jData['job_id_prefix'] = 'JOB-2026-00' . ($idx + 1);
            $job = Job::updateOrCreate(
                ['title' => $jData['title'], 'company_id' => $jData['company_id']],
                $jData
            );

            if ($jData === $jobs[0]) {
                JobApplication::firstOrCreate(
                    ['job_id' => $job->id, 'user_id' => $studentUser->id],
                    ['status' => 'applied', 'cover_letter' => 'Eager to bring my software engineering skills to TechCorp.']
                );

                JobBookmark::firstOrCreate(
                    ['job_id' => $job->id, 'user_id' => $studentUser->id]
                );
            }
        }

        // 11. Contests & Scholarships
        ScholarshipProgram::firstOrCreate(
            ['title' => 'Blueboxx Tech Merit Scholarship 2024'],
            [
                'description' => 'Full scholarship award for meritorious computer science students.',
                'amount'      => 50000.00,
                'deadline'    => now()->addMonths(2)->format('Y-m-d'),
                'status'      => 'open',
            ]
        );

        Contest::firstOrCreate(
            ['title' => 'National Coding & Hackathon Challenge 2024'],
            [
                'description' => 'Flagship online coding contest solving algorithmic challenges.',
                'category_id' => $catWeb->id,
                'start_date'  => now()->addDays(5),
                'end_date'    => now()->addDays(7),
                'status'      => 'upcoming',
            ]
        );

        // 12. Public Content (FAQs, Testimonials, Placement Partners, Blogs)
        Faq::firstOrCreate(
            ['question' => 'How do I enroll in Blueboxx courses?'],
            [
                'answer'    => 'Simply register a student account, browse the courses catalog, and click Enroll on your preferred course.',
                'is_active' => true,
            ]
        );

        Faq::firstOrCreate(
            ['question' => 'Are certificates issued upon course completion?'],
            [
                'answer'    => 'Yes, verifiable digital certificates are automatically issued once you complete 100% of the lessons and pass the final quiz.',
                'is_active' => true,
            ]
        );

        Testimonial::firstOrCreate(
            ['name' => 'Ananya Sharma'],
            [
                'role'    => 'Software Engineer @ Amazon',
                'content' => 'The Blueboxx Laravel & React courses gave me real project experience that helped me crack my technical interviews!',
                'rating'  => 5,
            ]
        );

        PlacementPartner::firstOrCreate(
            ['name' => 'Microsoft India'],
            ['logo_url' => null, 'is_active' => true]
        );
        PlacementPartner::firstOrCreate(
            ['name' => 'Google Cloud'],
            ['logo_url' => null, 'is_active' => true]
        );

        Blog::firstOrCreate(
            ['slug' => 'how-to-land-your-first-software-engineering-internship'],
            [
                'title'      => 'How to Land Your First Software Engineering Internship in 2024',
                'content'    => 'Step-by-step guide to building a strong portfolio, mastering DSA basics, and preparing for technical interviews.',
                'author_id'  => $superAdmin->id,
                'status'     => 'published',
            ]
        );

        $this->command->info('🎉 Comprehensive Enterprise Dummy Data Seeded Successfully!');
    }
}
