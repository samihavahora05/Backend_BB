<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssuedCertificate extends Model {
    use SoftDeletes;
    protected $guarded = [];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function course() {
        return $this->belongsTo(Course::class);
    }
    
    public function template() {
        return $this->belongsTo(CertificateTemplate::class);
    }
}
