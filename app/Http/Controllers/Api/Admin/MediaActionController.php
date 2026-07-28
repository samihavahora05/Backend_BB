<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Repositories\Eloquent\MediaRepository;
use Illuminate\Http\Request;

class MediaActionController extends Controller
{
    protected MediaRepository $repository;

    public function __construct(MediaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => $this->repository->getStatistics()
        ]);
    }

    public function trash()
    {
        $files = MediaFile::onlyTrashed()->with('folder')->get();
        $folders = MediaFolder::onlyTrashed()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'files' => $files,
                'folders' => $folders
            ]
        ]);
    }

    public function restoreFile($id)
    {
        $file = MediaFile::onlyTrashed()->findOrFail($id);
        $file->restore();
        return response()->json(['success' => true, 'message' => 'File restored successfully.']);
    }

    public function restoreFolder($id)
    {
        $folder = MediaFolder::onlyTrashed()->findOrFail($id);
        $folder->restore();
        // optionally restore all children files
        return response()->json(['success' => true, 'message' => 'Folder restored successfully.']);
    }

    public function forceDeleteFile($id)
    {
        $file = MediaFile::onlyTrashed()->findOrFail($id);
        
        // Delete actual file from disk
        \Illuminate\Support\Facades\Storage::disk($file->disk)->delete($file->path);
        
        // If thumb exists, delete it too
        if (isset($file->metadata['thumbnail'])) {
            \Illuminate\Support\Facades\Storage::disk($file->disk)->delete($file->metadata['thumbnail']);
        }
        
        $file->forceDelete();
        
        return response()->json(['success' => true, 'message' => 'File permanently deleted.']);
    }
}
