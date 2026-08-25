<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEducation extends Model
{
    use HasFactory;

    protected $table = 'student_education';
    protected $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'college_id',
        'college_name',
        'university',
        'course',
        'specialization',
        'semester',
        'start_year',
        'end_year',
        'cgpa',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
