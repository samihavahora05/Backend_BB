<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'user' => [
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'profile_photo' => $this->user->profile_photo,
            ],
            'professional_details' => [
                'designation' => $this->designation,
                'company' => $this->company,
                'bio' => $this->bio,
                'experience_years' => $this->experience_years,
                'highest_qualification' => $this->highest_qualification,
                'specialization' => $this->specialization,
            ],
            'financials' => [
                'hourly_rate' => $this->hourly_rate,
                'is_available' => $this->is_available,
            ],
            'social_links' => [
                'linkedin' => $this->linkedin_url,
                'github' => $this->github_url,
                'portfolio' => $this->portfolio_url,
                'website' => $this->website,
            ],
            'metrics' => [
                'total_revenue' => $this->total_revenue,
                'average_rating' => $this->average_rating,
                'total_reviews' => $this->total_reviews,
                'total_courses_sold' => $this->total_courses_sold,
                'total_students' => $this->total_students,
                'total_certificates_issued' => $this->total_certificates_issued,
                'completion_rate' => $this->completion_rate,
                'student_satisfaction' => $this->student_satisfaction,
            ],
            'status' => [
                'approval_status' => $this->approval_status,
                'is_verified' => $this->is_verified,
                'profile_completion_percentage' => $this->profile_completion_percentage,
            ],
            // Relations
            'skills' => $this->user->expertSkills,
            'languages' => $this->user->expertLanguages,
            'certificates' => $this->user->expertCertificates,
            'documents' => $this->user->expertDocuments,
        ];
    }
}
