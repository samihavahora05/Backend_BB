<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipApplication;
use Illuminate\Http\Request;

class PublicInternshipController extends Controller
{
    /**
     * Public internship listing with search and filters
     * GET /api/public/internships
     */
    public function index(Request $request)
    {
        $query = Internship::query()->whereIn('status', ['Active', 'active', 'Open', 'open', 'OPEN', 'Published', 'published']);

        if ($s = $request->query('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type); // Remote, On-site, Hybrid
        }
        if ($domain = $request->query('domain')) {
            $query->where('domain', 'like', "%{$domain}%");
        }
        if ($request->boolean('paid')) {
            $query->where('is_paid', true);
        }

        $perPage = min((int)$request->query('per_page', 12), 50);
        $internships = $query->latest()->paginate($perPage);

        $appliedInternshipIds = [];
        $user = auth('sanctum')->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        if ($user) {
            $appliedInternshipIds = \App\Models\InternshipApplication::where('user_id', $user->id)
                ->pluck('internship_id')
                ->toArray();
        }

        $data = $internships->through(fn($i) => [
            'id'           => $i->id,
            'title'        => $i->title,
            'company_name' => $i->company_name,
            'company_logo' => $i->company_logo ? asset('storage/' . $i->company_logo) : null,
            'location'     => $i->location,
            'type'         => $i->type,
            'duration'     => $i->duration,
            'domain'       => $i->domain ?? null,
            'is_paid'      => $i->is_paid,
            'stipend'      => $i->is_paid ? $i->stipend : null,
            'start_date'   => $i->start_date ?? null,
            'last_date'    => $i->last_date ?? null,
            'posted_at'    => $i->created_at->diffForHumans(),
            'has_applied'  => in_array($i->id, $appliedInternshipIds),
        ]);

        return response()->json([
            'success' => true,
            'debug_user_id' => $user ? $user->id : null,
            'data'    => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Public internship detail
     * GET /api/public/internships/{id}
     */
    public function show(Request $request, $id)
    {
        $internship = Internship::whereIn('status', ['Active', 'active', 'Open', 'open', 'OPEN', 'Published', 'published'])->findOrFail($id);

        $hasApplied = false;
        if ($request->user()) {
            $hasApplied = InternshipApplication::where('internship_id', $internship->id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data'    => array_merge($internship->toArray(), [
                'company_logo' => $internship->company_logo ? asset('storage/' . $internship->company_logo) : null,
                'has_applied'  => $hasApplied,
                'posted_at'    => $internship->created_at->diffForHumans(),
            ])
        ]);
    }

    /**
     * Apply for an internship (requires auth)
     * POST /api/public/internships/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        $internship = Internship::whereIn('status', ['Active', 'active', 'Open', 'open', 'OPEN', 'Published', 'published'])->findOrFail($id);

        $alreadyApplied = InternshipApplication::where('internship_id', $internship->id)
            ->where('user_id', $request->user()->id)
            ->exists();
        if ($alreadyApplied) {
            return response()->json(['success' => false, 'message' => 'You have already applied for this internship'], 422);
        }

        $data = $request->validate([
            'cover_letter' => 'nullable|string|max:5000',
            'resume'       => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'portfolio_url'=> 'nullable|url',
        ]);

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }

        $application = InternshipApplication::create([
            'internship_id' => $internship->id,
            'user_id'       => $request->user()->id,
            'status'        => 'applied',
            'cover_letter'  => $data['cover_letter'] ?? null,
            'resume_url'    => $resumePath,
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'applied_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Internship application submitted!',
            'data'    => ['application_id' => $application->id, 'status' => $application->status],
        ], 201);
    }

    /**
     * Get my internship applications
     * GET /api/public/internships/my-applications
     */
    public function myApplications(Request $request)
    {
        $applications = InternshipApplication::with(['internship'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $data = $applications->through(fn($a) => [
            'id'             => $a->id,
            'internship'     => $a->internship?->title,
            'company'        => $a->internship?->company_name,
            'status'         => $a->status,
            'applied_at'     => $a->applied_at?->format('M d, Y') ?? $a->created_at->format('M d, Y'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => ['current_page' => $data->currentPage(), 'total' => $data->total()],
        ]);
    }
}
