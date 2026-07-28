<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'title',
        'question',
        'status',
        'is_pinned',
        'is_reported',
        'reported_reason',
        'resolved_at',
        'closed_at'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_reported' => 'boolean',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Assuming a Course model exists. If not, this might be a soft relation in some systems, but standard is belongsTo
    public function course()
    {
        return $this->belongsTo(\App\Models\Course::class, 'course_id');
    }

    public function answers()
    {
        return $this->hasMany(CourseAnswer::class, 'question_id');
    }

    public function reports()
    {
        return $this->hasMany(CourseQuestionReport::class, 'question_id');
    }
}
