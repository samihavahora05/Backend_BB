<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Lesson;
use Google\Client;
use Google\Service\Drive;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseCurriculumController extends Controller
{
    public function getCurriculum($course_id)
    {
        $modules = Module::with(['lessons' => function($q) {
                $q->orderBy('order');
            }])
            ->where('course_id', $course_id)
            ->orderBy('order')
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $modules
        ]);
    }
    
    // --- MODULES ---
    public function storeModule(Request $request, $course_id)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);
        
        $order = Module::where('course_id', $course_id)->max('order') + 1;
        
        $module = Module::create([
            'course_id' => $course_id,
            'title' => $request->title,
            'order' => $order
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $module->load('lessons')
        ]);
    }
    
    public function updateModule(Request $request, $id)
    {
        $module = Module::findOrFail($id);
        $request->validate(['title' => 'required|string|max:255']);
        $module->update(['title' => $request->title]);
        return response()->json(['status' => 'success', 'data' => $module->load('lessons')]);
    }
    
    public function destroyModule($id)
    {
        Module::findOrFail($id)->delete();
        return response()->json(['status' => 'success']);
    }
    
    public function reorderModules(Request $request)
    {
        $request->validate(['ordered_ids' => 'required|array']);
        foreach ($request->ordered_ids as $index => $id) {
            Module::where('id', $id)->update(['order' => $index]);
        }
        return response()->json(['status' => 'success']);
    }
    
    // --- LESSONS ---
    // Helper function to extract Google Drive video duration
    private function getGoogleDriveVideoDuration($url)
    {
        if (strpos($url, 'drive.google.com') !== false && preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            try {
                $client = new Client();
                $client->setAuthConfig(storage_path('app/google-drive/service-account.json'));
                $client->addScope('https://www.googleapis.com/auth/drive.readonly');
                $service = new Drive($client);
                
                $file = $service->files->get($matches[1], ['fields' => 'videoMediaMetadata']);
                if ($file->getVideoMediaMetadata()) {
                    $durationMillis = $file->getVideoMediaMetadata()->getDurationMillis();
                    return $durationMillis / 60000; // Return exact minutes
                }
            } catch (\Exception $e) {
                // Ignore failure
            }
        }
        return null;
    }

    public function storeLesson(Request $request, $module_id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:Video,PDF,Text,Quiz,Assignment',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string',
            'duration_minutes' => 'nullable|numeric'
        ]);
        
        $order = Lesson::where('module_id', $module_id)->max('order') + 1;
        $data = $request->all();
        
        if (empty($data['duration_minutes']) && !empty($data['video_url'])) {
            $fetchedDuration = $this->getGoogleDriveVideoDuration($data['video_url']);
            if ($fetchedDuration) {
                $data['duration_minutes'] = $fetchedDuration;
            }
        }
        
        $lesson = Lesson::create(array_merge($data, [
            'module_id' => $module_id,
            'order' => $order
        ]));
        
        return response()->json([
            'status' => 'success',
            'data' => $lesson
        ]);
    }
    
    public function updateLesson(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:Video,PDF,Text,Quiz,Assignment',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string',
            'duration_minutes' => 'nullable|numeric'
        ]);
        
        $data = $request->all();
        if (empty($data['duration_minutes']) && !empty($data['video_url'])) {
            $fetchedDuration = $this->getGoogleDriveVideoDuration($data['video_url']);
            if ($fetchedDuration) {
                $data['duration_minutes'] = $fetchedDuration;
            }
        }
        
        $lesson->update($data);
        return response()->json(['status' => 'success', 'data' => $lesson]);
    }
    
    public function destroyLesson($id)
    {
        Lesson::findOrFail($id)->delete();
        return response()->json(['status' => 'success']);
    }
    
    public function reorderLessons(Request $request)
    {
        $request->validate([
            'ordered_ids' => 'required|array'
        ]);
        foreach ($request->ordered_ids as $index => $id) {
            Lesson::where('id', $id)->update(['order' => $index]);
        }
        return response()->json(['status' => 'success']);
    }
}
