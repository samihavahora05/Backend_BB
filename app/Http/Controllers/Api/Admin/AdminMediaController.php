<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminMediaController extends Controller
{
    public function getFolders(Request $request)
    {
        $folders = MediaFolder::withCount('files')->get();
        return response()->json([
            'success' => true,
            'data' => $folders
        ]);
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        $folder = MediaFolder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'created_by' => $request->user()?->id ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $folder,
            'message' => 'Folder created successfully'
        ], 201);
    }

    public function deleteFolder($id)
    {
        $folder = MediaFolder::findOrFail($id);
        $folder->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Folder hidden (Soft Deleted)'
        ]);
    }

    public function getFiles(Request $request)
    {
        $query = MediaFile::query();

        if ($request->has('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where('original_name', 'like', '%' . $request->search . '%');
        }

        $files = $query->latest()->get()->map(function ($file) {
            return array_merge($file->toArray(), [
                'url'        => $file->path ? asset('storage/' . $file->path) : null,
                'size_bytes' => $file->size,
            ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $files
        ]);
    }



    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'folder_id' => 'nullable|exists:media_folders,id'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Store file
        $path = $file->store('media_files', 'public');

        $mediaFile = MediaFile::create([
            'folder_id' => $request->folder_id,
            'name' => uniqid() . '_' . time() . '.' . $extension,
            'original_name' => $originalName,
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'created_by' => $request->user()?->id ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $mediaFile,
            'message' => 'File uploaded successfully'
        ], 201);
    }

    public function updateFile(Request $request, $id)
    {
        $request->validate([
            'original_name' => 'required|string|max:255'
        ]);

        $file = MediaFile::findOrFail($id);
        $file->update([
            'original_name' => $request->original_name
        ]);

        return response()->json([
            'success' => true,
            'data' => $file,
            'message' => 'File updated successfully'
        ]);
    }

    public function deleteFile($id)
    {
        $file = MediaFile::withTrashed()->findOrFail($id);
        
        if (!$file->trashed()) {
            // Soft delete only, so it stays in the database
            $file->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'File hidden from Media Manager (Soft Deleted in DB)'
        ]);
    }

    public function getTrash(Request $request)
    {
        $files = MediaFile::onlyTrashed()->orderBy('deleted_at', 'desc')->get()->map(function ($file) {
            return array_merge($file->toArray(), [
                'url'        => $file->path ? asset('storage/' . $file->path) : null,
                'size_bytes' => $file->size,
            ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $files
        ]);
    }

    public function restoreFile($id)
    {
        $file = MediaFile::withTrashed()->findOrFail($id);
        $file->restore();
        return response()->json([
            'success' => true,
            'message' => 'File restored successfully'
        ]);
    }

    public function forceDeleteFile($id)
    {
        $file = MediaFile::withTrashed()->findOrFail($id);
        
        // Optionally delete the physical file if you want to completely remove it from the server
        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        $file->forceDelete();
        
        return response()->json([
            'success' => true,
            'message' => 'File permanently deleted'
        ]);
    }

    public function getStatistics()
    {
        $totalFiles = MediaFile::count();
        $totalSize = MediaFile::sum('size');
        
        // Count by simplified type
        $images = MediaFile::where('mime_type', 'like', 'image%')->count();
        $videos = MediaFile::where('mime_type', 'like', 'video%')->count();
        $docs = MediaFile::where('mime_type', 'like', 'application/pdf')
            ->orWhere('mime_type', 'like', '%word%')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_files' => $totalFiles,
                'total_size_bytes' => (float)$totalSize,
                'types' => [
                    'image' => $images,
                    'video' => $videos,
                    'document' => $docs,
                ]
            ]
        ]);
    }
}
