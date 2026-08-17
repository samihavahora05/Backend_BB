<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'virtual_class_id',
        'title',
        'questions',
        'passing_score',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
        ];
    }

    public function virtualClass(): BelongsTo
    {
        return $this->belongsTo(VirtualClass::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }
}
