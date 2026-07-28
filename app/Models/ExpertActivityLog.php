<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertActivityLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
