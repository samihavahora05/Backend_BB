<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CertificateDownload extends Model {
    protected $fillable = [
        'issued_certificate_id',
        'user_id',
        'ip_address',
    ];
}
