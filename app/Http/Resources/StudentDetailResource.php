<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
            
            'profile' => $this->studentProfile,
            'education' => $this->education,
            'skills' => $this->skills,
            'social_links' => $this->socialLinks,
            'documents' => $this->documents,
            'preferences' => $this->preferences,
        ];
    }
}
