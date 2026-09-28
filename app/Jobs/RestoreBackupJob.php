<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemBackup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $disk = Storage::disk('local');
        $backupPath = $disk->path($backup->file_path);
        $tempRestoreDir = $disk->path('backups/temp_restore_' . $backup->id . '_' . time());

        try {
            $addLog('Restore initiated by User ID: ' . $this->userId);

            if (!file_exists($backupPath)) {
                throw new \Exception('Backup file not found on disk at ' . $backup->file_path);
            }

            // Verify checksum if available
            if (!empty($backup->checksum)) {
                $addLog('Verifying MD5 integrity checksum...');
                $currentChecksum = md5_file($backupPath);
                if ($currentChecksum !== $backup->checksum) {
                    throw new \Exception('Integrity Checksum mismatch. The backup file may be corrupt or altered.');
                }
                $addLog('Checksum verified successfully.');
            }

            if (!is_dir($tempRestoreDir)) {
                mkdir($tempRestoreDir, 0755, true);
            }

            $driver = config('database.default', 'sqlite');

            // 1. RESTORE DATABASE (if Database or Complete)
            if ($backup->type === 'Database' || $backup->type === 'Complete') {
                $sqlPath = null;
                $sqliteBinaryPath = null;

                if ($backup->type === 'Complete') {
                    $addLog('Extracting database files from Complete backup zip...');
                    $zip = new \ZipArchive();
                    if ($zip->open($backupPath) === TRUE) {
                        $zip->extractTo($tempRestoreDir);
                        $zip->close();
                        $addLog('Complete backup zip extracted to temp area.');

                        if (file_exists($tempRestoreDir . DIRECTORY_SEPARATOR . 'database.sqlite')) {
                            $sqliteBinaryPath = $tempRestoreDir . DIRECTORY_SEPARATOR . 'database.sqlite';
                        }
                        if (file_exists($tempRestoreDir . DIRECTORY_SEPARATOR . 'database.sql')) {
                            $sqlPath = $tempRestoreDir . DIRECTORY_SEPARATOR . 'database.sql';
                        }
                    } else {
                        throw new \Exception('Failed to open Complete backup zip archive.');
                    }
                } else {
                    $sqlPath = $backupPath;
                }

                // SQLite Restoration
                if ($driver === 'sqlite') {
                    $sqliteDestination = config('database.connections.sqlite.database', database_path('database.sqlite'));
                    $addLog('Restoring SQLite database to: ' . $sqliteDestination);

                    if ($sqliteBinaryPath && file_exists($sqliteBinaryPath)) {
                        copy($sqliteBinaryPath, $sqliteDestination);
                        $addLog('SQLite database restored successfully from binary snapshot.');
                    } elseif ($sqlPath && file_exists($sqlPath)) {
                        // Check if file is SQLite binary format or SQL text
                        $handle = fopen($sqlPath, 'rb');
                        $header = fread($handle, 16);
                        fclose($handle);

                        if (str_starts_with($header, 'SQLite format 3')) {
                            copy($sqlPath, $sqliteDestination);
                            $addLog('SQLite database restored directly from binary SQLite file.');
                        } else {
                            // Execute SQL text statements
                            $sqlContent = file_get_contents($sqlPath);
                            DB::connection('sqlite')->getPdo()->exec($sqlContent);
                            $addLog('SQLite database restored successfully via SQL statements.');
                        }
                    } else {
                        throw new \Exception('No valid database backup content found.');
                    }
                } else {
                    // MySQL Restoration
                    $addLog('Restoring MySQL database...');
                    if (!$sqlPath || !file_exists($sqlPath)) {
                        throw new \Exception('MySQL database.sql file not found in backup.');
                    }

                    $this->restoreMysqlDatabase($sqlPath, $addLog);
                }
            }

            // 2. RESTORE FILES (if Files or Complete)
            if ($backup->type === 'Files' || $backup->type === 'Complete') {
                $addLog('Restoring application storage files and assets...');
                $zip = new \ZipArchive();
                if ($zip->open($backupPath) === TRUE) {
                    $publicStorageDir = storage_path('app/public');
                    if (!is_dir($publicStorageDir)) {
                        mkdir($publicStorageDir, 0755, true);
                    }

                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        $entryName = $stat['name'];

                        if (str_starts_with($entryName, 'storage/public/')) {
                            $subPath = substr($entryName, strlen('storage/public/'));
                            $destPath = $publicStorageDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
                            $this->extractZipEntry($zip, $entryName, $destPath);
                        } elseif (str_starts_with($entryName, 'public/uploads/')) {
                            $subPath = substr($entryName, strlen('public/uploads/'));
                            $destPath = public_path('uploads') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
                            $this->extractZipEntry($zip, $entryName, $destPath);
                        } elseif (str_starts_with($entryName, 'public/documents/')) {
                            $subPath = substr($entryName, strlen('public/documents/'));
                            $destPath = public_path('documents') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
                            $this->extractZipEntry($zip, $entryName, $destPath);
                        }
                    }
                    $zip->close();
                    $addLog('Storage files extracted successfully.');
                } else {
                    throw new \Exception('Failed to open backup zip for file restoration.');
                }
            }

            $addLog('System restore completed successfully!', 'success');
            $backup->update([
                'restore_logs' => json_encode($logs)
            ]);

        } catch (\Throwable $e) {
            $addLog('Restore Failed: ' . $e->getMessage(), 'error');
            Log::error('Restore Failed: ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'trace' => $e->getTraceAsString()
            ]);

            $backup->update([
                'restore_logs' => json_encode($logs)
            ]);
        } finally {
            $this->deleteDirectory($tempRestoreDir);
        }
    }

    /**
     * Restore MySQL database using mysql CLI or PDO query execution fallback.
     */
    protected function restoreMysqlDatabase(string $sqlPath, callable $addLog): void
    {
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database', 'laravel');

        $passArg = ($dbPass !== null && $dbPass !== '') ? '--password=' . escapeshellarg($dbPass) : '';

        // Attempt 1: Standard mysql CLI
        $cmd = sprintf(
            'mysql --user=%s %s --host=%s --port=%s %s < %s 2>&1',
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );
        @exec($cmd, $output, $returnVar);

        // Attempt 2: XAMPP binary
        if ($returnVar !== 0 && file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')) {
            $cmd = sprintf(
                'C:\\xampp\\mysql\\bin\\mysql.exe --user=%s %s --host=%s --port=%s %s < %s 2>&1',
                escapeshellarg($dbUser),
                $passArg,
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($sqlPath)
            );
            @exec($cmd, $output, $returnVar);
        }

        // Attempt 3: Pure PHP PDO SQL statement execution fallback
        if ($returnVar !== 0) {
            $addLog('mysql CLI unavailable or exited with code; executing via PDO SQL statement runner...');
            $pdo = DB::connection('mysql')->getPdo();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            $sqlContent = file_get_contents($sqlPath);
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));

            foreach ($statements as $stmt) {
                if (!empty($stmt) && !str_starts_with($stmt, '--')) {
                    $pdo->exec($stmt);
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $addLog('MySQL database restored successfully via PDO runner.');
        } else {
            $addLog('MySQL database imported successfully via mysql CLI.');
        }
    }

    /**
     * Safely extract a single zip entry to a destination file path.
     */
    protected function extractZipEntry(\ZipArchive $zip, string $entryName, string $destPath): void
    {
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stream = $zip->getStream($entryName);
        if ($stream) {
            $dest = fopen($destPath, 'wb');
            stream_copy_to_stream($stream, $dest);
            fclose($dest);
            fclose($stream);
        }
    }

    /**
     * Delete directory recursively.
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
