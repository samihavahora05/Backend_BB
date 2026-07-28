<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id_prefix' => $this->job_id_prefix,
            'title' => $this->title,
            'company_name' => $this->company?->companyProfile?->company_name ?? $this->company?->first_name,
            'department' => $this->department,
            'employment_type' => $this->employment_type,
            'remote_type' => $this->remote_type,
            'location' => $this->location,
            'vacancies' => $this->vacancies,
            'application_deadline' => $this->application_deadline,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
