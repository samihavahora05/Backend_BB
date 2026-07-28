<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageRecipient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'message_thread_id',
        'message_id',
        'recipient_id',
        'read_at',
        'is_starred',
        'is_pinned',
        'is_important',
        'is_archived',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_starred' => 'boolean',
        'is_pinned' => 'boolean',
        'is_important' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function thread()
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
