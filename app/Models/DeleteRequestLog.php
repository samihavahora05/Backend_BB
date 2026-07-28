<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delete_request_id',
        'admin_id',
        'action',
        'notes',
        'ip_address',
    ];

    public function deleteRequest()
    {
        return $this->belongsTo(DeleteRequest::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
