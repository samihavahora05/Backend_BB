<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id_prefix' => $this->job_id_prefix,
            'company' => $this->company,
            'title' => $this->title,
            'department' => $this->department,
            'industry' => $this->industry,
            'employment_type' => $this->employment_type,
            'experience_level' => $this->experience_level,
            'remote_type' => $this->remote_type,
            'location' => $this->location,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'hide_salary' => $this->hide_salary,
            
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'required_skills' => $this->required_skills,
            
            'vacancies' => $this->vacancies,
            'application_deadline' => $this->application_deadline,
            'thumbnail' => $this->thumbnail,
            'preview_video' => $this->preview_video,
            
            'seo_title' => $this->seo_title,
            'seo_keywords' => $this->seo_keywords,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            
            'documents' => $this->documents,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
