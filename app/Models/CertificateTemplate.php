<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateTemplate extends Model {
    use SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'layout_settings' => 'array'
    ];
}
