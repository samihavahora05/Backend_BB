<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateTemplate extends Model {
    use SoftDeletes;
    protected $fillable = [
        'title',
        'background_image_path',
        'default_font_id',
        'default_signature_id',
        'layout_settings',
        'is_active',
    ];
    protected $casts = [
        'layout_settings' => 'array'
    ];
}
