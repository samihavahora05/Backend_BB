<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'industry',
        'company_size',
        'website',
        'logo',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function jobs() { return $this->hasMany(Job::class, 'company_profile_id'); }
    public function internships() { return $this->hasMany(Internship::class, 'company_profile_id'); }
}
