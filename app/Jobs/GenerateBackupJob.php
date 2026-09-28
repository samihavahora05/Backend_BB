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

        // Ensure disk root and backup folder exist
        $disk = Storage::disk('local');
        $fullPath = $disk->path($backup->file_path);
        $backupDir = dirname($fullPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $tempDir = $disk->path('backups/temp_' . $backup->id . '_' . time());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            $tempSqlPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
            $tempSqlitePath = $tempDir . DIRECTORY_SEPARATOR . 'database.sqlite';

            // 1. DATABASE EXPORT
            if ($backup->type === 'Database' || $backup->type === 'Complete') {
                $driver = config('database.default', 'sqlite');

                if ($driver === 'sqlite') {
                    $sqlitePath = config('database.connections.sqlite.database', database_path('database.sqlite'));
                    if (file_exists($sqlitePath)) {
                        copy($sqlitePath, $tempSqlitePath);
                        // Also create a readable SQL dump
                        $this->dumpSqliteToSql($sqlitePath, $tempSqlPath);
                    } else {
                        file_put_contents($tempSqlPath, "-- Empty SQLite database\n");
                    }
                } else {
                    // MySQL or other driver
                    $this->dumpMysqlToSql($tempSqlPath);
                }

                // If standalone Database backup
                if ($backup->type === 'Database') {
                    if (file_exists($tempSqlPath)) {
                        copy($tempSqlPath, $fullPath);
                    } elseif (file_exists($tempSqlitePath)) {
                        copy($tempSqlitePath, $fullPath);
                    }
                }
            }

            // 2. FILES OR COMPLETE BACKUP (ZIP)
            if ($backup->type === 'Files' || $backup->type === 'Complete') {
                $zip = new \ZipArchive();
                $zipResult = $zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                
                if ($zipResult !== TRUE) {
                    throw new \Exception('Failed to create zip archive: Error code ' . $zipResult);
                }

                $filesCount = 0;

                // Add storage/app/public files
                $publicStorageDir = storage_path('app/public');
                if (is_dir($publicStorageDir)) {
                    $filesCount += $this->addDirectoryToZip($zip, $publicStorageDir, 'storage/public');
                }

                // Add public/uploads if exists
                $publicUploadsDir = public_path('uploads');
                if (is_dir($publicUploadsDir)) {
                    $filesCount += $this->addDirectoryToZip($zip, $publicUploadsDir, 'public/uploads');
                }

                // Add public/documents if exists
                $publicDocsDir = public_path('documents');
                if (is_dir($publicDocsDir)) {
                    $filesCount += $this->addDirectoryToZip($zip, $publicDocsDir, 'public/documents');
                }

                // If Complete backup, include database dumps & manifest
                if ($backup->type === 'Complete') {
                    if (file_exists($tempSqlPath)) {
                        $zip->addFile($tempSqlPath, 'database.sql');
                    }
                    if (file_exists($tempSqlitePath)) {
                        $zip->addFile($tempSqlitePath, 'database.sqlite');
                    }

                    $manifest = [
                        'backup_id' => $backup->id,
                        'name' => $backup->name,
                        'type' => $backup->type,
                        'created_at' => Carbon::now()->toIso8601String(),
                        'app_name' => config('app.name', 'Blueboxx DA'),
                        'db_driver' => config('database.default', 'sqlite'),
                        'total_files' => $filesCount,
                    ];
                    $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
                }

                $zip->close();
            }

            if (!file_exists($fullPath)) {
                throw new \Exception('Backup file was not created at expected path: ' . $fullPath);
            }

            $endTime = Carbon::now();
            $duration = $startTime->diffInSeconds($endTime);
            $bytes = filesize($fullPath);
            $checksum = md5_file($fullPath);

            // Format human-readable size
            if ($bytes >= 1073741824) {
                $sizeFormatted = number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                $sizeFormatted = number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                $sizeFormatted = number_format($bytes / 1024, 2) . ' KB';
            } else {
                $sizeFormatted = $bytes . ' B';
            }

            $backup->update([
                'status' => 'completed',
                'size' => $sizeFormatted,
                'size_bytes' => $bytes,
                'checksum' => $checksum,
                'completed_at' => $endTime,
                'duration' => $duration,
                'error_message' => null
            ]);

        } catch (\Throwable $e) {
            Log::error('Backup Generation Failed: ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'trace' => $e->getTraceAsString()
            ]);

            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => Carbon::now()
            ]);
        } finally {
            // Clean up temporary directory
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Add directory contents recursively to zip.
     */
    protected function addDirectoryToZip(\ZipArchive $zip, string $dir, string $zipPrefix): int
    {
        $count = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $realPath = $file->getRealPath();
                $relativePath = substr($realPath, strlen(realpath($dir)) + 1);
                $zipPath = $zipPrefix . '/' . str_replace('\\', '/', $relativePath);
                $zip->addFile($realPath, $zipPath);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Export SQLite database to standard SQL statements.
     */
    protected function dumpSqliteToSql(string $sqlitePath, string $sqlPath): void
    {
        $pdo = new \PDO('sqlite:' . $sqlitePath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $out = fopen($sqlPath, 'w');
        fwrite($out, "-- Blueboxx SQLite Database Backup\n");
        fwrite($out, "-- Generated: " . Carbon::now()->toDateTimeString() . "\n\n");
        fwrite($out, "PRAGMA foreign_keys = OFF;\n\n");

        $tablesStmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        while ($row = $tablesStmt->fetch(\PDO::FETCH_ASSOC)) {
            $table = $row['name'];
            $createSql = $row['sql'];

            fwrite($out, "-- ----------------------------\n");
            fwrite($out, "-- Table structure for `{$table}`\n");
            fwrite($out, "-- ----------------------------\n");
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($out, $createSql . ";\n\n");

            // Dump data
            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                fwrite($out, "-- Records for `{$table}`\n");
                foreach ($rows as $r) {
                    $cols = array_map(function($c) { return '`' . $c . '`'; }, array_keys($r));
                    $vals = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($r));

                    fwrite($out, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n");
                }
                fwrite($out, "\n");
            }
        }

        fwrite($out, "PRAGMA foreign_keys = ON;\n");
        fclose($out);
    }

    /**
     * Export MySQL database to SQL script using mysqldump or PDO fallback.
     */
    protected function dumpMysqlToSql(string $sqlPath): void
    {
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database', 'laravel');

        $passArg = ($dbPass !== null && $dbPass !== '') ? '--password=' . escapeshellarg($dbPass) : '';
        
        // Attempt 1: Standard PATH mysqldump
        $cmd = sprintf(
            'mysqldump --user=%s %s --host=%s --port=%s %s > %s 2>&1',
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );
        @exec($cmd, $output, $returnVar);

        // Attempt 2: Common XAMPP location if on Windows
        if ($returnVar !== 0 && file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
            $cmd = sprintf(
                'C:\\xampp\\mysql\\bin\\mysqldump.exe --user=%s %s --host=%s --port=%s %s > %s 2>&1',
                escapeshellarg($dbUser),
                $passArg,
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($sqlPath)
            );
            @exec($cmd, $output, $returnVar);
        }

        // Attempt 3: Pure PHP PDO Dumper Fallback (Bulletproof across all hosting environments)
        if ($returnVar !== 0 || !file_exists($sqlPath) || filesize($sqlPath) === 0) {
            $this->dumpMysqlViaPdo($sqlPath);
        }
    }

    /**
     * Pure PHP MySQL dumper using PDO.
     */
    protected function dumpMysqlViaPdo(string $sqlPath): void
    {
        $pdo = DB::connection('mysql')->getPdo();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $out = fopen($sqlPath, 'w');
        fwrite($out, "-- Blueboxx MySQL Database Backup (PDO Engine)\n");
        fwrite($out, "-- Generated: " . Carbon::now()->toDateTimeString() . "\n\n");
        fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'");
        $tables = $tablesStmt->fetchAll(\PDO::FETCH_NUM);

        foreach ($tables as $tableRow) {
            $table = $tableRow[0];

            fwrite($out, "-- ----------------------------\n");
            fwrite($out, "-- Table structure for `{$table}`\n");
            fwrite($out, "-- ----------------------------\n");
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
            fwrite($out, $createRow[1] . ";\n\n");

            // Dump rows in chunks
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $totalRows = (int)$countStmt->fetchColumn();

            if ($totalRows > 0) {
                fwrite($out, "-- Records for `{$table}`\n");
                $limit = 500;
                for ($offset = 0; $offset < $totalRows; $offset += $limit) {
                    $dataStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$limit} OFFSET {$offset}");
                    $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $first = true;
                        foreach ($rows as $r) {
                            if ($first) {
                                $cols = array_map(function($c) { return '`' . $c . '`'; }, array_keys($r));
                                fwrite($out, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES\n");
                                $first = false;
                            } else {
                                fwrite($out, ",\n");
                            }

                            $vals = array_map(function($v) use ($pdo) {
                                return $v === null ? 'NULL' : $pdo->quote($v);
                            }, array_values($r));

                            fwrite($out, "(" . implode(', ', $vals) . ")");
                        }
                        fwrite($out, ";\n");
                    }
                }
                fwrite($out, "\n");
            }
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($out);
    }

    /**
     * Delete directory and its contents recursively.
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
