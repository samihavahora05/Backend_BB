<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemBackup;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GenerateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    protected $backupId;

    public function __construct($backupId)
    {
        $this->backupId = $backupId;
    }

    public function handle()
    {
        $backup = SystemBackup::find($this->backupId);
        if (!$backup) return;

        $startTime = Carbon::now();
        $backup->update(['status' => 'in_progress']);

        $fullPath = storage_path('app/' . $backup->file_path);
        Storage::disk('local')->makeDirectory('backups');

        try {
            if ($backup->type == 'Database' || $backup->type == 'Complete') {
                $dbUser = env('DB_USERNAME', 'root');
                $dbPass = env('DB_PASSWORD', '');
                $dbHost = env('DB_HOST', '127.0.0.1');
                $dbName = env('DB_DATABASE', 'blueboxx_db');
                
                $sqlPath = $backup->type == 'Complete' ? storage_path('app/backups/temp_db.sql') : $fullPath;
                
                $passStr = $dbPass ? "--password=\"{$dbPass}\"" : "";
                $cmd = "mysqldump --user=\"{$dbUser}\" {$passStr} --host=\"{$dbHost}\" \"{$dbName}\" > \"{$sqlPath}\"";
                exec($cmd, $output, $returnVar);
                
                if ($returnVar !== 0) {
                    $cmd = "C:\\xampp\\mysql\\bin\\mysqldump.exe --user=\"{$dbUser}\" {$passStr} --host=\"{$dbHost}\" \"{$dbName}\" > \"{$sqlPath}\"";
                    exec($cmd, $output, $returnVar);
                }
                
                if ($returnVar !== 0) {
                    throw new \Exception('Failed to generate database backup. Ensure mysqldump is installed.');
                }
            }

            if ($backup->type == 'Files' || $backup->type == 'Complete') {
                $zip = new \ZipArchive();
                if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                    $sourceDir = storage_path('app/public');
                    if (is_dir($sourceDir)) {
                        $files = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($sourceDir),
                            \RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        foreach ($files as $name => $file) {
                            if (!$file->isDir()) {
                                $filePathInZip = substr($file->getRealPath(), strlen($sourceDir) + 1);
                                $zip->addFile($file->getRealPath(), 'files/' . $filePathInZip);
                            }
                        }
                    }
                    
                    if ($backup->type == 'Complete' && file_exists(storage_path('app/backups/temp_db.sql'))) {
                        $zip->addFile(storage_path('app/backups/temp_db.sql'), 'database.sql');
                    }
                    $zip->close();
                    
                    if ($backup->type == 'Complete' && file_exists(storage_path('app/backups/temp_db.sql'))) {
                        unlink(storage_path('app/backups/temp_db.sql'));
                    }
                } else {
                    throw new \Exception('Failed to create zip file');
                }
            }

            $endTime = Carbon::now();
            $duration = $startTime->diffInSeconds($endTime);

            // Generate checksum (MD5) for integrity
            $checksum = md5_file($fullPath);

            $backup->update([
                'status' => 'completed',
                'size' => number_format(Storage::disk('local')->size($backup->file_path) / 1048576, 2) . ' MB',
                'size_bytes' => Storage::disk('local')->size($backup->file_path),
                'checksum' => $checksum,
                'completed_at' => $endTime,
                'duration' => $duration
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => Carbon::now()
            ]);
        }
    }
}
