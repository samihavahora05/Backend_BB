<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Services\Media\MediaUploadService;
use App\Services\Media\ImageProcessingService;
use App\Repositories\Eloquent\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaFileController extends Controller
{
    protected MediaUploadService $uploadService;
    protected ImageProcessingService $imageService;
    protected MediaRepository $repository;

    public function __construct(MediaUploadService $uploadService, ImageProcessingService $imageService, MediaRepository $repository)
    {
        $this->uploadService = $uploadService;
        $this->imageService = $imageService;
        $this->repository = $repository;
    }

    /**
     * List files and folders.
     */
    public function index(Request $request)
    {
        $folderId = $request->query('folder_id');
        $filters = $request->only(['search', 'type', 'extension']);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        $data = $this->repository->getFolderContents($folderId, $filters, $sortBy, $sortDir);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Standard upload endpoint
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max standard
            'folder_id' => 'nullable|exists:media_folders,id'
        ]);

        $file = $this->uploadService->uploadFile($request->file('file'), $request->folder_id, $request->user()->id ?? null);
        
        // Auto-process image
        $this->imageService->processImage($file);

        return response()->json(['success' => true, 'data' => $file]);
    }

    /**
     * Chunk upload endpoint for massive files.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'original_name' => 'required|string'
        ]);

        $result = $this->uploadService->handleChunk(
            $request->file('file'),
            $request->upload_id,
            $request->chunk_index,
            $request->total_chunks,
            $request->original_name,
            $request->folder_id,
            $request->user()->id ?? null
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Convert Image to WebP
     */
    public function convertToWebp(MediaFile $file)
    {
        $result = $this->imageService->convertToWebp($file);
        
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'File is not an image.'], 400);
        }
        
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function update(Request $request, MediaFile $file)
    {
        $request->validate(['original_name' => 'required|string|max:255']);
        $file->update(['original_name' => $request->original_name]);
        return response()->json(['success' => true, 'data' => $file]);
    }

    public function destroy(MediaFile $file)
    {
        $file->delete(); // Soft Delete
        return response()->json(['success' => true, 'message' => 'File moved to trash.']);
    }
}
