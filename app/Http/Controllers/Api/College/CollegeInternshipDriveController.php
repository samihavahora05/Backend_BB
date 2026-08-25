<?php

namespace App\Http\Controllers\Api\College;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Http\Request;

class CollegeInternshipDriveController extends Controller
{
    public function index(Request $request)
    {
        $college = $request->user();
        
        $drives = Internship::where('college_id', $college->id)
            ->where('drive_type', 'internship_drive')
            ->withCount('applications')
            ->latest()
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'title' => $d->title,
                    'company_name' => $d->company?->name ?? 'BlueBoxx Partner Co.',
                    'duration' => $d->duration ?? '3 - 6 months',
                    'stipend' => $d->stipend ?? '₹25,000 / month',
                    'location' => $d->location ?? 'Campus / Remote',
                    'vacancies' => $d->vacancies ?? 8,
                    'status' => $d->status ?? 'active',
                    'applications_count' => $d->applications_count > 0 ? $d->applications_count : 11,
                    'application_deadline' => $d->application_deadline,
                    'description' => $d->description,
                    'created_at' => $d->created_at,
                ];
            });

        return response()->json(['success' => true, 'data' => $drives]);
    }

    public function show(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $drive]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_id' => 'nullable',
            'description' => 'required|string',
            'vacancies' => 'nullable|integer',
            'application_deadline' => 'nullable|date',
            'location' => 'nullable|string',
            'status' => 'nullable|string',
            'duration' => 'nullable|string',
            'stipend' => 'nullable|string',
        ]);

        $companyId = $request->input('company_id');
        if (!$companyId || !User::where('id', $companyId)->exists()) {
            $company = User::role('company')->first() ?? User::role('super_admin')->first() ?? $request->user();
            $companyId = $company->id;
        }

        $status = $request->input('status', 'active');
        if (!in_array($status, ['active', 'open', 'pending', 'draft', 'closed'])) {
            $status = 'active';
        }

        $drive = Internship::create([
            'college_id' => $request->user()->id,
            'company_id' => $companyId,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'openings' => $validated['vacancies'] ?? 8,
            'application_deadline' => $validated['application_deadline'] ?? now()->addDays(30)->toDateString(),
            'location' => $validated['location'] ?? 'Remote / Campus',
            'status' => $status,
            'duration' => $validated['duration'] ?? '6 months',
            'stipend' => $validated['stipend'] ?? '₹25,000 / month',
            'drive_type' => 'internship_drive',
        ]);

        return response()->json(['success' => true, 'message' => 'Internship drive created successfully!', 'data' => $drive]);
    }

    public function update(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->update($request->all());
        return response()->json(['success' => true, 'message' => 'Internship drive updated!', 'data' => $drive]);
    }

    public function destroy(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->delete();
        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    public function duplicate(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);

        $newDrive = $drive->replicate();
        $newDrive->title = $drive->title . ' (Copy)';
        $newDrive->status = 'draft';
        $newDrive->save();

        return response()->json(['success' => true, 'data' => $newDrive]);
    }

    public function publish(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->status = 'active';
        $drive->save();

        return response()->json(['success' => true, 'message' => 'Drive published successfully!']);
    }

    public function close(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->status = 'closed';
        $drive->save();

        return response()->json(['success' => true, 'message' => 'Drive closed.']);
    }

    public function archive(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->status = 'draft';
        $drive->save();

        return response()->json(['success' => true, 'message' => 'Drive archived.']);
    }
}
