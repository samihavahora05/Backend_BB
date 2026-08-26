<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CertificateVerification extends Model {
    protected $fillable = [
        'issued_certificate_id',
        'verification_token',
        'verification_url',
        'verification_count',
        'last_verified_at',
    ];
}
