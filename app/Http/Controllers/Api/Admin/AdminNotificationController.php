<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Lead;
use App\Models\Job;
use App\Models\Internship;
use App\Models\Course;

class AdminNotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = $user->notifications()->paginate(15);
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Get live badge counts for the sidebar using real MySQL data
     */
    public function badges(Request $request)
    {
        // Compute unread/pending badges for different modules
        $pendingApprovals = User::where('status', 'pending')->count();
        $crmLeads = Lead::where('status', 'new')->count();
        $jobs = Job::where('status', 'pending')->count();
        $internships = Internship::where('status', 'pending')->count();
        
        $crmLeadsBreakdown = Lead::where('status', 'new')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        
        $bellCount = tap($request->user(), function($user) {
            return $user ? $user->unreadNotifications()->count() : 0;
        });

        return response()->json([
            'success' => true,
            'badges' => array_merge($crmLeadsBreakdown, [
                'Pending Approvals' => $pendingApprovals,
                'Leads & CRM' => $crmLeads,
                'All Jobs' => $jobs,
                'All Internships' => $internships,
                'bell' => $bellCount,
            ])
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
