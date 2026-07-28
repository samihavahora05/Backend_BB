<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_id',
        'user_id',
        'entry_date',
        'content',
        'hours_logged',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hours_logged' => 'decimal:2',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
