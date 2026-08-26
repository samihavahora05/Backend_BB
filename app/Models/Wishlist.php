<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

}