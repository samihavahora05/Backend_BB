<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'size',
        'size_bytes',
        'status',
        'disk',
        'file_path',
        'error_message',
        'created_by',
        'checksum',
        'completed_at',
        'restore_logs',
        'duration',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function backupLogs()
    {
        return $this->hasMany(BackupLog::class, 'backup_id');
    }

    public function restoreLogs()
    {
        return $this->hasMany(RestoreLog::class, 'backup_id');
    }
}
