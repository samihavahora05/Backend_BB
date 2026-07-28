<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateSetting;
use Illuminate\Http\Request;

class CertificateSettingController extends Controller
{
    public function show()
    {
        $setting = CertificateSetting::first();
        if (!$setting) {
            $setting = CertificateSetting::create([]);
        }
        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function update(Request $request)
    {
        $setting = CertificateSetting::first();
        if (!$setting) {
            $setting = CertificateSetting::create([]);
        }

        $setting->update($request->all());

        return response()->json(['success' => true, 'data' => $setting]);
    }
}
