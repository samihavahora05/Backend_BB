<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemEmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body',
        'variables',
        'status',
    ];

    protected $casts = [
        'variables' => 'array',
    ];
}
