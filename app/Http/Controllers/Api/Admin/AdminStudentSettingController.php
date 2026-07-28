<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentSetting;
use Illuminate\Http\Request;

class AdminStudentSettingController extends Controller
{
    public function index()
    {
        $this->authorize('manage students');
        $settings = StudentSetting::all()->pluck('setting_value', 'setting_key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request)
    {
        $this->authorize('manage students');
        $data = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($data['settings'] as $key => $value) {
            StudentSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
    }
}
