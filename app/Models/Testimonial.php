<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'company',
        'content',
        'rating',
        'status',
        'display_order',
        'photo_url',
        'is_featured',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->photo_url) {
            return asset('storage/' . $this->photo_url);
        }
        
        return null;
    }
}
