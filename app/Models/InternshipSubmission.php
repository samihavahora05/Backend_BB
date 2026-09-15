<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'version',
        'submission_text',
        'submission_comment',
        'file_paths',
        'proof_files',
        'github_link',
        'video_link',
        'status',
        'marks_obtained',
        'feedback',
        'reviewer_id',
        'reviewed_at',
    ];

    protected $casts = [
        'file_paths'     => 'array',
        'proof_files'    => 'array',
        'reviewed_at'    => 'datetime',
        'marks_obtained' => 'float',
        'version'        => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(InternshipTask::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
