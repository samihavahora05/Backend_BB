<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Job;
use App\Models\Internship;
use App\Models\RoleRequest;
use App\Services\OpportunityPermissionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

echo "\n=======================================================\n";
echo "   SARVAKSHETRA OPPORTUNITY PERMISSION & ROLE TEST\n";
echo "=======================================================\n\n";

$passed = 0;
$failed = 0;

function assertCondition($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failed++;
    }
}

// 1. Setup Roles
$roles = ['student', 'intern', 'job-seeker', 'expert', 'college', 'company', 'admin', 'super_admin'];
foreach ($roles as $r) {
    Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
}

// 2. Setup Test Users
$studentUser = User::firstOrCreate(
    ['email' => 'student_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'Student',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$studentUser->syncRoles(['student']);

$internUser = User::firstOrCreate(
    ['email' => 'intern_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'Intern',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$internUser->syncRoles(['intern']);

$jobseekerUser = User::firstOrCreate(
    ['email' => 'jobseeker_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'Jobseeker',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$jobseekerUser->syncRoles(['job-seeker']);

$expertUser = User::firstOrCreate(
    ['email' => 'expert_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'Expert',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$expertUser->syncRoles(['expert']);

$collegeUser = User::firstOrCreate(
    ['email' => 'college_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'College',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$collegeUser->syncRoles(['college']);

$companyUser = User::firstOrCreate(
    ['email' => 'company_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'Company',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$companyUser->syncRoles(['company']);

$adminUser = User::firstOrCreate(
    ['email' => 'admin_test_perm@sarvakshetra.com'],
    [
        'first_name' => 'Admin',
        'last_name' => 'Tester',
        'password' => Hash::make('Secret123!'),
        'status' => 'active',
        'account_status' => 'active',
        'admin_approved' => true,
        'email_verified_at' => now(),
    ]
);
$adminUser->syncRoles(['super_admin']);

echo "--- Group 1: Student Permissions ---\n";
assertCondition("Student cannot apply to Jobs", !OpportunityPermissionService::canApplyToJob($studentUser));
assertCondition("Student cannot apply to Internships", !OpportunityPermissionService::canApplyToInternship($studentUser));
assertCondition("Student CAN apply to Courses", OpportunityPermissionService::canApplyToCourse($studentUser));
assertCondition("Student can request role change to Intern", OpportunityPermissionService::canRequestRoleChange($studentUser, 'intern'));
assertCondition("Student can request role change to Jobseeker", OpportunityPermissionService::canRequestRoleChange($studentUser, 'jobseeker'));
assertCondition("Student CANNOT request role change to Admin", !OpportunityPermissionService::canRequestRoleChange($studentUser, 'admin'));
assertCondition("Student CANNOT request role change to Company", !OpportunityPermissionService::canRequestRoleChange($studentUser, 'company'));

echo "\n--- Group 2: Intern Permissions ---\n";
assertCondition("Intern cannot apply to Jobs", !OpportunityPermissionService::canApplyToJob($internUser));
assertCondition("Intern CAN apply to Internships", OpportunityPermissionService::canApplyToInternship($internUser));
assertCondition("Intern CAN apply to Courses", OpportunityPermissionService::canApplyToCourse($internUser));
assertCondition("Intern can request role change to Jobseeker", OpportunityPermissionService::canRequestRoleChange($internUser, 'jobseeker'));

echo "\n--- Group 3: Jobseeker Permissions ---\n";
assertCondition("Jobseeker CAN apply to Jobs", OpportunityPermissionService::canApplyToJob($jobseekerUser));
assertCondition("Jobseeker cannot apply to Internships", !OpportunityPermissionService::canApplyToInternship($jobseekerUser));
assertCondition("Jobseeker CAN apply to Courses", OpportunityPermissionService::canApplyToCourse($jobseekerUser));
assertCondition("Jobseeker can request role change to Intern", OpportunityPermissionService::canRequestRoleChange($jobseekerUser, 'intern'));

echo "\n--- Group 4: Expert, College, and Company Preservation ---\n";
assertCondition("Expert cannot apply to Jobs", !OpportunityPermissionService::canApplyToJob($expertUser));
assertCondition("Expert cannot apply to Internships", !OpportunityPermissionService::canApplyToInternship($expertUser));
assertCondition("Expert CAN apply to Courses", OpportunityPermissionService::canApplyToCourse($expertUser));
assertCondition("College CAN apply to Courses", OpportunityPermissionService::canApplyToCourse($collegeUser));
assertCondition("Company CAN apply to Courses", OpportunityPermissionService::canApplyToCourse($companyUser));
assertCondition("Admin CAN apply to all", OpportunityPermissionService::canApplyToJob($adminUser) && OpportunityPermissionService::canApplyToInternship($adminUser) && OpportunityPermissionService::canApplyToCourse($adminUser));

echo "\n--- Group 5: Role Change Request & Admin Approval Flow ---\n";
// Clean previous requests for test student
RoleRequest::where('user_id', $studentUser->id)->delete();

// Step A: Student submits request to become Intern
$roleChangeCtrl = new \App\Http\Controllers\Api\RoleChangeRequestController();
$req = new \Illuminate\Http\Request([
    'requested_role' => 'intern',
    'reason' => 'Completed preparatory courses, ready for internship.'
]);
$req->setUserResolver(fn() => $studentUser);

$res = $roleChangeCtrl->store($req);
assertCondition("Student role change request submitted (HTTP 201)", $res->getStatusCode() === 201);

$dbRequest = RoleRequest::where('user_id', $studentUser->id)->where('status', 'pending')->first();
assertCondition("Pending request saved in DB with correct target role", $dbRequest && $dbRequest->requested_role === 'intern');

// Step B: Duplicate pending request blocked
$resDup = $roleChangeCtrl->store($req);
assertCondition("Duplicate pending request blocked (HTTP 409)", $resDup->getStatusCode() === 409);

// Step C: Admin Approves the request
$adminRoleCtrl = new \App\Http\Controllers\Api\Admin\AdminRoleRequestController();
$adminReq = new \Illuminate\Http\Request();
$adminReq->setUserResolver(fn() => $adminUser);

$approveRes = $adminRoleCtrl->approve($adminReq, $dbRequest->id);
assertCondition("Admin approves role request (HTTP 200)", $approveRes->getStatusCode() === 200);

$studentUser->refresh();
assertCondition("User role is updated to intern", $studentUser->hasRole('intern'));
assertCondition("User can now apply to Internships", OpportunityPermissionService::canApplyToInternship($studentUser));

// Step D: Rejection flow
$internUserRequest = RoleRequest::create([
    'user_id' => $internUser->id,
    'current_role' => 'intern',
    'requested_role' => 'job-seeker',
    'requested_role_id' => Role::where('name', 'job-seeker')->first()->id,
    'status' => 'pending',
    'reason' => 'Looking for full-time job.'
]);

$rejectReq = new \Illuminate\Http\Request([
    'rejection_reason' => 'Please complete your ongoing internship duration first.'
]);
$rejectReq->setUserResolver(fn() => $adminUser);

$rejectRes = $adminRoleCtrl->reject($rejectReq, $internUserRequest->id);
assertCondition("Admin rejects role request with mandatory reason (HTTP 200)", $rejectRes->getStatusCode() === 200);

$internUserRequest->refresh();
assertCondition("Request status is updated to rejected with reason captured", $internUserRequest->status === 'rejected' && !empty($internUserRequest->rejection_reason));
$internUser->refresh();
assertCondition("User role remains intern after rejection", $internUser->hasRole('intern'));

// Reset test student role back to student
$studentUser->syncRoles(['student']);

echo "\n=======================================================\n";
echo "  SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);