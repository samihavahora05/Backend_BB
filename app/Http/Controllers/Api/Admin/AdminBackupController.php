<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemBackup;
use App\Models\BackupSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminBackupController extends Controller
{
    public function index(Request $request)
    {
        $backups = SystemBackup::with('creator')->latest()->paginate($request->get('per_page', 15));
        return response()->json($backups);
    }

    public function generate(Request $request)
    {
        $request->validate(['type' => 'required|in:Database,Files,Complete']);
        
        $fileName = 'blueboxx_'.strtolower($request->type).'_'.now()->format('Y_m_d_His').($request->type == 'Database' ? '.sql' : '.zip');
        $filePath = 'backups/' . $fileName;

        $backup = SystemBackup::create([
            'name' => $fileName,
            'type' => $request->type,
            'size' => 'Pending',
            'size_bytes' => 0,
            'status' => 'pending',
            'disk' => 'local',
            'file_path' => $filePath,
            'created_by' => $request->user()->id,
        ]);

        \App\Jobs\GenerateBackupJob::dispatch($backup->id);

        return response()->json(['success' => true, 'message' => 'Backup job dispatched successfully', 'data' => $backup]);
    }

    public function destroy($id)
    {
        $backup = SystemBackup::findOrFail($id);
        if (Storage::disk('local')->exists($backup->file_path)) {
            Storage::disk('local')->delete($backup->file_path);
        }
        $backup->delete();
        return response()->json(['success' => true]);
    }

    public function download($id)
    {
        $backup = SystemBackup::findOrFail($id);
        if (!Storage::disk('local')->exists($backup->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        return Storage::disk('local')->download($backup->file_path);
    }

    public function restore(Request $request, $id)
    {
        $backup = SystemBackup::findOrFail($id);
        if (!Storage::disk('local')->exists($backup->file_path)) {
            return response()->json(['error' => 'Backup file not found on disk'], 404);
        }

        \App\Jobs\RestoreBackupJob::dispatch($backup->id, $request->user()->id);

        return response()->json(['success' => true, 'message' => "Restore job for {$backup->name} dispatched successfully."]);
    }

    public function retry($id)
    {
        $backup = SystemBackup::findOrFail($id);
        
        if ($backup->status !== 'failed') {
            return response()->json(['error' => 'Only failed backups can be retried.'], 400);
        }

        $backup->update([
            'status' => 'pending',
            'error_message' => null,
            'completed_at' => null,
            'checksum' => null,
            'duration' => null,
            'size' => 'Pending',
            'size_bytes' => 0,
        ]);

        \App\Jobs\GenerateBackupJob::dispatch($backup->id);

        return response()->json(['success' => true, 'message' => 'Backup job retried successfully.']);
    }

    public function dashboard()
    {
        $backups = SystemBackup::all();
        $totalSize = $backups->sum('size_bytes');
        $successful = $backups->where('status', 'completed')->count();
        $failed = $backups->where('status', 'failed')->count();
        $lastBackup = $backups->where('status', 'completed')->sortByDesc('completed_at')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_backups' => $backups->count(),
                'total_size_mb' => number_format($totalSize / 1048576, 2),
                'successful_backups' => $successful,
                'failed_backups' => $failed,
                'last_backup_time' => $lastBackup ? $lastBackup->completed_at : null,
                'last_backup_name' => $lastBackup ? $lastBackup->name : 'N/A'
            ]
        ]);
    }

    public function getSettings()
    {
        $settings = BackupSetting::pluck('value', 'key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function updateSettings(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            BackupSetting::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
        }
        return response()->json(['success' => true]);
    }
}
