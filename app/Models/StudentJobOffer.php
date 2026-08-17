<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
