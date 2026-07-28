<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContestTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contest_id',
        'title',
        'description',
        'rules',
        'max_score',
    ];

    protected $casts = [
        'rules' => 'array',
    ];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }
}
