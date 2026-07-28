<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Handle generic file uploads
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120', // Max 5MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('uploads', 'public');
            
            // Generate full URL
            $url = asset('storage/' . $path);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'url' => $url,
                'path' => $path
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No file provided'
        ], 400);
    }
}
