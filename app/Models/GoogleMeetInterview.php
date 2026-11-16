<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleMeetInterview extends Model
{
    protected $fillable = [
        'attempt_id',
        'user_id',
        'google_space_name',
        'meeting_code',
        'meeting_uri',
        'organizer_email',
        'meeting_status',
        'scheduled_at',
        'space_details',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'space_details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }
}
