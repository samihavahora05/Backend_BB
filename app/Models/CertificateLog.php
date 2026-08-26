<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CertificateLog extends Model {
    protected $fillable = [
        'issued_certificate_id',
        'user_id',
        'action',
        'description',
    ];
}
