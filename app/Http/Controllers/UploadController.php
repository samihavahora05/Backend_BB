<?php

namespace App\Http\Controllers;

use App\Support\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|max:20480', // 20MB max
            'image' => 'nullable|file|max:20480',
            'type' => 'nullable|string|max:50'
        ]);

        $file = $request->file('file') ?? $request->file('image');

        if (!$file) {
            return response()->json(['message' => 'No file provided.'], 400);
        }
        
        // Security: Block double extensions and executables
        $extension = strtolower($file->getClientOriginalExtension());
        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm'];
        if (in_array($extension, $blockedExtensions)) {
            return response()->json(['message' => 'File type not allowed for security reasons.'], 403);
        }

        $type = $request->input('type', 'general');
        $sanitizedType = preg_replace('/[^a-zA-Z0-9_\-]/', '', $type);
        $folder = 'uploads/' . ($sanitizedType ?: 'general');
        
        // Generate safe unique filename
        $filename = Str::random(32) . '.' . ($extension ?: 'bin');

        // Store file publicly
        $path = $file->storeAs($folder, $filename, 'public');

        if (!$path || !Storage::disk('public')->exists($path)) {
            Log::error('Public upload failed to write to disk.', ['path' => $path]);
            return response()->json(['message' => 'Failed to save file to server storage.'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'File uploaded successfully',
            'url' => StorageHelper::url($path),
            'path' => $path
        ], 201);
    }
}
