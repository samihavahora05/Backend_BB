<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CertificateSetting extends Model {
    protected $fillable = [
        'prefix',
        'number_format',
        'default_template_id',
        'enable_qr_code',
        'enable_verification',
        'auto_generate',
        'auto_email',
        'date_format',
        'expiry_days',
    ];
}

