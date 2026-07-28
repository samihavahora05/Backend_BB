<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf,mp4|max:20480', // 20MB max
            'type' => 'required|string|in:profile,resume,course,blog,other'
        ]);

        $file = $request->file('file');
        
        // Security: Block double extensions and executables
        $extension = strtolower($file->getClientOriginalExtension());
        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm'];
        if (in_array($extension, $blockedExtensions)) {
            return response()->json(['message' => 'File type not allowed for security reasons.'], 403);
        }

        $folder = 'uploads/' . $request->type;
        
        // Generate safe unique filename
        $filename = Str::random(32) . '.' . $extension;

        // Store file publicly
        $path = $file->storeAs($folder, $filename, 'public');

        return response()->json([
            'message' => 'File uploaded successfully',
            'url' => Storage::url($path),
            'path' => $path
        ], 201);
    }
}
