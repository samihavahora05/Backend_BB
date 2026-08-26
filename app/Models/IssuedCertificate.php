<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssuedCertificate extends Model {
    use SoftDeletes;
    protected $fillable = [
        'certificate_number',
        'user_id',
        'course_id',
        'template_id',
        'status',
        'completion_percentage',
        'grade',
        'remarks',
        'pdf_path',
        'issued_at',
        'expires_at',
    ];
    
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
