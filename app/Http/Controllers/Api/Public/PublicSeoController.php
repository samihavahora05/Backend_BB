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

        // Normalize path: check both exact path and trimmed path
        $normalized = '/' . trim($path, '/');
        $seo = SeoMetadata::where('url_path', $path)
            ->orWhere('url_path', $normalized)
            ->orWhere('url_path', $normalized . '/')
            ->first();

        if ($seo) {
            return response()->json($seo, 200);
        }

        return response()->json(null, 200);
    }
}
