<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\StorageHelper;

class StudentJobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_name',
        'degree',
        'company_name',
        'role',
        'offered_on',
        'package',
        'avatar_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'photo_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return StorageHelper::url($this->avatar_url);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return StorageHelper::url($this->avatar_url);
    }
}
