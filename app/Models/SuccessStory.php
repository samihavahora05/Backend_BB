<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuccessStory extends Model
{
    protected $fillable = [
        'student_name',
        'course_name',
        'company_name',
        'package',
        'story',
        'photo_url',
        'is_featured',
    ];
}

