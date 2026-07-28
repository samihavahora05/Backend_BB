<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegePerformance extends Model
{
    protected $fillable = [
        'college_id',
        'total_students',
        'placement_percent',
        'total_internships',
        'total_certificates',
        'performance_score',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(User::class, 'college_id');
    }
}
