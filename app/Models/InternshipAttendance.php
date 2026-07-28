<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipAttendance extends Model
{
    use HasFactory;

    protected $table = 'internship_attendance';
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
