<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'expert_id',
        'booking_date',
        'start_time',
        'end_time',
        'amount',
        'order_id', // Razorpay Order ID
        'status', // Pending, Confirmed, Completed, Cancelled
        'meeting_link',
        'student_notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function setStartTimeAttribute($value)
    {
        if (!empty($value)) {
            try {
                $this->attributes['start_time'] = \Carbon\Carbon::parse($value)->format('H:i:s');
                return;
            } catch (\Exception $e) {}
        }
        $this->attributes['start_time'] = $value;
    }

    public function setEndTimeAttribute($value)
    {
        if (!empty($value)) {
            try {
                $this->attributes['end_time'] = \Carbon\Carbon::parse($value)->format('H:i:s');
                return;
            } catch (\Exception $e) {}
        }
        $this->attributes['end_time'] = $value;
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(ExpertProfile::class, 'expert_id');
    }
}
