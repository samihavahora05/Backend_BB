<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSystemSettingController extends Controller
{
    /**
     * Get all settings grouped by their group name
     */
    public function index()
    {
        $settings = Cache::rememberForever('system_settings_all', function () {
            return SystemSetting::all()->groupBy('group');
        });

        // Convert grouped collection to array format easy for frontend
        $formatted = [];
        foreach ($settings as $group => $items) {
            $formatted[$group] = $items->pluck('value', 'key')->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => collect($formatted)->isEmpty() ? new \stdClass() : $formatted
        ]);
    }

    /**
     * Update or create multiple settings at once
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'group' => 'required|string',
        ]);

        $group = $request->group;
        $settings = $request->settings;

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // Clear cache
        Cache::forget('system_settings_all');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
}
