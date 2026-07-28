<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requested_role_id',
        'status',
        'reason',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestedRole()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'requested_role_id');
    }
}
