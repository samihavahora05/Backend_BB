<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFolder;
use App\Services\Media\MediaFolderService;
use Illuminate\Http\Request;

class MediaFolderController extends Controller
{
    protected MediaFolderService $service;

    public function __construct(MediaFolderService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $folders = MediaFolder::all();
        return response()->json(['success' => true, 'data' => $folders]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id'
        ]);

        $folder = MediaFolder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'created_by' => $request->user()->id ?? null
        ]);

        return response()->json(['success' => true, 'data' => $folder]);
    }

    public function update(Request $request, MediaFolder $folder)
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $folder->update(['name' => $request->name]);

        return response()->json(['success' => true, 'data' => $folder]);
    }

    public function destroy(MediaFolder $folder)
    {
        $this->service->deleteFolder($folder);
        return response()->json(['success' => true, 'message' => 'Folder moved to trash.']);
    }

    public function move(Request $request, MediaFolder $folder)
    {
        $request->validate(['new_parent_id' => 'nullable|exists:media_folders,id']);
        
        try {
            $this->service->moveFolder($folder, $request->new_parent_id);
            return response()->json(['success' => true, 'message' => 'Folder moved successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
