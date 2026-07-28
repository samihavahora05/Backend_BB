<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'action',
        'message',
    ];

    public function backup()
    {
        return $this->belongsTo(SystemBackup::class, 'backup_id');
    }
}
