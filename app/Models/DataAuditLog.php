<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAuditLog extends Model
{
    protected $fillable = [
        'table_name',
        'column_name',
        'record_id',
        'action',
        'old_value',
        'new_value',
        'admin_id',
        'ip_address',
        'device',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
