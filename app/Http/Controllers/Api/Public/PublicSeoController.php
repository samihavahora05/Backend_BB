<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\SeoMetadata;
use Illuminate\Http\Request;

class PublicSeoController extends Controller
{
    public function getMetadata(Request $request)
    {
        $path = $request->query('path');
        
        if (!$path) {
            return response()->json(['error' => 'Path parameter is required'], 400);
        }

        // Exact match or wildcard fallback logic could go here
        $seo = SeoMetadata::where('url_path', $path)->first();

        if ($seo) {
            return response()->json($seo);
        }

        return response()->json(['message' => 'No custom SEO metadata found for this route'], 404);
    }
}
