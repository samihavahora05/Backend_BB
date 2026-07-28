<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'company_name',
        'salary_package',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'salary_package' => 'decimal:2',
            'placed_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
