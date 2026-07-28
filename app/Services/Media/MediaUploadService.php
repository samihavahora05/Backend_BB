<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    /**
     * Handle standard direct file upload.
     */
    public function uploadFile(UploadedFile $file, ?int $folderId = null, ?int $userId = null): MediaFile
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generate unique name to prevent collisions
        $name = Str::uuid() . '.' . $extension;
        
        $path = $file->storeAs('media/' . date('Y/m'), $name, 'public');

        return MediaFile::create([
            'folder_id' => $folderId,
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $mimeType,
            'extension' => strtolower($extension),
            'size' => $size,
            'created_by' => $userId,
        ]);
    }

    /**
     * Handle chunked file uploads (e.g., from Resumable.js or custom chunks)
     */
    public function handleChunk(UploadedFile $file, string $uploadId, int $chunkIndex, int $totalChunks, string $originalName, ?int $folderId = null, ?int $userId = null)
    {
        $chunkPath = "chunks/{$uploadId}";
        Storage::disk('local')->putFileAs($chunkPath, $file, "{$chunkIndex}.part");

        // Check if all chunks are uploaded
        $files = Storage::disk('local')->files($chunkPath);
        
        if (count($files) === $totalChunks) {
            return $this->mergeChunks($uploadId, $totalChunks, $originalName, $folderId, $userId);
        }

        return ['status' => 'chunk_uploaded', 'progress' => round((count($files) / $totalChunks) * 100, 2)];
    }

    /**
     * Merge all uploaded chunks into a single file.
     */
    protected function mergeChunks(string $uploadId, int $totalChunks, string $originalName, ?int $folderId, ?int $userId): MediaFile
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $finalName = Str::uuid() . '.' . $extension;
        $finalPath = 'media/' . date('Y/m') . '/' . $finalName;
        
        $absoluteFinalPath = Storage::disk('public')->path($finalPath);
        
        // Ensure directory exists
        if (!file_exists(dirname($absoluteFinalPath))) {
            mkdir(dirname($absoluteFinalPath), 0755, true);
        }

        $out = fopen($absoluteFinalPath, 'wb');
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = Storage::disk('local')->path("chunks/{$uploadId}/{$i}.part");
            $in = fopen($chunkFile, 'rb');
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            fclose($in);
            unlink($chunkFile); // Delete chunk after merging
        }
        
        fclose($out);
        Storage::disk('local')->deleteDirectory("chunks/{$uploadId}");

        $size = filesize($absoluteFinalPath);
        $mimeType = mime_content_type($absoluteFinalPath);

        return MediaFile::create([
            'folder_id' => $folderId,
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'path' => $finalPath,
            'disk' => 'public',
            'mime_type' => $mimeType ?: 'application/octet-stream',
            'extension' => strtolower($extension),
            'size' => $size,
            'created_by' => $userId,
        ]);
    }
}
