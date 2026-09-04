<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Format a storage file path into a valid, production-safe URL.
     *
     * @param string|null $path
     * @return string|null
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $trimmed = trim((string)$path);
        if ($trimmed === '') {
            return null;
        }

        // Data or Blob URIs
        if (str_starts_with($trimmed, 'data:') || str_starts_with($trimmed, 'blob:')) {
            return $trimmed;
        }

        // If already an absolute external URL
        if (preg_match('#^https?://#i', $trimmed) || str_starts_with($trimmed, '//')) {
            // If it's a localhost URL saved previously in database, extract the relative path
            if (str_contains($trimmed, 'localhost') || str_contains($trimmed, '127.0.0.1')) {
                $storagePos = strpos($trimmed, '/storage/');
                if ($storagePos !== false) {
                    $trimmed = substr($trimmed, $storagePos);
                } else {
                    $uploadsPos = strpos($trimmed, '/uploads/');
                    if ($uploadsPos !== false) {
                        $trimmed = substr($trimmed, $uploadsPos);
                    }
                }
            } else {
                return $trimmed;
            }
        }

        // Frontend static assets located in frontend public/ directory
        if (
            str_starts_with($trimmed, '/students/') || str_starts_with($trimmed, 'students/') ||
            str_starts_with($trimmed, '/logo/') || str_starts_with($trimmed, 'logo/') ||
            str_starts_with($trimmed, '/images/') || str_starts_with($trimmed, 'images/') ||
            str_starts_with($trimmed, '/assets/') || str_starts_with($trimmed, 'assets/') ||
            str_starts_with($trimmed, '/testimonials photos/') || str_starts_with($trimmed, 'testimonials photos/')
        ) {
            $cleanStatic = ltrim(str_replace('\\', '/', $trimmed), '/');
            $encodedSegments = array_map('rawurlencode', explode('/', $cleanStatic));
            return '/' . implode('/', $encodedSegments);
        }

        // Clean up storage path (strip leading slashes and any duplicate "storage/" prefix)
        $cleanPath = ltrim(str_replace('\\', '/', $trimmed), '/');
        while (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = ltrim(substr($cleanPath, 8), '/');
        }

        if (empty($cleanPath)) {
            return null;
        }

        $encodedSegments = array_map('rawurlencode', explode('/', $cleanPath));
        $encodedPath = implode('/', $encodedSegments);

        // Generate URL from public disk or configured APP_URL
        $baseUrl = rtrim(config('app.url', env('APP_URL', 'http://localhost')), '/');
        
        return $baseUrl . '/storage/' . $encodedPath;
    }
}
