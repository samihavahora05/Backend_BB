<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class AdminPlacementDriveController extends Controller
{
    public function index(Request $request)
    {
        $drives = Job::with(['college', 'company'])
            ->where('drive_type', 'placement_drive')
            ->latest()
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $drives
        ]);
    }

    public function approve(Request $request, $id)
    {
        $drive = Job::where('drive_type', 'placement_drive')->findOrFail($id);
        $drive->status = 'open';
        $drive->save();

        // Optionally dispatch notification to college
        // Notification::send($drive->college, new DriveApprovedNotification($drive));

        return response()->json([
            'success' => true,
            'message' => 'Placement drive approved successfully.',
            'data' => $drive
        ]);
    }

    public function reject(Request $request, $id)
    {
        $drive = Job::where('drive_type', 'placement_drive')->findOrFail($id);
        $drive->status = 'rejected';
        $drive->save();

        // Optionally dispatch notification to college

        return response()->json([
            'success' => true,
            'message' => 'Placement drive rejected successfully.',
            'data' => $drive
        ]);
    }
}
