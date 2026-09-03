<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertReview extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['user', 'reviewer_name', 'reviewer_avatar', 'comment'];

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    public function expertProfile()
    {
        return $this->belongsTo(ExpertProfile::class, 'expert_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserAttribute()
    {
        $u = $this->relationLoaded('user') && $this->getRelation('user') 
            ? $this->getRelation('user') 
            : ($this->relationLoaded('student') && $this->getRelation('student') ? $this->getRelation('student') : null);
        
        if (!$u) {
            $userId = $this->user_id ?? $this->student_id;
            if ($userId) {
                $u = User::find($userId);
            }
        }

        if ($u) {
            $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: ($u->name ?? 'Learner');
            return [
                'id' => $u->id,
                'name' => $name,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'email' => $u->email,
                'avatar' => $u->profile_photo ?? null,
            ];
        }

        return [
            'id' => $this->user_id ?? $this->student_id ?? 0,
            'name' => 'Verified Student',
            'avatar' => null,
        ];
    }

    public function getReviewerNameAttribute()
    {
        return $this->user['name'] ?? 'Verified Student';
    }

    public function getReviewerAvatarAttribute()
    {
        return $this->user['avatar'] ?? null;
    }

    public function getCommentAttribute()
    {
        return $this->review_text ?? '';
    }
}
