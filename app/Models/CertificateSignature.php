<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateSignature extends Model {
    use SoftDeletes;
    protected $fillable = [
        'name',
        'image_path',
        'is_active',
    ];
}
