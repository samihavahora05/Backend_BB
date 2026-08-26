<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarshipApplication extends Model
{
    protected $fillable = [
        'user_id',
        'scholarship_program_id',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scholarshipProgram()
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }
}

