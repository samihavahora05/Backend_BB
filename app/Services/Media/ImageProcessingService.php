<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Assuming GD is default for PHP
use Illuminate\Support\Str;

class ImageProcessingService
{
    protected ?ImageManager $manager = null;

    public function __construct()
    {
        if (class_exists(ImageManager::class)) {
            try {
                $this->manager = new ImageManager(new Driver());
            } catch (\Exception $e) {
                // GD missing, set manager to null
                $this->manager = null;
            }
        }
    }

    /**
     * Generate metadata (dimensions) and optionally a thumbnail
     */
    public function processImage(MediaFile $mediaFile): void
    {
        if (!$this->manager || !str_starts_with($mediaFile->mime_type, 'image/')) {
            return;
        }

        try {
            $absolutePath = Storage::disk($mediaFile->disk)->path($mediaFile->path);
            $image = $this->manager->read($absolutePath);

            $metadata = [
                'width' => $image->width(),
                'height' => $image->height(),
            ];

            // Auto-generate thumbnail
            $thumbPath = 'media/' . date('Y/m') . '/thumbs/' . pathinfo($mediaFile->path, PATHINFO_BASENAME);
            
            $thumb = $this->manager->read($absolutePath)->scale(width: 300);
            
            // Ensure thumb directory exists
            $absoluteThumbPath = Storage::disk($mediaFile->disk)->path($thumbPath);
            if (!file_exists(dirname($absoluteThumbPath))) {
                mkdir(dirname($absoluteThumbPath), 0755, true);
            }

            $thumb->save($absoluteThumbPath);
            $metadata['thumbnail'] = $thumbPath;

            $mediaFile->update(['metadata' => $metadata]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Image processing failed for MediaFile {$mediaFile->id}: " . $e->getMessage());
        }
    }

    /**
     * Convert an image to WebP
     */
    public function convertToWebp(MediaFile $mediaFile): ?MediaFile
    {
        if (!$this->manager || !str_starts_with($mediaFile->mime_type, 'image/')) {
            return null;
        }

        $absolutePath = Storage::disk($mediaFile->disk)->path($mediaFile->path);
        
        $newPath = preg_replace('/\.[^.]+$/', '.webp', $mediaFile->path);
        $absoluteNewPath = Storage::disk($mediaFile->disk)->path($newPath);

        $image = $this->manager->read($absolutePath);
        $image->toWebp(80)->save($absoluteNewPath);

        // Track as new version or update original
        $mediaFile->update([
            'path' => $newPath,
            'extension' => 'webp',
            'mime_type' => 'image/webp',
            'size' => filesize($absoluteNewPath)
        ]);

        return $mediaFile;
    }
}
