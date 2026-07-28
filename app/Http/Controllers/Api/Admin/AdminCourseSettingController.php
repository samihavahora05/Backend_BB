<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCourseSettingController extends Controller
{
    private function logActivity($action, $description)
    {
        ActivityLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function index()
    {
        $settings = CourseSetting::firstOrCreate(
            ['id' => 1],
            [
                'course_approval_required' => false,
                'hide_reviews' => false,
                'expiry_email_days' => 7,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if ($user && !$user->hasRole('super_admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only Super Admin can update course settings.'
            ], 403);
        }

        $validated = $request->validate([
            'course_approval_required' => 'required|boolean',
            'hide_reviews' => 'required|boolean',
            'expiry_email_days' => 'nullable|integer|min:0',
        ]);

        $settings = CourseSetting::firstOrCreate(['id' => 1]);
        
        $settings->update([
            'course_approval_required' => $validated['course_approval_required'],
            'hide_reviews' => $validated['hide_reviews'],
            'expiry_email_days' => $validated['expiry_email_days'],
            'updated_by' => Auth::id() ?? 1,
        ]);

        $this->logActivity('update_course_settings', 'Global course settings were updated.');

        return response()->json([
            'success' => true,
            'message' => 'Course settings updated successfully',
            'data' => $settings
        ]);
    }
}
