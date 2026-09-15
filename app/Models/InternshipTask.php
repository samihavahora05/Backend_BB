<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'internship_id',
        'assigned_to',
        'assigned_by',
        'admin_id',
        'title',
        'description',
        'instructions',
        'expected_deliverable',
        'priority',
        'status',
        'start_date',
        'due_date',
        'deadline',
        'completed_at',
        'attachments',
        'marks',
        'max_marks',
        'feedback',
    ];

    protected $casts = [
        'attachments'   => 'array',
        'deadline'      => 'datetime',
        'start_date'    => 'datetime',
        'due_date'      => 'datetime',
        'completed_at'  => 'datetime',
        'marks'         => 'float',
        'max_marks'     => 'float',
    ];

    protected $appends = ['is_overdue', 'effective_due_date'];

    public function getEffectiveDueDateAttribute()
    {
        $due = $this->attributes['due_date'] ?? $this->attributes['deadline'] ?? null;
        return $due ? $this->asDateTime($due) : null;
    }

    public function getIsOverdueAttribute(): bool
    {
        $due = $this->effective_due_date;
        if (!$due) {
            return false;
        }
        $status = $this->attributes['status'] ?? null;
        return $due->isPast() && !in_array($status, ['completed', 'approved']);
    }

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function submissions()
    {
        return $this->hasMany(InternshipSubmission::class, 'task_id')->orderBy('version', 'desc')->orderBy('id', 'desc');
    }

    public function latestSubmission()
    {
        return $this->hasOne(InternshipSubmission::class, 'task_id')->latestOfMany();
    }
}
