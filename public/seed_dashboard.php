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

echo "Starting Database Seeding for Dashboard via Public Script...\n";

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
        $user->email = "student_seed_{$i}@example.com";
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
        $user->email = "expert_seed_{$i}@example.com";
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
        $user->email = "company_seed_{$i}@example.com";
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
        $course->slug = \Illuminate\Support\Str::slug($title . '-' . rand(100, 999));
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
        $job = new Job();
        $job->title = "Software Engineer Role Pro {$i}";
        $job->company_id = User::role('company')->first()->id ?? 1;
        $job->description = 'Great job opportunity.';
        $job->requirements = 'React, Node';
        $job->location = 'Remote';
        $job->salary_range = '₹5L - ₹10L';
        $job->status = 'open';
        $job->save();
    }

    for ($i = 1; $i <= 10; $i++) {
        $intern = new Internship();
        $intern->title = "Frontend Intern Pro {$i}";
        $intern->company_id = User::role('company')->first()->id ?? 1;
        $intern->description = 'Learn frontend development.';
        $intern->requirements = 'HTML, CSS, JS';
        $intern->location = 'Remote';
        $intern->stipend = '₹10k/month';
        $intern->status = 'open';
        $intern->save();
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
    echo "Successfully seeded realistic dashboard data V3!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

?>
