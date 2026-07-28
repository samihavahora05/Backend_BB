<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'course_interested',
        'source',
        'source_page',
        'status', // new, contacted, in_progress, converted, closed, spam, dead
        'ip_address',
        'browser',
        'internal_notes',
        'assigned_admin_id',
    ];

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }
}
