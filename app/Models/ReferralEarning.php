<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'referral_id', 'amount', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }
}
