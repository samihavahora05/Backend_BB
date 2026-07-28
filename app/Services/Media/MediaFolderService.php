<?php

namespace App\Services\Media;

use App\Models\MediaFolder;
use App\Models\MediaFile;
use Illuminate\Support\Facades\DB;
use Exception;

class MediaFolderService
{
    /**
     * Recursively delete a folder, its subfolders, and all files.
     * (Actually uses SoftDeletes to move to Recycle Bin)
     */
    public function deleteFolder(MediaFolder $folder): void
    {
        DB::transaction(function () use ($folder) {
            // Delete files in this folder
            MediaFile::where('folder_id', $folder->id)->delete();

            // Recursively delete subfolders
            foreach ($folder->children as $child) {
                $this->deleteFolder($child);
            }

            // Finally, delete this folder
            $folder->delete();
        });
    }

    /**
     * Move a folder to a new parent folder.
     */
    public function moveFolder(MediaFolder $folder, ?int $newParentId): bool
    {
        if ($newParentId === $folder->id) {
            throw new Exception("Cannot move folder into itself.");
        }

        // Prevent circular moving (moving a folder into its own child)
        if ($newParentId) {
            $descendants = $this->getDescendantIds($folder);
            if (in_array($newParentId, $descendants)) {
                throw new Exception("Cannot move folder into its own subfolder.");
            }
        }

        return $folder->update(['parent_id' => $newParentId]);
    }

    /**
     * Helper to get all descendant IDs of a folder to prevent circular loops
     */
    protected function getDescendantIds(MediaFolder $folder): array
    {
        $ids = [];
        foreach ($folder->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        return $ids;
    }
}
