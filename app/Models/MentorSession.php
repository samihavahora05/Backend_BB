<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'expert_profile_id',
        'title',
        'duration_minutes',
        'price',
        'is_active',
        // Legacy direct-booking fields (used by MentorSessionController)
        'student_id',
        'expert_id',
        'scheduled_at',
        'meeting_url',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'scheduled_at' => 'datetime',
        ];
    }

    public function expertProfile(): BelongsTo
    {
        return $this->belongsTo(ExpertProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(MentorBooking::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
}
