<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'course_approval_required',
        'hide_reviews',
        'expiry_email_days',
        'updated_by',
    ];

    protected $casts = [
        'course_approval_required' => 'boolean',
        'hide_reviews' => 'boolean',
        'expiry_email_days' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
