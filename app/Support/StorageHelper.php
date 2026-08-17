<?php

namespace App\Support;

class StorageHelper
{
    /**
     * Format a storage file path into a full URL safely.
     * If the path is already an absolute HTTP/HTTPS URL, return it as-is.
     *
     * @param string|null $path
     * @return string|null
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
