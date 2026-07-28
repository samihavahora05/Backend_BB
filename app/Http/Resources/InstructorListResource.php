<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'first_name' => $this->user?->first_name ?? 'Unknown',
            'last_name' => $this->user?->last_name ?? '',
            'email' => $this->user?->email ?? '',
            'profile_photo' => $this->user?->profile_photo ?? null,
            'designation' => $this->designation,
            'company' => $this->company,
            'experience_years' => $this->experience_years,
            'approval_status' => $this->approval_status,
            'is_verified' => $this->is_verified,
            'profile_completion_percentage' => $this->profile_completion_percentage,
            'average_rating' => $this->average_rating,
        ];
    }
}
