<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'answer',
        'is_instructor',
        'is_admin',
    ];

    protected $casts = [
        'is_instructor' => 'boolean',
        'is_admin' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(CourseQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
