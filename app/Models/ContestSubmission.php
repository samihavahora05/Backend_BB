<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestSubmission extends Model
{
    protected $fillable = [
        'contest_task_id',
        'user_id',
        'repository_url',
        'attachments',
        'score',
        'feedback',
        'is_winner',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_winner' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ContestTask::class, 'contest_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
