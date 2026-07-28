<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsCache extends Model
{
    protected $table = 'analytics_cache';
    protected $primaryKey = 'metric_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'metric_key',
        'data',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];
}
