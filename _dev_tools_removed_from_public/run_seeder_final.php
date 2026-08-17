<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Job;
use App\Models\Internship;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DataAuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Starting Database Seeding for Dashboard via Public Script Final...\n";

try {
    DB::beginTransaction();

    $roles = ['student', 'expert', 'company', 'college'];
    foreach ($roles as $roleName) {
        if (!Role::where('name', $roleName)->exists()) {
            Role::create(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    $students = [];
    for ($i = 1; $i <= 50; $i++) {
        $user = new User();
        $user->first_name = 'Student';
        $user->last_name = (string)$i;
        $user->email = "student_final_{$i}@example.com";
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole('student');
        $students[] = $user;
    }

    $experts = [];
    for ($i = 1; $i <= 10; $i++) {
        $user = new User();
        $user->first_name = 'Expert';
        $user->last_name = (string)$i;
        $user->email = "expert_final_{$i}@example.com";
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole('expert');
        $experts[] = $user;
    }

    for ($i = 1; $i <= 5; $i++) {
        $user = new User();
        $user->first_name = 'Company';
        $user->last_name = (string)$i;
        $user->email = "company_final_{$i}@example.com";
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole('company');
    }

    $courses = [];
    $courseTitles = ['Advanced React & Next.js Pro', 'Fullstack Laravel Mastery', 'UI/UX Design Masterclass 2026', 'Data Science with Python Elite', 'Machine Learning Basics Pro', 'Digital Marketing 101 Pro', 'Node.js Backend Dev Elite', 'Cyber Security Essentials Pro'];
    foreach ($courseTitles as $idx => $title) {
        $course = new Course();
        $course->title = $title;
        $course->slug = \Illuminate\Support\Str::slug($title . '-' . rand(1000, 9999));
        $course->description = 'A comprehensive course on ' . $title;
        $course->price = rand(999, 4999);
        $course->status = 'published';
        $course->expert_id = $experts[array_rand($experts)]->id;
        $course->thumbnail = 'https://via.placeholder.com/600x400?text=' . urlencode($title);
        $course->level_id = 1;
        $course->duration = rand(10, 40) . ' hours';
        $course->category_id = 1;
        $course->save();
        $courses[] = $course;
    }

    for ($i = 1; $i <= 15; $i++) {
        DB::table('jobs')->insert([
            'title' => "Software Engineer Role Pro {$i}",
            'job_id_prefix' => 'JOB-' . rand(1000, 9999),
            'company_id' => User::role('company')->first()->id ?? 1,
            'description' => 'Great job opportunity.',
            'requirements' => json_encode(['React', 'Node']),
            'location' => 'Remote',
            'salary_min' => 500000,
            'salary_max' => 1000000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    for ($i = 1; $i <= 10; $i++) {
        DB::table('internships')->insert([
            'title' => "Frontend Intern Pro {$i}",
            'company_id' => User::role('company')->first()->id ?? 1,
            'description' => 'Learn frontend development.',
            'skills_required' => json_encode(['HTML', 'CSS', 'JS']),
            'location' => 'Remote',
            'stipend' => 10000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    // Seed Job Applications, Interviews, Offers
    $jobs = DB::table('jobs')->get();
    foreach ($jobs as $job) {
        $numApplicants = rand(2, 5);
        for ($a = 0; $a < $numApplicants; $a++) {
            $student = $students[array_rand($students)];
            
            // Check if already applied
            if (DB::table('job_applications')->where('job_id', $job->id)->where('user_id', $student->id)->exists()) {
                continue;
            }
            
            $appId = DB::table('job_applications')->insertGetId([
                'job_id' => $job->id,
                'user_id' => $student->id,
                'status' => 'applied',
                'resume_path' => 'resumes/dummy.pdf',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Randomly progress them
            $rand = rand(1, 100);
            if ($rand > 70) {
                DB::table('job_applications')->where('id', $appId)->update(['status' => 'interview_scheduled']);
                DB::table('job_interviews')->insert([
                    'application_id' => $appId,
                    'interviewer_id' => User::role('company')->first()->id ?? 1,
                    'round_number' => 1,
                    'mode' => 'google_meet',
                    'meeting_link' => 'https://meet.google.com/abc-def-ghi',
                    'scheduled_at' => now()->addDays(rand(1, 5)),
                    'recommendation' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($rand > 50) {
                DB::table('job_applications')->where('id', $appId)->update(['status' => 'shortlisted']);
                DB::table('job_shortlists')->insert([
                    'job_id' => $job->id,
                    'user_id' => $student->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($rand > 90) {
                DB::table('job_applications')->where('id', $appId)->update(['status' => 'offer_sent']);
                DB::table('job_offers')->insert([
                    'application_id' => $appId,
                    'salary_offered' => rand(600000, 1000000),
                    'valid_until' => now()->addDays(7),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    for ($m = 0; $m < 12; $m++) {
        $numOrders = rand(5, 20);
        $date = Carbon::now()->subMonths($m)->startOfMonth()->addDays(rand(1, 28));
        
        for ($i = 0; $i < $numOrders; $i++) {
            $student = $students[array_rand($students)];
            $course = $courses[array_rand($courses)];
            
            $order = new Order();
            $order->user_id = $student->id;
            $order->total_amount = $course->price;
            $order->status = 'completed';
            $order->payment_status = 'completed';
            $order->payment_id = 'PAY_' . \Illuminate\Support\Str::random(10);
            $order->created_at = $date;
            $order->updated_at = $date;
            $order->save();

            $item = new OrderItem();
            $item->order_id = $order->id;
            $item->course_id = $course->id;
            $item->price = $course->price;
            $item->created_at = $date;
            $item->updated_at = $date;
            $item->save();
        }
    }

    $admin = User::role('admin')->first() ?? clone $experts[0];
    if (!$admin->hasRole('admin')) {
        $admin->assignRole('admin');
    }
    
    $actions = ['created', 'updated', 'deleted', 'approved', 'rejected'];
    $tables = ['users', 'courses', 'jobs', 'internships', 'orders'];
    
    for ($i = 0; $i < 20; $i++) {
        $log = new DataAuditLog();
        $log->admin_id = $admin->id;
        $log->table_name = $tables[array_rand($tables)];
        $log->record_id = rand(1, 50);
        $log->action = $actions[array_rand($actions)];
        $log->old_data = json_encode(['status' => 'pending']);
        $log->new_data = json_encode(['status' => 'completed']);
        $log->ip_address = '127.0.0.1';
        $log->created_at = Carbon::now()->subHours(rand(1, 72));
        $log->save();
    }

    DB::commit();
    echo "Successfully seeded realistic dashboard data V4!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

?>
