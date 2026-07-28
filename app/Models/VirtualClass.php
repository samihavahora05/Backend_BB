<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualClass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'course_id', 'instructor_id', 'category_id',
        'language', 'duration_minutes', 'max_students', 'start_datetime', 'end_datetime',
        'status', 'platform', 'meeting_id', 'meeting_password', 'join_url', 'start_url',
        'recording_url', 'is_recorded', 'enrolled_count', 'price', 'is_free',
        'thumbnail', 'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
        'is_free'        => 'boolean',
        'is_recorded'    => 'boolean',
        'price'          => 'decimal:2',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments()
    {
        return $this->hasMany(VirtualClassEnrollment::class);
    }
}
