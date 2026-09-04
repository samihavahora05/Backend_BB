<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstName = $this->user?->first_name ?? '';
        $lastName = $this->user?->last_name ?? '';
        $fullName = trim($firstName . ' ' . $lastName);

        $rawPhoto = $this->profile_photo;
        $avatarUrl = $rawPhoto ? \App\Support\StorageHelper::url($rawPhoto) : null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $firstName ?: 'Expert',
            'last_name' => $lastName,
            'name' => !empty($fullName) ? $fullName : 'Expert User',
            'email' => $this->user?->email ?? '',
            'phone' => $this->user?->phone ?? '',
            'profile_photo' => $avatarUrl,
            'avatar' => $avatarUrl,
            'designation' => $this->designation ?? 'Expert',
            'company' => $this->company ?? 'Independent',
            'specialization' => $this->specialization ?? 'Career & Technical Mentorship',
            'hourly_rate' => (float)($this->hourly_rate ?? 1500),
            'experience_years' => $this->experience_years ?? 0,
            'approval_status' => $this->approval_status ?? 'approved',
            'is_verified' => (bool)($this->is_verified ?? true),
            'is_available' => (bool)($this->is_available ?? true),
            'profile_completion_percentage' => $this->profile_completion_percentage ?? 100,
            'average_rating' => (float)($this->average_rating ?? 5.0),
            'total_reviews' => (int)($this->total_reviews ?? 0),
        ];
    }
}
