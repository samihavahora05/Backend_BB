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

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $backupId;
    protected $userId;

    public function __construct($backupId, $userId)
    {
        $this->backupId = $backupId;
        $this->userId = $userId;
    }

    public function handle()
    {
        $backup = SystemBackup::find($this->backupId);
        if (!$backup) return;

        $logs = [];
        $addLog = function($msg, $type = 'info') use (&$logs) {
            $logs[] = ['time' => now()->toDateTimeString(), 'type' => $type, 'message' => $msg];
        };

        try {
            $addLog('Restore started by User ID: ' . $this->userId);

            if (!Storage::disk('local')->exists($backup->file_path)) {
                throw new \Exception('Backup file not found on disk.');
            }

            // Verify checksum if exists
            if ($backup->checksum) {
                $addLog('Verifying file checksum...');
                $currentChecksum = md5_file(storage_path('app/' . $backup->file_path));
                if ($currentChecksum !== $backup->checksum) {
                    throw new \Exception('Checksum mismatch. File may be corrupted.');
                }
                $addLog('Checksum verified successfully.');
            }

            $sqlPath = storage_path('app/' . $backup->file_path);

            if ($backup->type == 'Database' || $backup->type == 'Complete') {
                $driver = config('database.default', 'sqlite');

                if ($backup->type == 'Complete') {
                    $addLog('Extracting database.sql from zip...');
                    $zip = new \ZipArchive();
                    if ($zip->open($sqlPath) === TRUE) {
                        $zip->extractTo(storage_path('app/backups/'), array('database.sql'));
                        $zip->close();
                        $sqlPath = storage_path('app/backups/database.sql');
                        $addLog('Database extracted successfully.');
                    } else {
                        throw new \Exception('Failed to open Complete backup zip file.');
                    }
                }

                if ($driver === 'sqlite') {
                    $addLog('Restoring SQLite database file...');
                    $sqlitePath = config('database.connections.sqlite.database', database_path('database.sqlite'));
                    if (file_exists($sqlPath)) {
                        copy($sqlPath, $sqlitePath);
                        $addLog('SQLite database restored successfully.');
                    } else {
                        throw new \Exception('SQLite backup source file not found.');
                    }
                } else {
                    $dbUser = config('database.connections.mysql.username', 'root');
                    $dbPass = config('database.connections.mysql.password', '');
                    $dbHost = config('database.connections.mysql.host', '127.0.0.1');
                    $dbPort = config('database.connections.mysql.port', '3306');
                    $dbName = config('database.connections.mysql.database', 'laravel');

                    $addLog('Starting database import via mysql CLI...');
                    $passArg = ($dbPass !== null && $dbPass !== '') ? '--password=' . escapeshellarg($dbPass) : '';
                    $cmd = sprintf(
                        'mysql --user=%s %s --host=%s --port=%s %s < %s',
                        escapeshellarg($dbUser),
                        $passArg,
                        escapeshellarg($dbHost),
                        escapeshellarg($dbPort),
                        escapeshellarg($dbName),
                        escapeshellarg($sqlPath)
                    );
                    exec($cmd, $output, $returnVar);

                    if ($returnVar !== 0 && file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')) {
                        $addLog('mysql CLI failed, falling back to XAMPP binary...');
                        $cmd = sprintf(
                            'C:\\xampp\\mysql\\bin\\mysql.exe --user=%s %s --host=%s --port=%s %s < %s',
                            escapeshellarg($dbUser),
                            $passArg,
                            escapeshellarg($dbHost),
                            escapeshellarg($dbPort),
                            escapeshellarg($dbName),
                            escapeshellarg($sqlPath)
                        );
                        exec($cmd, $output, $returnVar);
                    }

                    if ($returnVar !== 0) {
                        throw new \Exception('Failed to import database. Check mysql CLI tools.');
                    }
                    $addLog('Database imported successfully.');
                }

                if ($backup->type == 'Complete' && file_exists(storage_path('app/backups/database.sql'))) {
                    unlink(storage_path('app/backups/database.sql'));
                }
            }
            
            if ($backup->type == 'Files' || $backup->type == 'Complete') {
                $addLog('Extracting files to public storage...');
                $zip = new \ZipArchive();
                $fullPath = storage_path('app/' . $backup->file_path);
                if ($zip->open($fullPath) === TRUE) {
                    $zip->extractTo(storage_path('app/public'));
                    $zip->close();
                    $addLog('Files restored successfully.');
                } else {
                    throw new \Exception('Failed to open zip file for file restoration.');
                }
            }

            $addLog('Restore completed successfully.', 'success');
            
            $backup->update([
                'restore_logs' => json_encode($logs)
            ]);

        } catch (\Exception $e) {
            $addLog('Restore Failed: ' . $e->getMessage(), 'error');
            $backup->update([
                'restore_logs' => json_encode($logs)
            ]);
        }
    }
}
