<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentLetter extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'generated_at'  => 'datetime',
        'downloaded_at' => 'datetime',
        'metadata'      => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(InternshipApplication::class, 'application_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}