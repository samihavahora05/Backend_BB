<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VirtualClass;
use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VirtualClassController extends Controller
{
    /**
     * List all virtual classes with server-side search, filter, and pagination.
     */
    public function index(Request $request)
    {
        $query = VirtualClass::with(['instructor', 'category', 'course'])
            ->when($request->search, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('language', 'like', "%{$search}%");
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->platform, fn($q, $p) => $q->where('platform', $p))
            ->when($request->category_id, fn($q, $c) => $q->where('category_id', $c));

        $perPage = $request->get('per_page', 15);
        $classes = $query->latest('start_datetime')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $classes->items(),
            'meta'    => [
                'current_page' => $classes->currentPage(),
                'last_page'    => $classes->lastPage(),
                'total'        => $classes->total(),
                'per_page'     => $classes->perPage(),
                'from'         => $classes->firstItem(),
                'to'           => $classes->lastItem(),
            ],
        ]);
    }

    /**
     * Store a new virtual class.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'course_id'        => 'nullable|exists:courses,id',
            'instructor_id'    => 'nullable|exists:users,id',
            'category_id'      => 'nullable|exists:course_categories,id',
            'language'         => 'required|string|max:50',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_students'     => 'required|integer|min:1',
            'start_datetime'   => 'required|date|after:now',
            'status'           => 'in:scheduled,live,completed,cancelled',
            'platform'         => 'in:zoom,google_meet,microsoft_teams,custom',
            'is_free'          => 'boolean',
            'price'            => 'nullable|numeric|min:0',
            'join_url'         => 'nullable|url',
            'meeting_id'       => 'nullable|string',
            'meeting_password'  => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status']     = $validated['status'] ?? 'scheduled';
        $validated['platform']   = $validated['platform'] ?? 'zoom';

        $class = VirtualClass::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Virtual class created successfully',
            'data'    => $class->load(['instructor', 'category', 'course']),
        ], 201);
    }

    /**
     * Show a specific virtual class.
     */
    public function show($id)
    {
        $class = VirtualClass::with([
            'instructor', 'category', 'course', 'enrollments.user', 'creator',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $class]);
    }

    /**
     * Update a virtual class.
     */
    public function update(Request $request, $id)
    {
        $class = VirtualClass::findOrFail($id);

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'course_id'        => 'nullable|exists:courses,id',
            'instructor_id'    => 'nullable|exists:users,id',
            'category_id'      => 'nullable|exists:course_categories,id',
            'language'         => 'sometimes|string|max:50',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'max_students'     => 'sometimes|integer|min:1',
            'start_datetime'   => 'sometimes|date',
            'status'           => 'sometimes|in:scheduled,live,completed,cancelled',
            'platform'         => 'sometimes|in:zoom,google_meet,microsoft_teams,custom',
            'is_free'          => 'sometimes|boolean',
            'price'            => 'nullable|numeric|min:0',
            'join_url'         => 'nullable|url',
            'meeting_id'       => 'nullable|string',
            'meeting_password'  => 'nullable|string',
            'recording_url'    => 'nullable|url',
        ]);

        $class->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Virtual class updated',
            'data'    => $class->fresh(['instructor', 'category', 'course']),
        ]);
    }

    /**
     * Delete a virtual class (soft delete).
     */
    public function destroy($id)
    {
        $class = VirtualClass::findOrFail($id);
        $class->delete();

        return response()->json(['success' => true, 'message' => 'Class deleted successfully']);
    }

    /**
     * Update just the status of a class.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:scheduled,live,completed,cancelled',
        ]);

        $class = VirtualClass::findOrFail($id);
        $class->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated', 'data' => $class]);
    }

    /**
     * Export virtual classes as Excel.
     */
    public function export(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\VirtualClassesExport($request->status),
            'virtual_classes_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Get summary stats for dashboard.
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'total'     => VirtualClass::count(),
                'scheduled' => VirtualClass::where('status', 'scheduled')->count(),
                'live'      => VirtualClass::where('status', 'live')->count(),
                'completed' => VirtualClass::where('status', 'completed')->count(),
                'cancelled' => VirtualClass::where('status', 'cancelled')->count(),
            ],
        ]);
    }
}
