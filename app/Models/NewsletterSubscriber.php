<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'is_active',
        'ip_address',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
