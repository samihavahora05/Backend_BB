<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestRegistration;
use App\Models\ContestSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicContestController extends Controller
{
    /**
     * List active and upcoming contests
     * GET /api/public/contests
     */
    public function index(Request $request)
    {
        $query = Contest::whereIn('status', ['Upcoming', 'Active']);

        if ($s = $request->query('search')) {
            $query->where('title', 'like', "%{$s}%");
        }

        $perPage = min((int)$request->query('per_page', 12), 50);
        $contests = $query->orderBy('start_date', 'asc')->paginate($perPage);

        $data = $contests->through(fn($c) => [
            'id'          => $c->id,
            'title'       => $c->title,
            'description' => \Str::limit($c->description, 100),
            'start_date'  => $c->start_date?->format('Y-m-d H:i'),
            'end_date'    => $c->end_date?->format('Y-m-d H:i'),
            'status'      => $c->status,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Contest details
     * GET /api/public/contests/{id}
     */
    public function show(Request $request, $id)
    {
        $contest = Contest::findOrFail($id);
        
        $hasRegistered = false;
        if ($request->user()) {
            $hasRegistered = ContestRegistration::where('contest_id', $contest->id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $contest->id,
                'title'          => $contest->title,
                'description'    => $contest->description,
                'start_date'     => $contest->start_date?->format('Y-m-d H:i'),
                'end_date'       => $contest->end_date?->format('Y-m-d H:i'),
                'status'         => $contest->status,
                'has_registered' => $hasRegistered,
            ]
        ]);
    }

    /**
     * Register for a contest
     * POST /api/public/contests/{id}/register
     */
    public function register(Request $request, $id)
    {
        $contest = Contest::findOrFail($id);

        if ($contest->status !== 'Upcoming' && $contest->status !== 'Active') {
            return response()->json(['success' => false, 'message' => 'Contest is not open for registration.'], 400);
        }

        $alreadyRegistered = ContestRegistration::where('contest_id', $contest->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json(['success' => false, 'message' => 'You are already registered for this contest.'], 400);
        }

        $data = $request->validate([
            'team_name' => 'nullable|string|max:255',
        ]);

        $registration = ContestRegistration::create([
            'contest_id' => $contest->id,
            'user_id'    => $request->user()->id,
            'team_name'  => $data['team_name'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registered successfully!',
            'data'    => ['registration_id' => $registration->id]
        ]);
    }

    /**
     * Submit a project for a contest
     * POST /api/public/contests/{id}/submit
     */
    public function submitProject(Request $request, $id)
    {
        $contest = Contest::findOrFail($id);

        if ($contest->status !== 'Active') {
            return response()->json(['success' => false, 'message' => 'Contest is not active.'], 400);
        }

        $registration = ContestRegistration::where('contest_id', $contest->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'You must register first before submitting.'], 403);
        }

        $data = $request->validate([
            'project_title' => 'required|string|max:255',
            'repo_url'      => 'required|url|max:255',
            'demo_url'      => 'nullable|url|max:255',
        ]);

        $submission = ContestSubmission::updateOrCreate(
            ['registration_id' => $registration->id],
            [
                'project_title' => $data['project_title'],
                'repo_url'      => $data['repo_url'],
                'demo_url'      => $data['demo_url'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Project submitted successfully!',
            'data'    => ['submission_id' => $submission->id]
        ]);
    }
}
