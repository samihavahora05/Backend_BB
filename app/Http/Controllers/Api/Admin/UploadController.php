<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Handle generic file and image uploads for the Admin panel.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file'  => 'nullable|file|max:20480', // 20MB max
            'image' => 'nullable|file|max:20480',
            'type'  => 'nullable|string|max:50',
        ]);

        $file = $request->file('file') ?? $request->file('image');

        if (!$file) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No file provided for upload.'
            ], 400);
        }

        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm'];
            if (in_array($extension, $blockedExtensions)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'File extension not allowed for security reasons.'
                ], 403);
            }

            $folder = 'uploads';
            if ($type = $request->input('type')) {
                $sanitizedType = preg_replace('/[^a-zA-Z0-9_\-]/', '', $type);
                if (!empty($sanitizedType)) {
                    $folder .= '/' . $sanitizedType;
                }
            }

            $filename = Str::random(32) . '.' . ($extension ?: 'bin');
            $path = $file->storeAs($folder, $filename, 'public');

            if (!$path || !Storage::disk('public')->exists($path)) {
                Log::error('Upload failed: File could not be physically written to disk.', [
                    'disk' => 'public',
                    'folder' => $folder,
                    'filename' => $filename,
                ]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to save file to server storage.'
                ], 500);
            }

            $url = StorageHelper::url($path);

            return response()->json([
                'status'  => 'success',
                'message' => 'File uploaded successfully',
                'url'     => $url,
                'path'    => $path,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Admin file upload exception: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Server error during file upload: ' . $e->getMessage()
            ], 500);
        }
    }
}
