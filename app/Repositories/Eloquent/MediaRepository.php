<?php

namespace App\Repositories\Eloquent;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MediaRepository
{
    /**
     * Get root folders or contents of a specific folder
     */
    public function getFolderContents(?int $folderId = null, array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc')
    {
        $folders = MediaFolder::where('parent_id', $folderId)
            ->withCount('files')
            ->orderBy($sortBy, $sortDir)
            ->get();

        $filesQuery = MediaFile::where('folder_id', $folderId);

        // Apply filters
        $filesQuery = $this->applyFilters($filesQuery, $filters);

        $files = $filesQuery->orderBy($sortBy, $sortDir)->paginate(50);

        return [
            'folders' => $folders,
            'files' => $files
        ];
    }

    /**
     * Get global statistics for the Media Manager
     */
    public function getStatistics(): array
    {
        return [
            'total_files' => MediaFile::count(),
            'total_size' => MediaFile::sum('size'), // bytes
            'images' => MediaFile::where('mime_type', 'like', 'image/%')->count(),
            'videos' => MediaFile::where('mime_type', 'like', 'video/%')->count(),
            'documents' => MediaFile::where('mime_type', 'like', 'application/%')->count(),
            
            // Assuming 50GB total available quota for the entire platform
            'total_quota' => 50 * 1024 * 1024 * 1024,
            
            'recent_uploads' => MediaFile::whereDate('created_at', today())->count(),
        ];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['search'])) {
            $query->where('original_name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['type'])) {
            $query->where('mime_type', 'like', $filters['type'] . '/%');
        }

        if (isset($filters['extension'])) {
            $query->where('extension', $filters['extension']);
        }

        return $query;
    }
}
