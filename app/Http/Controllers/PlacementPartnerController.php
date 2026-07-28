<?php

namespace App\Http\Controllers;

use App\Models\PlacementPartner;
use Illuminate\Http\Request;

class PlacementPartnerController extends Controller
{
    /**
     * Public list of placement partners
     */
    public function index()
    {
        return response()->json(PlacementPartner::where('is_active', true)->get());
    }

    /**
     * Admin create placement partner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'is_active' => 'boolean'
        ]);

        $partner = PlacementPartner::create($validated);

        return response()->json($partner, 201);
    }

    /**
     * Admin show placement partner
     */
    public function show($id)
    {
        return response()->json(PlacementPartner::findOrFail($id));
    }

    /**
     * Admin update placement partner
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean'
        ]);

        $partner = PlacementPartner::findOrFail($id);
        $partner->update($validated);

        return response()->json($partner);
    }

    /**
     * Admin delete placement partner
     */
    public function destroy($id)
    {
        $partner = PlacementPartner::findOrFail($id);
        $partner->delete();

        return response()->json(['message' => 'Placement partner deleted successfully']);
    }
}
