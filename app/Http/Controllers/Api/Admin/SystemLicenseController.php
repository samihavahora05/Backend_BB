<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLicense;
use Illuminate\Http\Request;

class SystemLicenseController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemLicense::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('license_key', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', strtolower($request->status));
        }

        $licenses = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $licenses]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|unique:system_licenses,license_key',
            'domain' => 'nullable|string',
            'email' => 'nullable|email',
            'expires_at' => 'nullable|date',
            'status' => 'required|in:active,suspended,expired'
        ]);

        $license = SystemLicense::create($request->all());

        return response()->json(['success' => true, 'data' => $license]);
    }

    public function show($id)
    {
        $license = SystemLicense::findOrFail($id);
        return response()->json(['success' => true, 'data' => $license]);
    }

    public function update(Request $request, $id)
    {
        $license = SystemLicense::findOrFail($id);

        $request->validate([
            'license_key' => 'required|string|unique:system_licenses,license_key,' . $id,
            'domain' => 'nullable|string',
            'email' => 'nullable|email',
            'expires_at' => 'nullable|date',
            'status' => 'required|in:active,suspended,expired'
        ]);

        $license->update($request->all());

        return response()->json(['success' => true, 'data' => $license]);
    }

    public function destroy($id)
    {
        $license = SystemLicense::findOrFail($id);
        $license->delete();

        return response()->json(['success' => true]);
    }

    public function action(Request $request, $id)
    {
        $license = SystemLicense::findOrFail($id);
        $action = $request->input('action');

        switch ($action) {
            case 'suspend':
                $license->update(['status' => 'suspended']);
                break;
            case 'activate':
                $license->update(['status' => 'active', 'activated_at' => now()]);
                break;
        }

        return response()->json(['success' => true, 'data' => $license]);
    }
}
