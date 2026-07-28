<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobApplication;
use App\Models\JobInterview;
use App\Models\JobOffer;
use App\Models\JobSeekerProfile;
use Illuminate\Support\Facades\Hash;

class JobseekerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $userId = $user->id;

        $applications = JobApplication::with('job.company.companyProfile')
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();

        $recentApps = $applications->map(function ($app) {
            $companyName = $app->job?->company?->companyProfile?->company_name ?? $app->job?->company?->name ?? 'Company';
            return [
                'id'      => $app->id,
                'company' => $companyName,
                'role'    => $app->job?->title         ?? 'Job Role',
                'status'  => $app->status,
                'time'    => $app->created_at->diffForHumans(),
                'type'    => $app->job?->employment_type ?? 'Full-Time',
            ];
        });

        // Profile completion
        $profile = JobSeekerProfile::where('user_id', $userId)->first();
        $completion = 0;
        if ($profile) {
            $fields = ['headline', 'about_me', 'preferred_location', 'skills', 'linkedin', 'github', 'resume_path', 'expected_salary', 'experience'];
            $filled = 0;
            foreach ($fields as $field) {
                $val = $profile->$field;
                if (!empty($val) && $val !== null && $val !== '[]') $filled++;
            }
            // name/email are always filled (+2)
            $completion = (int) round((($filled + 2) / (count($fields) + 2)) * 100);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'stats' => [
                    'jobs_applied' => JobApplication::where('user_id', $userId)->count(),
                    'saved_jobs'   => \App\Models\JobBookmark::where('user_id', $userId)->count(),
                    'interviews'   => JobApplication::where('user_id', $userId)->where('status', 'interview')->count(),
                    'offers'       => JobApplication::where('user_id', $userId)->whereIn('status', ['offer', 'hired', 'offered'])->count(),
                ],
                'profile_completion' => $completion,
                'recent_applications' => $recentApps,
            ]
        ]);
    }

    public function applications(Request $request)
    {
        $userId = $request->user()->id;

        $query = JobApplication::with('job.company.companyProfile')
            ->where('user_id', $userId);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('job', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company.companyProfile', function ($q2) use ($search) {
                      $q2->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'All Status') {
            $query->where('status', strtolower($request->status));
        }

        $applications = $query->latest()->get();

        $apps = $applications->map(function ($app) {
            $companyName = $app->job?->company?->companyProfile?->company_name ?? $app->job?->company?->name ?? 'Company';
            $companyLogo = $app->job?->company?->companyProfile?->logo ?? null;

            return [
                'id'       => $app->id,
                'role'     => $app->job?->title         ?? 'Job Role',
                'company'  => $companyName,
                'location' => $app->job?->location      ?? 'Remote',
                'status'   => $app->status,
                'date'     => $app->created_at->format('M d, Y'),
                'logo'     => $companyLogo ? asset('storage/' . $companyLogo) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $apps,
        ]);
    }

    public function withdrawApplication(Request $request, $id)
    {
        $userId = $request->user()->id;

        $application = JobApplication::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$application) {
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        if (!in_array($application->status, ['applied'])) {
            return response()->json(['success' => false, 'message' => 'Cannot withdraw application at this stage.'], 422);
        }

        $application->delete();

        return response()->json(['success' => true, 'message' => 'Application withdrawn.']);
    }

    public function interviews(Request $request)
    {
        $userId = $request->user()->id;

        $interviews = JobInterview::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['application.job.company.companyProfile'])
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->map(function ($interview) {
                $job = $interview->application->job ?? null;
                return [
                    'id'           => $interview->id,
                    'job_title'    => $job->title        ?? 'Job Role',
                    'company_name' => $job->company->companyProfile->company_name ?? $job->company->name ?? 'Company',
                    'company_logo' => ($job->company->companyProfile->logo ?? null) ? asset('storage/' . $job->company->companyProfile->logo) : null,
                    'scheduled_at' => $interview->scheduled_at?->format('M d, Y'),
                    'time'         => $interview->scheduled_at?->format('h:i A'),
                    'type'         => $interview->type        ?? 'Video Call',
                    'location'     => $interview->location    ?? null,
                    'meeting_link' => $interview->meeting_link ?? null,
                    'notes'        => $interview->notes        ?? null,
                    'status'       => $interview->status       ?? 'scheduled',
                    'application_id' => $interview->application_id,
                ];
            });

        return response()->json(['success' => true, 'data' => $interviews]);
    }

    public function offers(Request $request)
    {
        $userId = $request->user()->id;

        $offers = JobOffer::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['application.job.company.companyProfile'])
            ->latest()
            ->get()
            ->map(function ($offer) {
                $job = $offer->application->job ?? null;
                return [
                    'id'             => $offer->id,
                    'job_title'      => $job->title        ?? 'Job Role',
                    'company_name'   => $job->company->companyProfile->company_name ?? $job->company->name ?? 'Company',
                    'company_logo'   => ($job->company->companyProfile->logo ?? null) ? asset('storage/' . $job->company->companyProfile->logo) : null,
                    'salary'         => $offer->salary_offered   ?? null,
                    'salary_type'    => $offer->salary_type      ?? 'Per Year',
                    'joining_date'   => $offer->joining_date     ?? null,
                    'valid_until'    => $offer->valid_until?->format('M d, Y'),
                    'status'         => $offer->status           ?? 'pending',
                    'message'        => $offer->message          ?? null,
                    'application_id' => $offer->application_id,
                ];
            });

        return response()->json(['success' => true, 'data' => $offers]);
    }

    public function acceptOffer(Request $request, $id)
    {
        $userId = $request->user()->id;

        $offer = JobOffer::whereHas('application', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->findOrFail($id);

        $offer->update(['status' => 'accepted']);
        // Update application status too
        $offer->application()->update(['status' => 'hired']);

        return response()->json(['success' => true, 'message' => 'Offer accepted! Congratulations!']);
    }

    public function rejectOffer(Request $request, $id)
    {
        $userId = $request->user()->id;

        $offer = JobOffer::whereHas('application', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->findOrFail($id);

        $offer->update(['status' => 'rejected']);
        $offer->application()->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Offer declined.']);
    }

    public function settings(Request $request)
    {
        $user    = $request->user();
        $profile = JobSeekerProfile::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $profile->phone,
                'avatar'     => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'headline'   => $profile->headline,
                'preferred_location' => $profile->preferred_location,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:20',
            'headline'   => 'nullable|string|max:200',
            'preferred_location' => 'nullable|string|max:200',
        ]);

        $user->update($request->only(['first_name', 'last_name', 'email']));

        $profile = JobSeekerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($request->only(['phone', 'headline', 'preferred_location']));

        return response()->json(['success' => true, 'message' => 'Settings updated successfully.']);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated.',
            'avatar'  => asset('storage/' . $path),
        ]);
    }
}
