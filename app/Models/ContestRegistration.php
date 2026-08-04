<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContestRegistration extends Model
{
    protected $guarded = [];

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
