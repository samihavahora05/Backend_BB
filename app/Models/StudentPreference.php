<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPreference extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'notification_preferences' => 'array',
        'privacy_settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
