<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_role',
        'requested_role',
        'requested_role_id',
        'status',
        'reason',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'requested_role',
        'current_role',
    ];

    public function getRequestedRoleAttribute()
    {
        if (!empty($this->attributes['requested_role'])) {
            return $this->attributes['requested_role'];
        }
        return $this->requestedRole?->name ?? 'jobseeker';
    }

    public function getCurrentRoleAttribute()
    {
        if (!empty($this->attributes['current_role'])) {
            return $this->attributes['current_role'];
        }
        return $this->user?->roles?->first()?->name ?? 'student';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestedRole()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'requested_role_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}