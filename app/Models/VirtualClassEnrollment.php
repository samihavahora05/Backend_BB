<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualClassEnrollment extends Model
{
    protected $fillable = [
        'virtual_class_id', 'user_id', 'status', 'joined_at', 'duration_attended_minutes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function virtualClass()
    {
        return $this->belongsTo(VirtualClass::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
