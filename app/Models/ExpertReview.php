<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertReview extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
