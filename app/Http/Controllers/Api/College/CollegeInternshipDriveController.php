<?php

namespace App\Http\Controllers\Api\College;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use Illuminate\Http\Request;

class CollegeInternshipDriveController extends Controller
{
    public function index(Request $request)
    {
        $drives = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->withCount('applications')
            ->latest()
            ->get();
        return response()->json(['success' => true, 'data' => $drives]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'vacancies' => 'nullable|integer',
            'application_deadline' => 'nullable|date',
            'location' => 'nullable|string',
            'status' => 'nullable|string',
            'duration' => 'nullable|string',
        ]);

        $insertData = $validated;
        
        $status = $request->input('status');
        if (!in_array($status, ['pending', 'draft'])) {
            $status = 'pending';
        }

        $drive = Internship::create(array_merge($insertData, [
            'college_id' => $request->user()->id,
            'drive_type' => 'internship_drive',
            'status' => $status
        ]));

        return response()->json(['success' => true, 'data' => $drive]);
    }

    public function update(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->update($request->all());
        return response()->json(['success' => true, 'data' => $drive]);
    }

    public function destroy(Request $request, $id)
    {
        $drive = Internship::where('college_id', $request->user()->id)
            ->where('drive_type', 'internship_drive')
            ->findOrFail($id);
            
        $drive->delete();
        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    // --- New Action Endpoints ---

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
            
        // College can only submit for approval, not directly open.
        $drive->status = 'pending';
        $drive->save();

        return response()->json(['success' => true, 'message' => 'Submitted for approval.']);
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
            
        $drive->status = 'archived';
        $drive->save();

        return response()->json(['success' => true, 'message' => 'Drive archived.']);
    }

    public function export(Request $request, $id)
    {
        // Mock export for now, returns a URL to download
        return response()->json(['success' => true, 'download_url' => '/exports/applications_' . $id . '.csv']);
    }
}
