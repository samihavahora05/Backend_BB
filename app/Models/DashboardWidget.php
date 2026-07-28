<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = [
        'name',
        'key',
        'type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
