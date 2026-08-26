<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CertificateQrCode extends Model {
    protected $fillable = [
        'issued_certificate_id',
        'qr_code_path',
        'target_url',
    ];
}
