<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'total_questions',
        'total_marks',
        'passing_percentage',
        'passing_marks',
        'duration_minutes',
        'time_limit_minutes',
        'status',
        'category',
        'category_breakdown',
    ];

    protected $casts = [
        'category_breakdown' => 'array',
        'total_questions' => 'integer',
        'total_marks' => 'integer',
        'passing_percentage' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('order', 'asc');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
}
