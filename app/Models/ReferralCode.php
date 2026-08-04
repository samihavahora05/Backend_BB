<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'code', 'total_clicks'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
