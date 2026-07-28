<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseQuestionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'reported_by',
        'reason',
        'status',
    ];

    public function question()
    {
        return $this->belongsTo(CourseQuestion::class, 'question_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
