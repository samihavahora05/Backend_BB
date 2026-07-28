<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/dev/migrate-fresh', function () {
    try {
        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        return response()->json(['success' => true, 'output' => Artisan::output()]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

Route::get('/dev/db-check', function () {
    try {
        $columns = Illuminate\Support\Facades\Schema::getColumnListing('leads');
        return response()->json(['success' => true, 'columns' => $columns]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

// Remove old dev routes to avoid cache issues
Route::get('/dev/clear', function() {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    return 'cleared';
});

Route::get('/dev/perms', function () {
    return response()->json([
        'super_admin' => \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))->with('roles.permissions')->first(),
        'admin' => \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->with('roles.permissions')->first(),
        'all_permissions' => \Spatie\Permission\Models\Permission::pluck('name')
    ]);
});

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\CallbackRequestController;
use App\Http\Controllers\ScholarshipProgramController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PlacementPartnerController;
use App\Http\Controllers\SuccessStoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\MentorSessionController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\InternshipApplicationController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ChatController;


Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

Route::get('/debug-log', function () {
    $path = storage_path('logs/laravel.log');
    if (!file_exists($path)) return 'No log file';
    $file = file($path);
    $lastLines = array_slice($file, -500);
    return response(implode("", $lastLines))->header('Content-Type', 'text/plain');
});

Route::get('/debug-clear', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        return "Cache cleared! " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});

Route::get('/debug-categories', function () {
    if (\App\Models\CourseCategory::count() === 0) {
        \App\Models\CourseCategory::create(['name' => 'Data Science', 'slug' => 'data-science', 'status' => 'active']);
        \App\Models\CourseCategory::create(['name' => 'Web Development', 'slug' => 'web-development', 'status' => 'active']);
    }
    if (\App\Models\CourseLevel::count() === 0) {
        \App\Models\CourseLevel::create(['title' => 'Beginner', 'slug' => 'beginner', 'status' => 'active']);
        \App\Models\CourseLevel::create(['title' => 'Intermediate', 'slug' => 'intermediate', 'status' => 'active']);
        \App\Models\CourseLevel::create(['title' => 'Advanced', 'slug' => 'advanced', 'status' => 'active']);
    }
    return response()->json([
        'count' => \App\Models\CourseCategory::count(),
        'categories' => \App\Models\CourseCategory::all()
    ]);
});

Route::get('/debug-courses', function () {
    return response()->json(\App\Models\Course::all());
});

Route::get('/debug-dashboard', function (\App\Services\DashboardService $service) {
    if (function_exists('opcache_reset')) opcache_reset();
    \Illuminate\Support\Facades\Cache::forget('dashboard.summary');
    \Illuminate\Support\Facades\Cache::forget('dashboard.charts.monthly');
    try {
        return response()->json($service->getPlatformSummary());
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 200);
    }
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/log-tail', function () {
    $path = storage_path('logs/laravel.log');
    if (!file_exists($path)) return 'No log file';
    
    $file = fopen($path, 'r');
    fseek($file, -2000, SEEK_END);
    $tail = fread($file, 2000);
    fclose($file);
    return response($tail)->header('Content-Type', 'text/plain');
});
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/social-login', [AuthController::class, 'socialLogin']);

// Payment Gateway Webhooks (NO AUTH - called by Razorpay servers directly)
Route::post('/webhooks/razorpay', [\App\Http\Controllers\Api\RazorpayWebhookController::class, 'handle']);

// Public Lead Management API
Route::post('/consultations', [ConsultationController::class, 'store']);
Route::post('/callback-requests', [CallbackRequestController::class, 'store']);

// Public Scholarships & Contests API
Route::apiResource('scholarships', ScholarshipProgramController::class)->only(['index', 'show']);
Route::apiResource('contests', ContestController::class)->only(['index', 'show']);

// Public Blog CMS, FAQs, Testimonials, Reviews
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{slug}', [BlogController::class, 'show']);
Route::apiResource('faqs', FaqController::class)->only(['index', 'show']);
Route::apiResource('testimonials', TestimonialController::class)->only(['index', 'show']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::apiResource('placement-partners', PlacementPartnerController::class)->only(['index', 'show']);
Route::apiResource('success-stories', SuccessStoryController::class)->only(['index', 'show']);

// ─── PUBLIC Course Catalog & Career APIs ─────────────────────────────────────
Route::prefix('public')->middleware('throttle:60,1')->group(function () {
    // Course Catalog
    Route::get('courses', [\App\Http\Controllers\Api\Public\PublicCourseController::class, 'index']);
    Route::get('courses/{slug}', [\App\Http\Controllers\Api\Public\PublicCourseController::class, 'show']);
    // Enroll-status needs optional auth — use auth:sanctum with nullable
    Route::middleware('auth:sanctum')->get('courses/{slug}/enroll-status', [\App\Http\Controllers\Api\Public\PublicCourseController::class, 'enrollStatus']);

    // Course Categories (for filter dropdowns)
    Route::get('course-categories', fn() => response()->json([
        'success' => true,
        'data'    => \App\Models\CourseCategory::where('status', 'active')->orderBy('name')->get(['id', 'name', 'slug']),
    ]));

    // Course Levels
    Route::get('course-levels', fn() => response()->json([
        'success' => true,
        'data'    => \App\Models\CourseLevel::where('status', 'active')->orderBy('title')->get(['id', 'title', 'slug']),
    ]));

    // CMS & Platform Stats
    Route::get('stats', [\App\Http\Controllers\Api\Public\PublicCmsController::class, 'stats']);
    Route::get('settings', [\App\Http\Controllers\Api\Public\PublicCmsController::class, 'settings']);
    Route::get('partners', [\App\Http\Controllers\Api\Public\PublicCmsController::class, 'partners']);
    Route::get('testimonials-cms', [\App\Http\Controllers\Api\Public\PublicCmsController::class, 'testimonials']);
    Route::get('faqs-cms', [\App\Http\Controllers\Api\Public\PublicCmsController::class, 'faqs']);
    Route::get('experts-cms', [\App\Http\Controllers\Api\Public\PublicCmsController::class, 'experts']);

    // Featured Courses (Homepage Hero)
    Route::get('featured-courses', fn() => response()->json([
        'success' => true,
        'data'    => \App\Models\Course::with(['category', 'level'])
            ->where('status', 'Published')
            ->where('is_featured', true)
            ->where('is_archived', false)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'slug'           => $c->slug,
                'title'          => $c->title,
                'thumbnail'      => $c->thumbnail ? asset('storage/' . $c->thumbnail) : null,
                'price'          => $c->price,
                'discount_price' => $c->discount_price,
                'course_type'    => $c->course_type,
                'category'       => $c->category?->name,
                'level'          => $c->level?->name,
            ]),
    ]));

    // ─── Blog CMS ─────────────────────────────────────────────────────────────
    Route::get('blogs', [\App\Http\Controllers\Api\Public\PublicBlogController::class, 'index']);
    Route::get('blogs/{slug}', [\App\Http\Controllers\Api\Public\PublicBlogController::class, 'show']);
    Route::get('blog-categories', [\App\Http\Controllers\Api\Public\PublicBlogController::class, 'categories']);

    // ─── Auth-required routes (jobs, internships, expert booking, etc) ────────
    Route::middleware('auth:sanctum')->group(function () {
        // Job Applications
        Route::post('jobs/{id}/apply', [\App\Http\Controllers\Api\Public\PublicJobController::class, 'apply']);
        Route::post('jobs/{id}/bookmark', [\App\Http\Controllers\Api\Public\PublicJobController::class, 'toggleBookmark']);
        Route::get('jobs/my-applications', [\App\Http\Controllers\Api\Public\PublicJobController::class, 'myApplications']);
        Route::get('jobs/bookmarks', [\App\Http\Controllers\Api\Public\PublicJobController::class, 'bookmarks']);

        // Internship Applications
        Route::post('internships/{id}/apply', [\App\Http\Controllers\Api\Public\PublicInternshipController::class, 'apply']);
        Route::get('internships/my-applications', [\App\Http\Controllers\Api\Public\PublicInternshipController::class, 'myApplications']);

        // Expert Bookings
        Route::post('experts/sessions/{session_id}/book', [\App\Http\Controllers\Api\Public\PublicExpertController::class, 'bookSession']);
        Route::post('experts/bookings/{booking_id}/verify', [\App\Http\Controllers\Api\Public\PublicExpertController::class, 'verifyBooking']);

        // Contests
        Route::post('contests/{id}/register', [\App\Http\Controllers\Api\Public\PublicContestController::class, 'register']);
        Route::post('contests/{id}/submit', [\App\Http\Controllers\Api\Public\PublicContestController::class, 'submitProject']);

        // Scholarships
        Route::post('scholarships/{id}/apply', [\App\Http\Controllers\Api\Public\PublicScholarshipController::class, 'apply']);

        // CRM (Support Tickets)
        Route::post('support/tickets', [\App\Http\Controllers\Api\Public\PublicCRMController::class, 'createTicket']);
    });

    // ─── Jobs Platform ───────────────────────────────────────────────────────
    Route::get('jobs', [\App\Http\Controllers\Api\Public\PublicJobController::class, 'index']);
    Route::get('jobs/{id}', [\App\Http\Controllers\Api\Public\PublicJobController::class, 'show'])->where('id', '[0-9]+');

    // ─── Internships Platform ─────────────────────────────────────────────────
    Route::get('internships', [\App\Http\Controllers\Api\Public\PublicInternshipController::class, 'index']);
    Route::get('internships/{id}', [\App\Http\Controllers\Api\Public\PublicInternshipController::class, 'show'])->where('id', '[0-9]+');

    // ─── Experts & Mentorship Platform ────────────────────────────────────────
    Route::get('experts', [\App\Http\Controllers\Api\Public\PublicExpertController::class, 'index']);
    Route::get('experts/{id}', [\App\Http\Controllers\Api\Public\PublicExpertController::class, 'show'])->where('id', '[0-9]+');

    // ─── Certificates (Verification & Download) ───────────────────────────────
    Route::get('certificates/{certificate_number}/verify', [\App\Http\Controllers\Api\Public\PublicCertificateController::class, 'verify']);
    Route::get('certificates/{certificate_number}/download', [\App\Http\Controllers\Api\Public\PublicCertificateController::class, 'download']);

    // ─── Contests Platform (Hackathons) ───────────────────────────────────────
    Route::get('contests', [\App\Http\Controllers\Api\Public\PublicContestController::class, 'index']);
    Route::get('contests/{id}', [\App\Http\Controllers\Api\Public\PublicContestController::class, 'show'])->where('id', '[0-9]+');

    // ─── Scholarships Engine ──────────────────────────────────────────────────
    Route::get('scholarships', [\App\Http\Controllers\Api\Public\PublicScholarshipController::class, 'index']);
    Route::get('scholarships/{id}', [\App\Http\Controllers\Api\Public\PublicScholarshipController::class, 'show'])->where('id', '[0-9]+');

    // ─── CRM & Leads ──────────────────────────────────────────────────────────
    Route::post('contact', [\App\Http\Controllers\Api\Public\PublicCRMController::class, 'submitLead']);

    // ─── Newsletter ──────────────────────────────────────────────────────────
    Route::post('newsletter/subscribe', [\App\Http\Controllers\Api\Public\PublicNewsletterController::class, 'subscribe']);

    // ─── SEO Metadata ─────────────────────────────────────────────────────────
    Route::get('seo', [\App\Http\Controllers\Api\Public\PublicSeoController::class, 'getMetadata']);
    Route::post('newsletter/unsubscribe', [\App\Http\Controllers\Api\Public\PublicNewsletterController::class, 'unsubscribe']);

});

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        
        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::put('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
            Route::put('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
        });
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/me', [AuthController::class, 'me']);


    // Checkout Integration
    Route::prefix('checkout')->group(function () {
        Route::post('/create-order', [\App\Http\Controllers\Api\CheckoutController::class, 'createOrder']);
        Route::post('/verify', [\App\Http\Controllers\Api\CheckoutController::class, 'verifyPayment']);
    });

    // Student Portal Integration
    Route::middleware('role:student,sanctum')->prefix('student')->group(function () {
        Route::get('/courses', [\App\Http\Controllers\Api\Student\StudentCourseController::class, 'index']);
        Route::post('/courses/{course_id}/lessons/{lesson_id}/complete', [\App\Http\Controllers\Api\Student\StudentCourseController::class, 'markLessonComplete']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\Student\StudentDashboardController::class, 'metrics']);
        
        // New Real Data Endpoints
        Route::get('/messages', [\App\Http\Controllers\Api\Student\StudentMessageController::class, 'index']);
        Route::get('/internships', [\App\Http\Controllers\Api\Student\StudentInternshipController::class, 'index']);
        Route::get('/scholarships', [\App\Http\Controllers\Api\Student\StudentScholarshipController::class, 'index']);
        Route::get('/certificates', [\App\Http\Controllers\Api\Student\StudentCertificateController::class, 'index']);
        
        // Resume
        Route::post('/resume/upload', [\App\Http\Controllers\Api\Student\StudentResumeController::class, 'upload']);
    });

    // Company Portal Integration
    Route::middleware('role:company,sanctum')->prefix('company')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Company\CompanyDashboardController::class, 'index']);
        Route::get('/analytics', [\App\Http\Controllers\Api\Company\CompanyDashboardController::class, 'analytics']);
        
        // Jobs
        Route::get('/jobs', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'index']);
        Route::post('/jobs', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'store']);
        Route::get('/jobs/{id}', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'show']);
        Route::put('/jobs/{id}', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'update']);
        Route::put('/jobs/{id}/status', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'updateStatus']);
        Route::delete('/jobs/{id}', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'destroy']);
        
        // Internships
        Route::get('/internships', [\App\Http\Controllers\Api\Company\CompanyInternshipController::class, 'index']);
        
        // Applicants
        Route::get('/applicants', [\App\Http\Controllers\Api\Company\CompanyApplicantController::class, 'index']);
        Route::get('/applicants/{id}', [\App\Http\Controllers\Api\Company\CompanyApplicantController::class, 'show']);
        Route::post('/applicants/{id}/status', [\App\Http\Controllers\Api\Company\CompanyApplicantController::class, 'updateStatus']);
        
        // Interviews
        Route::get('/interviews', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'index']);
        Route::post('/interviews', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'store']);
        Route::put('/interviews/{id}', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'update']);
        
        // Offers
        Route::get('/offers', [\App\Http\Controllers\Api\Company\CompanyOfferController::class, 'index']);
        Route::post('/offers', [\App\Http\Controllers\Api\Company\CompanyOfferController::class, 'store']);
    });

    // Expert Portal Integration
    Route::middleware('role:expert,sanctum')->prefix('expert')->group(function () {
        Route::get('/metrics', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'metrics']);
        Route::get('/sessions/upcoming', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'upcomingSessions']);
        Route::put('/sessions/{id}/meeting-link', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'updateMeetingLink']);
        Route::get('/earnings/chart', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'earningsChart']);
        Route::get('/mentees/requests', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'menteeRequests']);
        Route::post('/mentees/requests/{id}/accept', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'acceptRequest']);
        Route::post('/mentees/requests/{id}/decline', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'declineRequest']);
        Route::get('/transactions', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'transactions']);
        Route::get('/mentees', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'mentees']);
        Route::get('/schedule', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'schedule']);
    });

    // Company Portal Integration
    Route::middleware(['auth:sanctum', 'role:company'])->prefix('company')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\Company\CompanyDashboardController::class, 'index']);
        Route::get('/analytics', [\App\Http\Controllers\Api\Company\CompanyDashboardController::class, 'analytics']);
        
        // Jobs
        Route::apiResource('/jobs', \App\Http\Controllers\Api\Company\CompanyJobController::class);
        Route::put('/jobs/{id}/status', [\App\Http\Controllers\Api\Company\CompanyJobController::class, 'updateStatus']);
        
        // Internships
        Route::apiResource('/internships', \App\Http\Controllers\Api\Company\CompanyInternshipController::class);
        Route::put('/internships/{id}/status', [\App\Http\Controllers\Api\Company\CompanyInternshipController::class, 'updateStatus']);
        
        // Applicants
        Route::get('/applicants', [\App\Http\Controllers\Api\Company\CompanyApplicantController::class, 'index']);
        Route::get('/applicants/{id}', [\App\Http\Controllers\Api\Company\CompanyApplicantController::class, 'show']);
        Route::put('/applicants/{id}/status', [\App\Http\Controllers\Api\Company\CompanyApplicantController::class, 'updateStatus']);
        
        // Interviews
        Route::get('/interviews', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'index']);
        Route::post('/interviews', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'store']);
        Route::put('/interviews/{id}', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'update']);
        Route::delete('/interviews/{id}', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'destroy']);
        Route::put('/interviews/{id}/status', [\App\Http\Controllers\Api\Company\CompanyInterviewController::class, 'updateStatus']);
        
        // Offers
        Route::get('/offers', [\App\Http\Controllers\Api\Company\CompanyOfferController::class, 'index']);
        Route::post('/offers', [\App\Http\Controllers\Api\Company\CompanyOfferController::class, 'store']);
        Route::put('/offers/{id}', [\App\Http\Controllers\Api\Company\CompanyOfferController::class, 'update']);
        Route::delete('/offers/{id}', [\App\Http\Controllers\Api\Company\CompanyOfferController::class, 'destroy']);
        
        // Settings & Team
        Route::get('/settings', [\App\Http\Controllers\Company\CompanySettingsController::class, 'getSettings']);
        Route::put('/settings', [\App\Http\Controllers\Company\CompanySettingsController::class, 'updateSettings']);
        Route::get('/team', [\App\Http\Controllers\Company\CompanySettingsController::class, 'getTeamMembers']);
        Route::post('/team/invite', [\App\Http\Controllers\Company\CompanySettingsController::class, 'inviteTeamMember']);
        Route::delete('/team/{id}', [\App\Http\Controllers\Company\CompanySettingsController::class, 'removeTeamMember']);

        // Support Tickets
        Route::get('/support/tickets', [\App\Http\Controllers\Company\SupportTicketController::class, 'index']);
        Route::post('/support/tickets', [\App\Http\Controllers\Company\SupportTicketController::class, 'store']);
        Route::get('/support/tickets/{id}', [\App\Http\Controllers\Company\SupportTicketController::class, 'show']);
        Route::post('/support/tickets/{id}/reply', [\App\Http\Controllers\Company\SupportTicketController::class, 'reply']);
    });

    // Job Seeker Portal Integration
    Route::middleware(['auth:sanctum', 'role:job-seeker|jobseeker'])->prefix('jobseeker')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'index']);
        
        // Applications
        Route::get('/applications', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'applications']);
        Route::post('/applications/{id}/withdraw', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'withdrawApplication']);
        
        // Interviews
        Route::get('/interviews', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'interviews']);
        
        // Offers
        Route::get('/offers', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'offers']);
        Route::post('/offers/{id}/accept', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'acceptOffer']);
        Route::post('/offers/{id}/reject', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'rejectOffer']);
        
        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\JobseekerProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\JobseekerProfileController::class, 'update']);
        Route::post('/resume', [\App\Http\Controllers\Api\JobseekerProfileController::class, 'uploadResume']);
        
        // Settings
        Route::get('/settings', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'settings']);
        Route::put('/settings', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'updateSettings']);
        Route::put('/change-password', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'changePassword']);
        Route::post('/avatar', [\App\Http\Controllers\Api\JobseekerDashboardController::class, 'uploadAvatar']);
    });


    // Intern Portal Integration
    Route::middleware(['role:intern'])->prefix('intern')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\InternDashboardController::class, 'index']);
        Route::get('/applications', [\App\Http\Controllers\Api\InternDashboardController::class, 'applications']);
        Route::get('/mentor-sessions', [\App\Http\Controllers\Api\InternDashboardController::class, 'mentorSessions']);
        Route::get('/settings', [\App\Http\Controllers\Api\InternDashboardController::class, 'settings']);
        Route::put('/settings', [\App\Http\Controllers\Api\InternDashboardController::class, 'updateSettings']);
        Route::get('/resume', [\App\Http\Controllers\Api\InternDashboardController::class, 'resume']);
        Route::post('/resume', [\App\Http\Controllers\Api\InternDashboardController::class, 'uploadResume']);
    });

    // Profiles API
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/role', [ProfileController::class, 'updateRole']);
    // Alias for password change from profile pages
    Route::put('/profile/password', [AuthController::class, 'changePassword']);

    // Checkout & Payments
    Route::post('/checkout/create-order', [\App\Http\Controllers\OrderController::class, 'store']);
    Route::post('/checkout/verify', [\App\Http\Controllers\PaymentController::class, 'verify']);

    // Dashboard Stats API
    Route::get('/dashboard/student', [\App\Http\Controllers\Api\Student\StudentDashboardController::class, 'metrics']);
    Route::get('/dashboard/student/placement-progress', [\App\Http\Controllers\Api\Student\StudentDashboardController::class, 'placementProgress']);
    
    // Global Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
    
    Route::post('/notifications/device-token', [NotificationController::class, 'storeDeviceToken']);
    Route::get('/certificates', [CertificateController::class, 'index']);
    Route::apiResource('mentor-sessions', MentorSessionController::class);

    // Messages API
    Route::prefix('messages')->group(function () {
        Route::get('/unread', [\App\Http\Controllers\Api\MessageController::class, 'unreadSummary']);
        Route::get('/', [ChatController::class, 'index']);
        Route::get('/{id}', [ChatController::class, 'show']);
        Route::post('/{id}', [ChatController::class, 'store']);
    });

    // Student Dashboard & LMS Progress API
    Route::prefix('student')->group(function () {
        // Resume
        Route::get('/resume', [\App\Http\Controllers\Api\Student\StudentDashboardController::class, 'resume']);
        Route::post('/resume/upload', [\App\Http\Controllers\Api\Student\StudentDashboardController::class, 'uploadResume']);

        // Enrolled courses & progress
        Route::get('/courses', [\App\Http\Controllers\Api\Student\StudentCourseController::class, 'index']);
        Route::get('/courses/{course_id}', [\App\Http\Controllers\Api\Student\StudentCourseController::class, 'show']);
        Route::post('/courses/{course_id}/enroll', [\App\Http\Controllers\Api\Student\StudentCourseController::class, 'enroll']);
        Route::post('/courses/{course_id}/lessons/{lesson_id}/complete', [\App\Http\Controllers\Api\Student\StudentCourseController::class, 'markLessonComplete']);

        // LMS Notes
        Route::get('/courses/{course_id}/notes', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'getNotes']);
        Route::post('/courses/{course_id}/notes', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'addNote']);
        Route::delete('/courses/{course_id}/notes/{note_id}', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'deleteNote']);

        // LMS Resources
        Route::get('/courses/{course_id}/resources', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'getResources']);

        // LMS Q&A
        Route::get('/courses/{course_id}/questions', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'getQuestions']);
        Route::post('/courses/{course_id}/questions', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'postQuestion']);
        Route::post('/courses/{course_id}/questions/{question_id}/answer', [\App\Http\Controllers\Api\Student\StudentLMSController::class, 'postAnswer']);

        // Quizzes
        Route::get('/lessons/{lesson_id}/quiz', [\App\Http\Controllers\Api\Student\StudentQuizController::class, 'show']);
        Route::post('/quizzes/{quiz_id}/submit', [\App\Http\Controllers\Api\Student\StudentQuizController::class, 'submit']);

        // Scholarships & Contests
        Route::get('/scholarships', [\App\Http\Controllers\Api\Student\StudentScholarshipController::class, 'index']);

        // Applications
        Route::get('/applications', [\App\Http\Controllers\Api\Student\StudentDashboardController::class, 'placementProgress']);

        // Mentor Sessions
        Route::get('/mentor-sessions', function (\Illuminate\Http\Request $r) {
            return response()->json(\App\Models\MentorSession::with('expert.user')
                ->where('student_id', $r->user()->id)
                ->orderBy('scheduled_at', 'desc')
                ->get());
        });

        // Certificates earned
        Route::get('/certificates', function(\Illuminate\Http\Request $r) {
            $certs = \App\Models\IssuedCertificate::with(['course'])
                ->where('user_id', $r->user()->id)
                ->where('status', 'Issued')
                ->latest('issued_at')
                ->get()
                ->map(fn($c) => [
                    'id'                 => $c->id,
                    'certificate_number' => $c->certificate_number,
                    'course'             => $c->course->title ?? 'N/A',
                    'issued_at'          => $c->issued_at?->format('M d, Y'),
                    'status'             => $c->status,
                ]);
            return response()->json(['success' => true, 'data' => $certs]);
        });

        // Orders & Payments history
        Route::get('/orders', function(\Illuminate\Http\Request $r) {
            $orders = \App\Models\Order::with(['items', 'payments'])
                ->where('user_id', $r->user()->id)
                ->latest()
                ->get()
                ->map(fn($o) => [
                    'id'           => $o->id,
                    'order_number' => $o->order_number,
                    'amount'       => $o->total_amount,
                    'status'       => $o->status,
                    'items'        => $o->items->map(fn($i) => [
                        'type' => class_basename($i->purchasable_type),
                        'id'   => $i->purchasable_id,
                        'price'=> $i->price,
                    ]),
                    'paid_at' => $o->updated_at?->format('M d, Y'),
                ]);
            return response()->json(['success' => true, 'data' => $orders]);
        });
    });
    
    // Expert Dashboard API
    Route::prefix('expert')->group(function () {
        Route::get('/metrics', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'metrics']);
        Route::get('/sessions/upcoming', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'upcomingSessions']);
        Route::get('/earnings/chart', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'earningsChart']);
        Route::get('/mentees/requests', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'menteeRequests']);
        Route::get('/mentees', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'mentees']);
        Route::get('/transactions', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'transactions']);
        Route::get('/schedule', [\App\Http\Controllers\Api\Expert\ExpertDashboardController::class, 'schedule']);
    });
    
    // College Portal API (Placement Cell)
    Route::middleware('role:college,sanctum')->prefix('college')->group(function () {
        // Dashboard & Students
        Route::get('/dashboard', [\App\Http\Controllers\Api\College\CollegeDashboardController::class, 'index']);
        Route::get('/students', [\App\Http\Controllers\Api\College\CollegeDashboardController::class, 'students']);
        
        // Placement Drives
        Route::apiResource('/placement-drives', \App\Http\Controllers\Api\College\CollegePlacementDriveController::class);
        Route::post('/placement-drives/{id}/duplicate', [\App\Http\Controllers\Api\College\CollegePlacementDriveController::class, 'duplicate']);
        Route::put('/placement-drives/{id}/publish', [\App\Http\Controllers\Api\College\CollegePlacementDriveController::class, 'publish']);
        Route::put('/placement-drives/{id}/close', [\App\Http\Controllers\Api\College\CollegePlacementDriveController::class, 'close']);
        Route::put('/placement-drives/{id}/archive', [\App\Http\Controllers\Api\College\CollegePlacementDriveController::class, 'archive']);
        Route::get('/placement-drives/{id}/export', [\App\Http\Controllers\Api\College\CollegePlacementDriveController::class, 'export']);
        
        // Internship Drives
        Route::apiResource('/internship-drives', \App\Http\Controllers\Api\College\CollegeInternshipDriveController::class);
        Route::post('/internship-drives/{id}/duplicate', [\App\Http\Controllers\Api\College\CollegeInternshipDriveController::class, 'duplicate']);
        Route::put('/internship-drives/{id}/publish', [\App\Http\Controllers\Api\College\CollegeInternshipDriveController::class, 'publish']);
        Route::put('/internship-drives/{id}/close', [\App\Http\Controllers\Api\College\CollegeInternshipDriveController::class, 'close']);
        Route::put('/internship-drives/{id}/archive', [\App\Http\Controllers\Api\College\CollegeInternshipDriveController::class, 'archive']);
        Route::get('/internship-drives/{id}/export', [\App\Http\Controllers\Api\College\CollegeInternshipDriveController::class, 'export']);
        
        // BlueBoxx Admin info (for drive creation — only BlueBoxx can be partner)
        Route::get('/blueboxx-admin', function() {
            $admin = \App\Models\User::role('super_admin')->first();
            return response()->json(['success' => true, 'data' => $admin ? ['id' => $admin->id, 'name' => $admin->name] : null]);
        });
        
        // Connected Companies
        Route::get('/companies', [\App\Http\Controllers\Api\College\CollegeCompanyController::class, 'index']);
        Route::delete('/companies/{id}', [\App\Http\Controllers\Api\College\CollegeCompanyController::class, 'destroy']);
        
        // Student Management
        Route::get('/students/export', [\App\Http\Controllers\Api\College\CollegeDashboardController::class, 'exportStudents']);
        Route::post('/students/import', [\App\Http\Controllers\Api\College\CollegeDashboardController::class, 'importStudents']);
        
        // Reports
        Route::get('/reports/export', [\App\Http\Controllers\Api\College\CollegeDashboardController::class, 'exportReports']);
        
        // Notifications
        Route::get('/notifications', function(\Illuminate\Http\Request $request) {
            $notifications = $request->user()->notifications()->take(20)->get()->map(function($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            });
            return response()->json(['success' => true, 'data' => $notifications]);
        });
        Route::put('/notifications/read-all', function(\Illuminate\Http\Request $request) {
            $request->user()->unreadNotifications->markAsRead();
            return response()->json(['success' => true]);
        });

    });

    // College Profile Settings — accessible to college AND super_admin
    Route::prefix('college')->group(function () {
        Route::get('/profile', function(\Illuminate\Http\Request $request) {
            $user = $request->user();
            $profile = \Illuminate\Support\Facades\DB::table('college_profiles')->where('user_id', $user->id)->first();
            return response()->json([
                'success' => true,
                'data' => [
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'phone'            => $profile->phone ?? '',
                    'website'          => $profile->website ?? '',
                    'address'          => $profile->address ?? '',
                    'contact_person'   => $profile->contact_person ?? '',
                    'placement_drive'  => $profile->placement_officer ?? '2026 Batch',
                    'target_placement' => '90',
                ]
            ]);
        });
        Route::put('/profile', function(\Illuminate\Http\Request $request) {
            $user = $request->user();
            $data = $request->validate([
                'name'             => 'sometimes|string|max:255',
                'email'            => 'sometimes|nullable|email|max:255',
                'phone'            => 'sometimes|nullable|string|max:30',
                'website'          => 'sometimes|nullable|string|max:255',
                'address'          => 'sometimes|nullable|string|max:500',
                'contact_person'   => 'sometimes|nullable|string|max:255',
                'placement_drive'  => 'sometimes|nullable|string|max:100',
                'target_placement' => 'sometimes|nullable|string|max:10',
            ]);

            if (isset($data['name'])) {
                $user->update(['name' => $data['name']]);
            }
            if (isset($data['email']) && $data['email'] !== $user->email) {
                $user->update(['email' => $data['email']]);
            }

            \Illuminate\Support\Facades\DB::table('college_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'phone'             => $data['phone'] ?? null,
                    'website'           => $data['website'] ?? null,
                    'address'           => $data['address'] ?? null,
                    'contact_person'    => $data['contact_person'] ?? null,
                    'placement_officer' => $data['placement_drive'] ?? null,
                    'college_name'      => $data['name'] ?? $user->name,
                    'email'             => $data['email'] ?? $user->email,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );

            return response()->json(['success' => true, 'message' => 'Profile updated successfully']);
        });
    });

    // Auth Scholarships & Contests (Applying)
    Route::post('/scholarships/{id}/apply', [ScholarshipProgramController::class, 'apply']);
    Route::post('/contests/{id}/register', [ContestController::class, 'registerUser']);
    
    // Auth Blog Interactions & Reviews
    Route::post('/blogs/{id}/like', [BlogController::class, 'toggleLike']);
    Route::post('/blogs/{id}/comments', [BlogController::class, 'addComment']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    
    // Admin API
    Route::middleware(['auth:sanctum', 'role:super_admin|admin,sanctum'])->prefix('admin')->group(function () {
        // SEO Administration
        Route::apiResource('seo-metadata', \App\Http\Controllers\Api\Admin\AdminSeoController::class);
        // Notifications & Badges
        Route::get('notifications', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'index']);
        Route::get('notifications/badges', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'badges']);
        Route::put('notifications/read-all', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'markAllAsRead']);
        Route::put('notifications/{id}/read', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'markAsRead']);

        // Dashboard APIs are now handled below (AdminDashboardController)
        Route::get('dashboard/summary', [AdminDashboardController::class, 'summary']);
        Route::get('dashboard/charts', [AdminDashboardController::class, 'charts']);
        Route::get('dashboard/feed', [AdminDashboardController::class, 'feed']);
        Route::get('dashboard/top/courses', [AdminDashboardController::class, 'topCourses']);
        Route::get('dashboard/recent/enrollments', [AdminDashboardController::class, 'recentEnrollments']);
        Route::get('users/export', [UserController::class, 'export']);
        Route::apiResource('users', UserController::class);

        // Support Management
        Route::get('support/tickets', [\App\Http\Controllers\Admin\AdminSupportController::class, 'index']);
        Route::get('support/tickets/{id}', [\App\Http\Controllers\Admin\AdminSupportController::class, 'show']);
        Route::put('support/tickets/{id}/status', [\App\Http\Controllers\Admin\AdminSupportController::class, 'updateStatus']);
        Route::put('support/tickets/{id}/assign', [\App\Http\Controllers\Admin\AdminSupportController::class, 'assignAdmin']);
        Route::post('support/tickets/{id}/reply', [\App\Http\Controllers\Admin\AdminSupportController::class, 'reply']);
        Route::post('support/tickets/{id}/notes', [\App\Http\Controllers\Admin\AdminSupportController::class, 'addNote']);

        // Approvals API
        Route::get('approvals', [ApprovalController::class, 'index']);
        Route::put('approvals/{id}/approve', [ApprovalController::class, 'approve']);
        Route::put('approvals/{id}/reject', [ApprovalController::class, 'reject']);
        Route::put('approvals/{id}/suspend', [ApprovalController::class, 'suspend']);
        
        // Leads & CRM API
        Route::get('crm/dashboard', [\App\Http\Controllers\Api\Admin\AdminCRMController::class, 'dashboard']);
        Route::apiResource('leads', \App\Http\Controllers\Api\Admin\AdminLeadController::class);
        Route::post('leads/{id}/convert', [\App\Http\Controllers\Api\Admin\AdminLeadController::class, 'convertToStudent']);
        Route::post('leads/{id}/convert-company', [\App\Http\Controllers\Api\Admin\AdminLeadController::class, 'convertToCompanyLead']);
        Route::post('leads/{id}/convert-corporate', [\App\Http\Controllers\Api\Admin\AdminLeadController::class, 'convertToCorporateLead']);


        // User Manager (Delete Requests)
        Route::get('delete-requests', [\App\Http\Controllers\Admin\DeleteRequestController::class, 'index']);
        Route::post('delete-requests/{deleteRequest}/approve', [\App\Http\Controllers\Admin\DeleteRequestController::class, 'approve']);
        Route::post('delete-requests/{deleteRequest}/reject', [\App\Http\Controllers\Admin\DeleteRequestController::class, 'reject']);

        // System Settings
        Route::get('settings', [\App\Http\Controllers\Api\Admin\AdminSystemSettingController::class, 'index']);
        Route::post('settings', [\App\Http\Controllers\Api\Admin\AdminSystemSettingController::class, 'update']);
        
        // System Settings V2 (Enterprise Module)
        Route::post('system-settings/smtp/test', [\App\Http\Controllers\Api\Admin\SystemSettingsController::class, 'testSmtp']);
        Route::get('system-settings/{group}', [\App\Http\Controllers\Api\Admin\SystemSettingsController::class, 'getSettings']);
        Route::post('system-settings/{group}', [\App\Http\Controllers\Api\Admin\SystemSettingsController::class, 'updateSettings']);
        
        Route::post('system-licenses/{id}/action', [\App\Http\Controllers\Api\Admin\SystemLicenseController::class, 'action']);
        Route::apiResource('system-licenses', \App\Http\Controllers\Api\Admin\SystemLicenseController::class);
        
        Route::apiResource('system-email-templates', \App\Http\Controllers\Api\Admin\SystemEmailTemplateController::class);
        
        Route::get('system-api-credentials', [\App\Http\Controllers\Api\Admin\SystemApiController::class, 'index']);
        Route::post('system-api-credentials/{provider}/show-secret', [\App\Http\Controllers\Api\Admin\SystemApiController::class, 'showSecret']);
        Route::post('system-api-credentials/{provider}/test', [\App\Http\Controllers\Api\Admin\SystemApiController::class, 'testConnection']);
        Route::put('system-api-credentials/{provider}', [\App\Http\Controllers\Api\Admin\SystemApiController::class, 'update']);
        Route::delete('system-api-credentials/{provider}', [\App\Http\Controllers\Api\Admin\SystemApiController::class, 'destroy']);
        
        // System Logs
        Route::get('/security/logs', [\App\Http\Controllers\Api\Admin\AdminLogController::class, 'index']);
        
        // Session Management
        Route::prefix('security/sessions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\AdminSessionController::class, 'index']);
            Route::delete('/others', [\App\Http\Controllers\Api\Admin\AdminSessionController::class, 'destroyOther']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\AdminSessionController::class, 'destroy']);
        });

        // System Backups
        Route::prefix('backups')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'dashboard']);
            Route::get('settings', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'getSettings']);
            Route::put('settings', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'updateSettings']);
            Route::post('generate', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'generate']);
            Route::post('{id}/retry', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'retry']);
            Route::get('{id}/download', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'download']);
            Route::post('{id}/restore', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'restore']);
            Route::delete('{id}', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'destroy']);
        });

        // Certificate Management
        Route::prefix('certificates')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\AdminCertificateController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Admin\AdminCertificateController::class, 'store']);
            
            Route::get('/settings', [\App\Http\Controllers\Api\Admin\CertificateSettingController::class, 'show']);
            Route::put('/settings', [\App\Http\Controllers\Api\Admin\CertificateSettingController::class, 'update']);
            
            Route::apiResource('fonts', \App\Http\Controllers\Api\Admin\CertificateFontController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('templates', \App\Http\Controllers\Api\Admin\CertificateTemplateController::class);
        });
        Route::apiResource('backups', \App\Http\Controllers\Api\Admin\AdminBackupController::class)->only(['index', 'destroy']);

        // Dashboard
        Route::get('dashboard/summary', [AdminDashboardController::class, 'getSummary']);
        Route::get('dashboard/charts', [AdminDashboardController::class, 'getCharts']);
        Route::get('dashboard/top/{module}', [AdminDashboardController::class, 'getTopLists']);
        Route::get('dashboard/recent/{module}', [AdminDashboardController::class, 'getRecentData']);
        Route::get('dashboard/feed', [AdminDashboardController::class, 'getActivityFeed']);
        // Blog System (Enterprise)
        Route::post('blogs/upload-image', [AdminBlogController::class, 'uploadImage']);
        Route::apiResource('blogs', AdminBlogController::class);
        
        // Admin Roles & Permissions
        Route::post('roles/import', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'importRoles']);
        Route::get('roles/export', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'exportRoles']);
        Route::get('roles/audit/export', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'exportAuditLogs']);
        Route::get('roles/audit', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'getAuditLogs']);
        Route::get('roles/{id}/audit', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'getAuditLogs']);
        Route::post('roles/{id}/clone', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'clone']);
        Route::post('roles/{id}/users', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'assignUsers']);
        Route::delete('roles/{id}/users/{userId}', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'removeUser']);
        Route::apiResource('roles', \App\Http\Controllers\Api\Admin\AdminRoleController::class);

        // Account Delete Requests
        Route::get('delete-requests/export', [\App\Http\Controllers\Api\Admin\AdminDeleteRequestController::class, 'export']);
        Route::post('delete-requests/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminDeleteRequestController::class, 'approve']);
        Route::post('delete-requests/{id}/reject', [\App\Http\Controllers\Api\Admin\AdminDeleteRequestController::class, 'reject']);
        Route::apiResource('delete-requests', \App\Http\Controllers\Api\Admin\AdminDeleteRequestController::class)->only(['index', 'show']);

        // Role Requests
        Route::get('role-requests', [\App\Http\Controllers\Api\Admin\AdminRoleRequestController::class, 'index']);
        Route::post('role-requests/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminRoleRequestController::class, 'approve']);
        Route::post('role-requests/{id}/reject', [\App\Http\Controllers\Api\Admin\AdminRoleRequestController::class, 'reject']);

        // Admin Lead Management
        Route::apiResource('consultations', ConsultationController::class)->except(['store']);
        Route::apiResource('callback-requests', CallbackRequestController::class)->except(['store']);
        
        // Admin Scholarships & Contests
        Route::apiResource('scholarships', ScholarshipProgramController::class)->except(['index', 'show']);
        Route::apiResource('contests', ContestController::class)->except(['index', 'show']);
        
        // Admin Site Content (FAQs, Testimonials, Reviews, Partners, Success Stories)
        Route::apiResource('faqs', FaqController::class)->except(['index', 'show']);
        Route::apiResource('testimonials', TestimonialController::class)->except(['index', 'show']);
        Route::apiResource('reviews', ReviewController::class)->except(['index', 'store']);
        Route::apiResource('placement-partners', PlacementPartnerController::class)->except(['index', 'show']);
        Route::apiResource('success-stories', SuccessStoryController::class)->except(['index', 'show']);
        
        // Admin Verify Profile endpoint
        Route::put('verify-profile/{user}', [UserController::class, 'verifyProfile']);
        
        // Student Settings
        Route::get('students/settings', [\App\Http\Controllers\Api\Admin\AdminStudentSettingController::class, 'index']);
        Route::put('students/settings', [\App\Http\Controllers\Api\Admin\AdminStudentSettingController::class, 'update']);
        
        // ----- Enterprise Student Management -----
        Route::prefix('students')->group(function () {
            // Bulk Actions
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\AdminStudentController::class, 'bulkDelete']);
            Route::post('bulk-status', [\App\Http\Controllers\Api\Admin\AdminStudentController::class, 'bulkUpdateStatus']);
            
            // Settings
            Route::get('settings', [\App\Http\Controllers\Api\Admin\AdminStudentSettingController::class, 'index']);
            Route::post('settings', [\App\Http\Controllers\Api\Admin\AdminStudentSettingController::class, 'update']);
            
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminStudentController::class, 'export']);
            Route::post('import', [\App\Http\Controllers\Api\Admin\AdminStudentImportController::class, 'import']);
            
            Route::put('{id}/suspend', [\App\Http\Controllers\Api\Admin\AdminStudentController::class, 'suspend']);
            Route::put('{id}/activate', [\App\Http\Controllers\Api\Admin\AdminStudentController::class, 'activate']);
            Route::post('{id}/reset-password', [\App\Http\Controllers\Api\Admin\AdminStudentController::class, 'resetPassword']);
            Route::get('{id}/dashboard-data', [\App\Http\Controllers\Api\Admin\AdminStudentDashboardController::class, 'getAggregatedData']);
        });
        Route::apiResource('students', \App\Http\Controllers\Api\Admin\AdminStudentController::class);
        
        // ----- Enterprise ATS Jobs Management -----
        Route::prefix('jobs')->group(function () {
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminJobController::class, 'export']);
            Route::get('dashboard-metrics', [\App\Http\Controllers\Api\Admin\AdminJobDashboardController::class, 'getMetrics']);
            Route::post('{id}/duplicate', [\App\Http\Controllers\Api\Admin\AdminJobController::class, 'duplicate']);
            
            // Applications
            Route::get('{id}/applications', [\App\Http\Controllers\Api\Admin\AdminJobApplicationController::class, 'index']);
        });
        Route::apiResource('jobs', \App\Http\Controllers\Api\Admin\AdminJobController::class);

        // College Placement Drives
        Route::get('placement-drives', [\App\Http\Controllers\Api\Admin\AdminPlacementDriveController::class, 'index']);
        Route::put('placement-drives/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminPlacementDriveController::class, 'approve']);
        Route::put('placement-drives/{id}/reject', [\App\Http\Controllers\Api\Admin\AdminPlacementDriveController::class, 'reject']);
        
        // College Internship Drives
        Route::get('internship-drives', [\App\Http\Controllers\Api\Admin\AdminInternshipDriveController::class, 'index']);
        Route::put('internship-drives/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminInternshipDriveController::class, 'approve']);
        Route::put('internship-drives/{id}/reject', [\App\Http\Controllers\Api\Admin\AdminInternshipDriveController::class, 'reject']);
        
        // ATS Applications & Interviews
        Route::prefix('job-applications')->group(function () {
            Route::get('{id}', [\App\Http\Controllers\Api\Admin\AdminJobApplicationController::class, 'show']);
            Route::put('{id}/status', [\App\Http\Controllers\Api\Admin\AdminJobApplicationController::class, 'updateStatus']);
            
            // Interviews
            Route::post('{id}/interviews', [\App\Http\Controllers\Api\Admin\AdminJobInterviewController::class, 'schedule']);
        });
        
        Route::prefix('job-interviews')->group(function () {
            Route::put('{id}/grade', [\App\Http\Controllers\Api\Admin\AdminJobInterviewController::class, 'grade']);
        });
        
        // ----- Enterprise LMS Instructor Management -----
        Route::prefix('instructors')->group(function () {
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminInstructorController::class, 'export']);
            Route::get('dashboard-metrics', [\App\Http\Controllers\Api\Admin\AdminInstructorDashboardController::class, 'getMetrics']);
            Route::put('{id}/status', [\App\Http\Controllers\Api\Admin\AdminInstructorWorkflowController::class, 'updateStatus']);
            Route::post('{id}/reset-password', [\App\Http\Controllers\Api\Admin\AdminInstructorWorkflowController::class, 'resetPassword']);
            Route::get('{id}/courses', [\App\Http\Controllers\Api\Admin\AdminInstructorAssignmentController::class, 'getCourses']);
            Route::post('{id}/courses', [\App\Http\Controllers\Api\Admin\AdminInstructorAssignmentController::class, 'assignCourse']);
            Route::get('{id}/metrics', [\App\Http\Controllers\Api\Admin\AdminInstructorDashboardController::class, 'getInstructorMetrics']);
        });
        Route::apiResource('instructors', \App\Http\Controllers\Api\Admin\AdminInstructorController::class);
        
        // ----- Enterprise Internship Management -----
        Route::prefix('internships')->group(function () {
            Route::post('bulk-update-status', [\App\Http\Controllers\Api\Admin\InternshipController::class, 'bulkUpdateStatus']);
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\InternshipController::class, 'bulkDelete']);
            Route::post('{id}/duplicate', [\App\Http\Controllers\Api\Admin\InternshipController::class, 'duplicate']);
            Route::get('stats', [\App\Http\Controllers\Api\Admin\AdminInternshipController::class, 'stats']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\InternshipController::class, 'export']);
            
            // Global Applications & Submissions – use AdminInternshipController (no policy gates)
            Route::get('all-applications', [\App\Http\Controllers\Api\Admin\AdminInternshipController::class, 'allApplications']);
            Route::get('all-submissions', [\App\Http\Controllers\Api\Admin\AdminInternshipController::class, 'allSubmissions']);

            // Per-Internship Applications
            Route::get('{id}/applications', [\App\Http\Controllers\Api\Admin\AdminInternshipController::class, 'applicationsByInternship']);
            Route::get('applications/{id}', [\App\Http\Controllers\Api\Admin\InternshipApplicationController::class, 'show']);
            Route::put('applications/{id}/status', [\App\Http\Controllers\Api\Admin\AdminInternshipController::class, 'updateApplicationStatus']);
            
            // Tasks & Submissions
            Route::get('{id}/tasks', [\App\Http\Controllers\Api\Admin\InternshipTaskController::class, 'index']);
            Route::post('tasks', [\App\Http\Controllers\Api\Admin\InternshipTaskController::class, 'store']);
            Route::put('tasks/{id}', [\App\Http\Controllers\Api\Admin\InternshipTaskController::class, 'update']);
            Route::delete('tasks/{id}', [\App\Http\Controllers\Api\Admin\InternshipTaskController::class, 'destroy']);
            Route::put('submissions/{id}/grade', [\App\Http\Controllers\Api\Admin\InternshipTaskController::class, 'gradeSubmission']);
        });
        Route::apiResource('internships', \App\Http\Controllers\Api\Admin\InternshipController::class);
        
        // ----- Courses -----
        Route::prefix('courses')->group(function () {
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'bulkDelete']);
            Route::post('bulk-status', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'bulkStatus']);
            Route::post('{id}/duplicate', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'duplicate']);
            Route::post('{id}/toggle-archive', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'toggleArchive']);
            Route::post('{id}/update-status', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'updateStatus']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'export']);
        });
        Route::apiResource('courses', \App\Http\Controllers\Api\Admin\AdminCourseController::class);

        // ----- Course Categories -----
        Route::prefix('course-categories')->group(function () {
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\AdminCourseCategoryController::class, 'bulkDelete']);
            Route::post('bulk-status', [\App\Http\Controllers\Api\Admin\AdminCourseCategoryController::class, 'bulkStatus']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminCourseCategoryController::class, 'export']);
        });
        Route::apiResource('course-categories', \App\Http\Controllers\Api\Admin\AdminCourseCategoryController::class);

        // ----- Course Subjects -----
        Route::put('course-subjects/{id}/status', [\App\Http\Controllers\Api\Admin\AdminCourseSubjectController::class, 'updateStatus']);

        // =========================================================
        // ENTERPRISE MODULES: BLOGS, COMMUNICATION, BACKUPS
        // =========================================================

        // ----- Blog Management -----
        Route::get('blogs/dashboard-metrics', [\App\Http\Controllers\Api\Admin\AdminBlogController::class, 'dashboardMetrics']);
        Route::post('blogs/{id}/action', [\App\Http\Controllers\Api\Admin\AdminBlogController::class, 'action']);
        Route::apiResource('blogs', \App\Http\Controllers\Api\Admin\AdminBlogController::class);
        Route::apiResource('blog-categories', \App\Http\Controllers\Api\Admin\AdminBlogCategoryController::class);

        // ----- Communication Center -----
        Route::prefix('communication')->group(function () {
            Route::get('inbox', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'inbox']);
            Route::get('threads/{id}', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'showThread']);
            Route::delete('threads/{id}', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'deleteThread']);
            Route::post('messages', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'sendMessage']);
            Route::get('broadcasts', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'broadcasts']);
            Route::post('broadcasts', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'sendBroadcast']);
            Route::delete('broadcasts/{id}', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'deleteBroadcast']);
            
            // Announcements
            Route::get('announcements', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'getAnnouncements']);
            Route::post('announcements', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'storeAnnouncement']);
            Route::put('announcements/{id}', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'updateAnnouncement']);
            Route::delete('announcements/{id}', [\App\Http\Controllers\Api\Admin\AdminCommunicationController::class, 'deleteAnnouncement']);
        });

        // ----- Backup Manager -----
        Route::prefix('backups')->group(function () {
            Route::get('settings', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'getSettings']);
            Route::put('settings', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'updateSettings']);
            Route::post('generate', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'generate']);
            Route::get('{id}/download', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'download']);
            Route::post('{id}/restore', [\App\Http\Controllers\Api\Admin\AdminBackupController::class, 'restore']);
        });
        Route::apiResource('backups', \App\Http\Controllers\Api\Admin\AdminBackupController::class)->only(['index', 'destroy']);

        Route::apiResource('course-subjects', \App\Http\Controllers\Api\Admin\AdminCourseSubjectController::class);

        // ----- Course Levels -----
        Route::prefix('course-levels')->group(function () {
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\AdminCourseLevelController::class, 'bulkDelete']);
            Route::post('bulk-status', [\App\Http\Controllers\Api\Admin\AdminCourseLevelController::class, 'bulkStatus']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminCourseLevelController::class, 'export']);
        });
        Route::apiResource('course-levels', \App\Http\Controllers\Api\Admin\AdminCourseLevelController::class);

        // ----- Generic Uploads -----
        Route::post('upload', [\App\Http\Controllers\Api\Admin\UploadController::class, 'upload']);

        // ----- Course Settings -----
        Route::get('course-settings', [\App\Http\Controllers\Api\Admin\AdminCourseSettingController::class, 'index']);
        Route::put('course-settings', [\App\Http\Controllers\Api\Admin\AdminCourseSettingController::class, 'update']);
        
        // ----- Courses -----
        Route::prefix('courses')->group(function () {
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'bulkDelete']);
            Route::post('bulk-status', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'bulkStatus']);
            Route::post('{id}/duplicate', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'duplicate']);
            Route::put('{id}/status', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'updateStatus']);
            Route::put('{id}/archive', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'toggleArchive']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminCourseController::class, 'export']);
        });
        Route::apiResource('courses', \App\Http\Controllers\Api\Admin\AdminCourseController::class);
        
        // ----- Course Curriculum -----
        Route::prefix('courses/{course_id}/curriculum')->group(function () {
            Route::get('', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'getCurriculum']);
            Route::post('modules', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'storeModule']);
        });
        Route::prefix('curriculum')->group(function () {
            Route::put('modules/reorder', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'reorderModules']);
            Route::put('modules/{id}', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'updateModule']);
            Route::delete('modules/{id}', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'destroyModule']);
            
            Route::post('modules/{module_id}/lessons', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'storeLesson']);
            Route::put('lessons/reorder', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'reorderLessons']);
            Route::put('lessons/{id}', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'updateLesson']);
            Route::delete('lessons/{id}', [\App\Http\Controllers\Api\Admin\AdminCourseCurriculumController::class, 'destroyLesson']);
        });

        // ----- Quiz Builder (MCQ) -----
        Route::prefix('quiz')->group(function () {
            Route::get('lessons/{lesson_id}', [\App\Http\Controllers\Api\Admin\AdminQuizController::class, 'show']);
            Route::post('lessons/{lesson_id}', [\App\Http\Controllers\Api\Admin\AdminQuizController::class, 'upsert']);
            Route::delete('lessons/{lesson_id}', [\App\Http\Controllers\Api\Admin\AdminQuizController::class, 'destroy']);
        });

        // ----- MCQ Results (read-only analytics) -----
        Route::get('mcq/results', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'results']);
        Route::get('mcq/stats', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'stats']);
        Route::get('mcq/leaderboard', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'leaderboard']);
        Route::get('mcq/courses', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'courses']);
        Route::get('mcq/export', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'export']);

        
        // ----- Certificates -----
        Route::prefix('certificates')->group(function () {
            Route::get('stats', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'stats']);
            Route::put('{id}/status', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'updateStatus']);
            
            Route::apiResource('templates', \App\Http\Controllers\Api\Admin\CertificateTemplateController::class);
            Route::apiResource('fonts', \App\Http\Controllers\Api\Admin\CertificateFontController::class);
            
            Route::get('settings', [\App\Http\Controllers\Api\Admin\CertificateSettingController::class, 'show']);
            Route::put('settings', [\App\Http\Controllers\Api\Admin\CertificateSettingController::class, 'update']);
        });
        Route::apiResource('certificates', \App\Http\Controllers\Api\Admin\CertificateController::class);

        // ----- Course Q&A -----
        Route::prefix('course-qa')->group(function () {
            Route::get('stats', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'stats']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'export']);
            Route::post('bulk-delete', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'bulkDelete']);
            Route::post('{id}/reply', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'reply']);
            Route::put('{id}/status', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'updateStatus']);
            Route::put('{id}/pin', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'togglePin']);
            Route::put('{id}/spam', [\App\Http\Controllers\Api\Admin\AdminCourseQAController::class, 'markSpam']);
        });
        Route::apiResource('course-qa', \App\Http\Controllers\Api\Admin\AdminCourseQAController::class);
        
        // ----- Virtual Classes -----
        Route::prefix('virtual-classes')->group(function () {
            Route::get('stats', [\App\Http\Controllers\Api\Admin\VirtualClassController::class, 'stats']);
            Route::get('export', [\App\Http\Controllers\Api\Admin\VirtualClassController::class, 'export']);
            Route::put('{id}/status', [\App\Http\Controllers\Api\Admin\VirtualClassController::class, 'updateStatus']);
        });
        Route::apiResource('virtual-classes', \App\Http\Controllers\Api\Admin\VirtualClassController::class);

        // Phase 3: Media Manager & Enrollments
        Route::get('media/folders', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'getFolders']);
        Route::post('media/folders', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'createFolder']);
        Route::delete('media/folders/{id}', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'deleteFolder']);
        Route::get('media/files', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'getFiles']);
        Route::get('media/trash', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'getTrash']);
        Route::post('media/files/upload', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'uploadFile']);
        Route::put('media/files/{id}', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'updateFile']);
        Route::delete('media/files/{id}', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'deleteFile']);
        Route::post('media/files/{id}/restore', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'restoreFile']);
        Route::delete('media/files/{id}/force', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'forceDeleteFile']);
        Route::get('media/statistics', [\App\Http\Controllers\Api\Admin\AdminMediaController::class, 'getStatistics']);

        Route::get('enrollments/export', [\App\Http\Controllers\Api\Admin\AdminCourseEnrollmentController::class, 'export']);
        Route::put('enrollments/{id}/status', [\App\Http\Controllers\Api\Admin\AdminCourseEnrollmentController::class, 'updateStatus']);
        Route::apiResource('enrollments', \App\Http\Controllers\Api\Admin\AdminCourseEnrollmentController::class);

        // Phase 4: Jobs (Routes moved to Enterprise ATS block above)

        Route::get('certificates', [\App\Http\Controllers\Api\Admin\AdminCertificateController::class, 'index']);
        Route::post('certificates', [\App\Http\Controllers\Api\Admin\AdminCertificateController::class, 'store']);

        // Phase 5: Dashboard Analytics
        Route::get('dashboard/summary', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'summary']);
        Route::get('dashboard/charts', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'charts']);
        Route::get('dashboard/top/courses', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'topCourses']);
        Route::get('dashboard/recent/enrollments', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'recentEnrollments']);
        Route::get('dashboard/feed', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'feed']);

        Route::get('analytics/summary', [\App\Http\Controllers\Api\Admin\AdminAnalyticsController::class, 'summary']);
        Route::get('analytics/leaderboards', [\App\Http\Controllers\Api\Admin\AdminAnalyticsController::class, 'leaderboards']);
        Route::get('analytics/tab-stats', [\App\Http\Controllers\Api\Admin\AdminAnalyticsController::class, 'tabStats']);
        Route::get('analytics/chart-data', [\App\Http\Controllers\Api\Admin\AdminAnalyticsController::class, 'chartData']);
        Route::get('analytics/recent-activity', [\App\Http\Controllers\Api\Admin\AdminAnalyticsController::class, 'recentActivity']);

        // MCQ Results
        Route::get('mcq/results', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'results']);
        Route::get('mcq/stats', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'stats']);
        Route::get('mcq/leaderboard', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'leaderboard']);
        Route::get('mcq/courses', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'courses']);
        Route::get('mcq/export', [\App\Http\Controllers\Api\Admin\AdminMCQController::class, 'export']);

        // ----- Zoom Settings -----
        Route::get('zoom-settings', [\App\Http\Controllers\Api\Admin\ZoomSettingsController::class, 'show']);
        Route::put('zoom-settings', [\App\Http\Controllers\Api\Admin\ZoomSettingsController::class, 'update']);
        Route::post('zoom-settings/test-connection', [\App\Http\Controllers\Api\Admin\ZoomSettingsController::class, 'testConnection']);

        // ----- Media Manager APIs (COMMENTED OUT TO PREVENT CONFLICT WITH PHASE 3 AdminMediaController) -----
        /*
        Route::prefix('media')->group(function () {
            // Statistics
            Route::get('statistics', [\App\Http\Controllers\Api\Admin\MediaActionController::class, 'statistics']);
            
            // Trash
            Route::get('trash', [\App\Http\Controllers\Api\Admin\MediaActionController::class, 'trash']);
            Route::post('trash/files/{id}/restore', [\App\Http\Controllers\Api\Admin\MediaActionController::class, 'restoreFile']);
            Route::post('trash/folders/{id}/restore', [\App\Http\Controllers\Api\Admin\MediaActionController::class, 'restoreFolder']);
            Route::delete('trash/files/{id}/force', [\App\Http\Controllers\Api\Admin\MediaActionController::class, 'forceDeleteFile']);
            
            // Files
            Route::get('files', [\App\Http\Controllers\Api\Admin\MediaFileController::class, 'index']);
            Route::post('files/upload', [\App\Http\Controllers\Api\Admin\MediaFileController::class, 'upload']);
            Route::post('files/upload/chunk', [\App\Http\Controllers\Api\Admin\MediaFileController::class, 'uploadChunk']);
            Route::post('files/{file}/webp', [\App\Http\Controllers\Api\Admin\MediaFileController::class, 'convertToWebp']);
            Route::put('files/{file}', [\App\Http\Controllers\Api\Admin\MediaFileController::class, 'update']);
            Route::delete('files/{file}', [\App\Http\Controllers\Api\Admin\MediaFileController::class, 'destroy']);
            
            // Folders
            Route::get('folders', [\App\Http\Controllers\Api\Admin\MediaFolderController::class, 'index']);
            Route::post('folders', [\App\Http\Controllers\Api\Admin\MediaFolderController::class, 'store']);
            Route::put('folders/{folder}', [\App\Http\Controllers\Api\Admin\MediaFolderController::class, 'update']);
            Route::delete('folders/{folder}', [\App\Http\Controllers\Api\Admin\MediaFolderController::class, 'destroy']);
            Route::post('folders/{folder}/move', [\App\Http\Controllers\Api\Admin\MediaFolderController::class, 'move']);
        });
        */
    });

    // EdTech API
    Route::apiResource('course-categories', CourseCategoryController::class);
    
    // Require verified profile to modify courses/modules/lessons
    Route::middleware('verified.profile')->group(function () {
        Route::apiResource('courses', CourseController::class)->except(['index', 'show']);
        Route::apiResource('modules', ModuleController::class)->except(['index', 'show']);
        Route::apiResource('lessons', LessonController::class)->except(['index', 'show']);
    });
    // Public course viewing
    Route::apiResource('courses', CourseController::class)->only(['index', 'show']);
    Route::apiResource('modules', ModuleController::class)->only(['index', 'show']);
    Route::apiResource('lessons', LessonController::class)->only(['index', 'show']);
    
    // Hiring API
    Route::middleware('verified.profile')->group(function () {
        Route::apiResource('jobs', JobController::class)->except(['index', 'show']);
        Route::apiResource('internships', InternshipController::class)->except(['index', 'show']);
    });
    // Public viewing and applications
    Route::apiResource('jobs', JobController::class)->only(['index', 'show']);
    Route::apiResource('internships', InternshipController::class)->only(['index', 'show']);
    Route::apiResource('job-applications', JobApplicationController::class);
    Route::apiResource('internship-applications', InternshipApplicationController::class);
    
    // E-Commerce & Checkout API
    Route::apiResource('wishlist', WishlistController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('cart', CartController::class)->only(['index', 'store', 'destroy']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    
    // Core Course Checkout API (Gateway-Agnostic)
    Route::post('checkout/create-order', [\App\Http\Controllers\Api\CheckoutController::class, 'createOrder']);
    Route::post('checkout/verify', [\App\Http\Controllers\Api\CheckoutController::class, 'verifyPayment']);
    
    // File Upload API
    Route::post('upload', [UploadController::class, 'store']);
});

Route::get('/qa-routes-map', function () {
    $scanData = [
        'api_endpoints' => [],
        'backend_routes' => []
    ];
    
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    foreach ($routes as $route) {
        $uri = $route->uri();
        $methods = implode('|', $route->methods());
        
        if (str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum') || str_starts_with($uri, 'broadcasting')) continue;
        
        $routeInfo = [
            'uri' => $uri,
            'methods' => $methods,
        ];
        
        if (str_starts_with($uri, 'api/')) {
            $scanData['api_endpoints'][] = $routeInfo;
        } else {
            $scanData['backend_routes'][] = $routeInfo;
        }
    }
    return response()->json($scanData);
});
