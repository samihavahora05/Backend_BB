<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use Illuminate\Http\Request;

class AdminInternshipDriveController extends Controller
{
    public function index(Request $request)
    {
        $drives = Internship::with(['college', 'company'])
            ->where('drive_type', 'internship_drive')
            ->latest()
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $drives
        ]);
    }

    public function approve(Request $request, $id)
    {
        $drive = Internship::where('drive_type', 'internship_drive')->findOrFail($id);
        $drive->status = 'open';
        $drive->save();

        // Optionally dispatch notification to college
        // Notification::send($drive->college, new DriveApprovedNotification($drive));

        return response()->json([
            'success' => true,
            'message' => 'Internship drive approved successfully.',
            'data' => $drive
        ]);
    }

    public function reject(Request $request, $id)
    {
        $drive = Internship::where('drive_type', 'internship_drive')->findOrFail($id);
        $drive->status = 'rejected';
        $drive->save();

        // Optionally dispatch notification to college

        return response()->json([
            'success' => true,
            'message' => 'Internship drive rejected successfully.',
            'data' => $drive
        ]);
    }
}
