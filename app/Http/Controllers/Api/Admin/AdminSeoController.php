<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMetadata;
use Illuminate\Http\Request;

class AdminSeoController extends Controller
{
    public function index()
    {
        return response()->json(SeoMetadata::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url_path' => 'required|string|unique:seo_metadata,url_path',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'og_image' => 'nullable|url',
            'schema_json' => 'nullable|array',
            'robots' => 'nullable|string'
        ]);

        $seo = SeoMetadata::create($validated);
        return response()->json($seo, 201);
    }

    public function update(Request $request, SeoMetadata $seo)
    {
        $validated = $request->validate([
            'url_path' => 'sometimes|required|string|unique:seo_metadata,url_path,' . $seo->id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'og_image' => 'nullable|url',
            'schema_json' => 'nullable|array',
            'robots' => 'nullable|string'
        ]);

        $seo->update($validated);
        return response()->json($seo);
    }

    public function destroy(SeoMetadata $seo)
    {
        $seo->delete();
        return response()->json(['message' => 'SEO Metadata deleted']);
    }
}
