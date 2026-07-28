<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestoreLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'restored_by',
        'status',
        'details',
    ];

    public function backup()
    {
        return $this->belongsTo(SystemBackup::class, 'backup_id');
    }

    public function restorer()
    {
        return $this->belongsTo(User::class, 'restored_by');
    }
}
