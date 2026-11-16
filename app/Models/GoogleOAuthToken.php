<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleOAuthToken extends Model
{
    protected $table = 'google_oauth_tokens';

    protected $fillable = [
        'email',
        'access_token',
        'refresh_token',
        'expires_in',
        'token_type',
        'scope',
        'created_at_timestamp',
    ];

    protected $casts = [
        'created_at_timestamp' => 'datetime',
    ];
}
