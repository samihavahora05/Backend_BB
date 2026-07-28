<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageThread extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject',
        'creator_id',
        'type', // private, announcement, broadcast, chat
        'status', // active, archived, closed
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }
}
