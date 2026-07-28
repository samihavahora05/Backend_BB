<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class AdminCRMController extends Controller
{
    /**
     * Get CRM Dashboard Analytics
     */
    public function dashboard(Request $request)
    {
        $today = now()->startOfDay();

        $stats = [
            'new_leads' => Lead::where('status', 'new')->count(),
            'todays_leads' => Lead::where('created_at', '>=', $today)->count(),
            'pending_follow_ups' => Lead::whereIn('status', ['new', 'contacted', 'in_progress'])->count(),
            'converted_leads' => Lead::where('status', 'converted')->count(),
            'course_inquiries' => Lead::where('type', 'Course Inquiries')->count(),
            'mentor_inquiries' => Lead::where('type', 'Mentor Inquiries')->count(),
            'corporate_training' => Lead::where('type', 'Corporate Training')->count(),
            'total_leads' => Lead::count(),
        ];

        $unreadCounts = Lead::select('type', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->where('status', 'new')
            ->groupBy('type')
            ->pluck('total', 'type');

        return response()->json([
            'success' => true,
            'data' => $stats,
            'unread_counts' => $unreadCounts
        ]);
    }
}
