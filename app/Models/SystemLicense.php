<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemLicense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'license_key',
        'domain',
        'email',
        'status',
        'expires_at',
        'activated_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'metadata' => 'array',
    ];
}
