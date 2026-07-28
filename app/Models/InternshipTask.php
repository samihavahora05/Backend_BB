<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipTask extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'attachments' => 'array',
        'deadline' => 'datetime',
    ];

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function submissions()
    {
        return $this->hasMany(InternshipSubmission::class, 'task_id');
    }
}
