<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'start_date',
        'end_date',
        'status',
        'college_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function college()
    {
        return $this->belongsTo(User::class, 'college_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ContestRegistration::class);
    }
}
